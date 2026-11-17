<?php
/**
 * Íconos SVG de línea (sin dependencias externas). Uso: icono('libreta')
 */
function icono(string $nombre, string $clase = 'ico'): string
{
    static $p = [
        'inicio'       => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'libreta'      => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 2.5h6v3H9zM9 10h6M9 14h6M9 18h3"/>',
        'calendario'   => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M8 14h2M14 14h2M8 17h2"/>',
        'documento'    => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'campana'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/>',
        'usuario'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'usuarios'     => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4.1a4 4 0 0 1 0 7.8M22 21a7 7 0 0 0-4-6.3"/>',
        'check'        => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'check_circulo'=> '<circle cx="12" cy="12" r="9.5"/><path d="m8 12.5 3 3 5-6"/>',
        'candado'      => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'escudo'       => '<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
        'escudo_check' => '<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'salir'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'sol'          => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'luna'         => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'mas'          => '<path d="M12 5v14M5 12h14"/>',
        'flecha_der'   => '<path d="m9 6 6 6-6 6"/>',
        'flecha_izq'   => '<path d="m15 6-6 6 6 6"/>',
        'alerta'       => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'correo'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pulso'        => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
        'monitor'      => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'hospital'     => '<path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16M12 7v6M9 10h6M10 21v-4h4v4M2 21h20"/>',
        'casa'         => '<path d="m3 11 9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5"/>',
        'libro'        => '<path d="M4 19V5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2 2 2 0 0 0 2 2h13"/>',
        'descarga'     => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
        'buscar'       => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'editar'       => '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
        'basura'       => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'reloj'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'grafico'      => '<path d="M4 20V11M10 20V5M16 20v-7M2 20h20"/>',
        'enviar'       => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/>',
        'estetoscopio' => '<path d="M6 3H5v6a5 5 0 0 0 10 0V3h-1M10 14v1a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="11" r="2"/>',
        'birrete'      => '<path d="M2 9 12 4l10 5-10 5zM6 11v5c3 2 9 2 12 0v-5M22 9v6"/>',
        'ojo'          => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'filtro'       => '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
    ];
    $c = $p[$nombre] ?? $p['documento'];
    return '<svg class="' . $clase . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"'
         . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $c . '</svg>';
}
