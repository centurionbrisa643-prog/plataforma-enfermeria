<?php
/**
 * Consultas reutilizables.
 */

/** Eventos visibles para un estudiante (o todos si es admin) entre dos fechas. */
function eventos_para(array $u, string $desde, string $hasta, ?int $limite = null): array
{
    $sql = 'SELECT e.*, s.numero AS semestre_numero, se.nombre AS sede, t.nombre AS turno
              FROM eventos_calendario e
              LEFT JOIN semestres s ON s.id = e.semestre_id
              LEFT JOIN sedes se ON se.id = e.sede_id
              LEFT JOIN turnos t ON t.id = e.turno_id
             WHERE COALESCE(e.fecha_fin, e.fecha_inicio) >= ? AND e.fecha_inicio < ?';
    $p = [$desde, $hasta];
    if ($u['rol'] === 'estudiante') {
        $actual = (int)q('SELECT semestre_actual FROM v_estudiantes WHERE id = ?', [$u['id']])->fetchColumn();
        $sql .= ' AND (e.sede_id IS NULL OR e.sede_id = ?)
                  AND (e.turno_id IS NULL OR e.turno_id = ?)
                  AND (e.semestre_id IS NULL OR s.numero = ?)';
        array_push($p, $u['sede_id'], $u['turno_id'], $actual);
    }
    $sql .= ' ORDER BY e.fecha_inicio';
    if ($limite) $sql .= ' LIMIT ' . (int)$limite;
    return q($sql, $p)->fetchAll();
}

/** Progreso de la libreta por semestre: [semestre_id => ['total'=>, 'hechas'=>]] */
function progreso_libreta(int $estudianteId): array
{
    $filas = q('SELECT p.semestre_id, COUNT(*) AS total, COUNT(r.id) AS hechas
                  FROM procedimientos p
                  LEFT JOIN practicas_realizadas r ON r.procedimiento_id = p.id AND r.estudiante_id = ?
                 WHERE p.activo
                 GROUP BY p.semestre_id', [$estudianteId])->fetchAll();
    $r = [];
    foreach ($filas as $f) $r[$f['semestre_id']] = ['total' => (int)$f['total'], 'hechas' => (int)$f['hechas']];
    return $r;
}

function porcentaje(int $hechas, int $total): int
{
    return $total ? (int)round($hechas * 100 / $total) : 0;
}

/** Renderiza un evento como fila de lista. */
function fila_evento(array $ev, bool $admin = false): string
{
    $t = strtotime($ev['fecha_inicio']);
    $alcance = [];
    if ($admin) {
        $alcance[] = $ev['semestre_numero'] ? $ev['semestre_numero'] . '.º sem.' : 'Todos los semestres';
        if ($ev['turno']) $alcance[] = $ev['turno'];
        if ($ev['sede']) $alcance[] = $ev['sede'];
    }
    $rango = fecha_larga($ev['fecha_inicio'], true);
    if ($ev['fecha_fin']) $rango .= ' → ' . fecha_larga($ev['fecha_fin'], true);
    return '<li class="t-' . e($ev['tipo']) . '">
      <div class="fecha-caja"><b>' . date('j', $t) . '</b><span>' . MESES_CORTOS[(int)date('n', $t)] . '</span></div>
      <div class="lista-cuerpo"><span class="tipo-evento">' . e(TIPOS_EVENTO[$ev['tipo']]) . '</span>
        <strong>' . e($ev['titulo']) . '</strong>
        <small>' . e($rango) . ($alcance ? ' · ' . e(implode(' · ', $alcance)) : '') . '</small>'
        . ($ev['descripcion'] ? '<div class="muy-tenue">' . e($ev['descripcion']) . '</div>' : '') . '</div>';
}
