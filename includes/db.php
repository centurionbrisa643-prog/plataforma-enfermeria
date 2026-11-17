<?php
/**
 * Conexión única a PostgreSQL mediante PDO.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET TIME ZONE 'America/Asuncion'");
        } catch (PDOException $e) {
            http_response_code(500);
            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit("<h2>No se pudo conectar a PostgreSQL</h2>
                  <p>Revisá en <code>config/config.php</code> el usuario, la contraseña y que la base
                  <b>" . DB_NAME . "</b> exista en PgAdmin 4. También que en <code>php.ini</code>
                  esté activa la línea <code>extension=pdo_pgsql</code>.</p><pre>$msg</pre>");
        }
    }
    return $pdo;
}

/** Ejecuta una consulta preparada y devuelve el statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
