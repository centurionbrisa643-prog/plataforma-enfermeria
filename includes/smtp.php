<?php
/**
 * Cliente SMTP mínimo (sin librerías externas, no requiere Composer).
 * Soporta STARTTLS (puerto 587), SSL (465) y AUTH LOGIN.
 * Mantiene una sola conexión abierta para enviar varios correos seguidos.
 */
class ClienteSmtp
{
    /** @var resource|null */
    private $conexion = null;

    public function __construct(
        private string $host,
        private int $puerto,
        private string $seguridad,
        private string $usuario,
        private string $clave,
        private string $remitente,
        private string $nombreRemitente,
        private bool $verificarCert = true,
    ) {}

    public function conectar(): void
    {
        $ctx = stream_context_create(['ssl' => [
            'verify_peer' => $this->verificarCert,
            'verify_peer_name' => $this->verificarCert,
        ]]);
        $prefijo = $this->seguridad === 'ssl' ? 'ssl://' : 'tcp://';
        $this->conexion = @stream_socket_client($prefijo . $this->host . ':' . $this->puerto,
            $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->conexion) {
            throw new RuntimeException("No se pudo conectar a {$this->host}:{$this->puerto} ($errstr)");
        }
        stream_set_timeout($this->conexion, 20);
        $this->esperar(220);
        $this->comando('EHLO ' . $this->nombreEquipo(), 250);

        if ($this->seguridad === 'tls') {
            $this->comando('STARTTLS', 220);
            $metodo = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $metodo |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            if (!stream_socket_enable_crypto($this->conexion, true, $metodo)) {
                throw new RuntimeException('No se pudo iniciar TLS (revisá openssl.cafile en php.ini)');
            }
            $this->comando('EHLO ' . $this->nombreEquipo(), 250);
        }
        if ($this->usuario !== '') {
            $this->comando('AUTH LOGIN', 334);
            $this->comando(base64_encode($this->usuario), 334);
            $this->comando(base64_encode($this->clave), 235, true);
        }
    }

    public function enviar(string $para, string $nombrePara, string $asunto, string $html, string $texto): void
    {
        if (!$this->conexion) $this->conectar();
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Dirección inválida: $para");
        }
        $this->comando('MAIL FROM:<' . $this->remitente . '>', 250);
        $this->comando('RCPT TO:<' . $para . '>', [250, 251]);
        $this->comando('DATA', 354);

        $limite = 'b_' . bin2hex(random_bytes(12));
        $cab = [
            'Date: ' . date('r'),
            'From: ' . $this->codificar($this->nombreRemitente) . ' <' . $this->remitente . '>',
            'To: ' . $this->codificar($nombrePara) . ' <' . $para . '>',
            'Subject: ' . $this->codificar($asunto),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $this->dominioRemitente() . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $limite . '"',
        ];
        $cuerpo = implode("\r\n", $cab) . "\r\n\r\n"
            . "--$limite\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($texto))
            . "--$limite\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$limite--\r\n";
        // "dot-stuffing": líneas que empiezan con punto
        $cuerpo = preg_replace('/^\./m', '..', $cuerpo);
        fwrite($this->conexion, $cuerpo . "\r\n.\r\n");
        $this->esperar(250);
    }

    /** Si un envío falla, deja la conexión lista para el siguiente. */
    public function reiniciar(): void
    {
        if ($this->conexion) {
            try { $this->comando('RSET', 250); } catch (Throwable) { $this->cerrar(); }
        }
    }

    public function cerrar(): void
    {
        if ($this->conexion) {
            @fwrite($this->conexion, "QUIT\r\n");
            @fclose($this->conexion);
            $this->conexion = null;
        }
    }

    public function __destruct() { $this->cerrar(); }

    // ---------------------------------------------------------------
    private function comando(string $cmd, int|array $esperado, bool $ocultar = false): string
    {
        fwrite($this->conexion, $cmd . "\r\n");
        return $this->esperar($esperado, $ocultar ? '[credenciales]' : $cmd);
    }

    private function esperar(int|array $esperado, string $cmd = ''): string
    {
        $resp = '';
        while (($linea = fgets($this->conexion, 1024)) !== false) {
            $resp .= $linea;
            if (strlen($linea) < 4 || $linea[3] === ' ') break;   // fin de respuesta multilínea
        }
        $codigo = (int)substr($resp, 0, 3);
        if (!in_array($codigo, (array)$esperado, true)) {
            throw new RuntimeException(trim("SMTP $cmd → " . ($resp ?: 'sin respuesta')));
        }
        return $resp;
    }

    private function codificar(string $s): string
    {
        return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    private function nombreEquipo(): string
    {
        return preg_replace('/[^a-z0-9.\-]/i', '', gethostname() ?: 'localhost') ?: 'localhost';
    }

    private function dominioRemitente(): string
    {
        return substr(strrchr($this->remitente, '@') ?: '@localhost', 1);
    }
}
