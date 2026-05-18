<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$id_user = (int) $_SESSION['usuario_id'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo     = getDB();

// ─── SALDO ────────────────────────────────────────────────────
if ($action === 'saldo') {
    $st = $pdo->prepare('SELECT saldo FROM usuarios WHERE id_user = ?');
    $st->execute([$id_user]);
    $fila = $st->fetch();
    echo json_encode(['ok' => true, 'saldo' => number_format($fila['saldo'], 2)]);
    exit;
}

// ─── PISTAS DISPONIBLES ───────────────────────────────────────
if ($action === 'pistas') {
    $st = $pdo->prepare("
        SELECT id_pista AS id, nombre_pista AS nombre,
               tipo_deporte AS deporte, precio_hora AS precio
        FROM pistas WHERE estado = 'disponible'
    ");
    $st->execute();
    echo json_encode(['ok' => true, 'pistas' => $st->fetchAll()]);
    exit;
}

// ─── MONITORES (todos, con disponibilidad) ────────────────────
if ($action === 'monitores') {
    $st = $pdo->query("
        SELECT id_monitor AS id, nombre, especialidad, precio_sesion AS precio, disponibilidad
        FROM monitores
    ");
    echo json_encode(['ok' => true, 'monitores' => $st->fetchAll()]);
    exit;
}

// ─── MATERIAL ─────────────────────────────────────────────────
if ($action === 'materiales') {
    $st = $pdo->query("
        SELECT id_material AS id, nombre_material AS nombre,
               precio_alquiler AS precio, stock_total AS stock
        FROM material WHERE stock_total > 0
    ");
    echo json_encode(['ok' => true, 'materiales' => $st->fetchAll()]);
    exit;
}

// ─── PRÓXIMAS RESERVAS DEL USUARIO ───────────────────────────
if ($action === 'reservas') {
    $st = $pdo->prepare("
        SELECT r.id_reserva AS id, p.nombre_pista AS pista,
               p.tipo_deporte AS deporte, r.fecha,
               r.hora_inicio, r.hora_fin, r.estado_pago, r.precio_final,
               m.nombre AS monitor_nombre
        FROM reservas r
        JOIN pistas p ON p.id_pista = r.id_pista
        LEFT JOIN monitores m ON m.id_monitor = r.id_monitor
        WHERE r.id_user = ?
          AND r.estado_pago != 'cancelada'
          AND r.fecha >= CURDATE()
        ORDER BY r.fecha, r.hora_inicio
        LIMIT 10
    ");
    $st->execute([$id_user]);
    echo json_encode(['ok' => true, 'reservas' => $st->fetchAll()]);
    exit;
}

// ─── HISTORIAL COMPLETO ───────────────────────────────────────
if ($action === 'historial') {
    $st = $pdo->prepare("
        SELECT r.id_reserva AS id, p.nombre_pista AS pista,
               p.tipo_deporte AS deporte, r.fecha,
               r.hora_inicio, r.hora_fin, r.estado_pago, r.precio_final,
               m.nombre AS monitor_nombre,
               GROUP_CONCAT(mat.nombre_material ORDER BY mat.nombre_material SEPARATOR ', ') AS materiales
        FROM reservas r
        JOIN pistas p ON p.id_pista = r.id_pista
        LEFT JOIN monitores m ON m.id_monitor = r.id_monitor
        LEFT JOIN reserva_material rm ON rm.id_reserva = r.id_reserva
        LEFT JOIN material mat ON mat.id_material = rm.id_material
        WHERE r.id_user = ?
        GROUP BY r.id_reserva
        ORDER BY r.fecha DESC, r.hora_inicio DESC
    ");
    $st->execute([$id_user]);
    echo json_encode(['ok' => true, 'reservas' => $st->fetchAll()]);
    exit;
}

// ─── RESERVAR ─────────────────────────────────────────────────
if ($action === 'reservar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pista    = (int)  ($_POST['pista_id']    ?? 0);
    $fecha       = trim(   $_POST['fecha']        ?? '');
    $hora_ini    = trim(   $_POST['hora_inicio']  ?? '');
    $hora_fin    = trim(   $_POST['hora_fin']     ?? '');
    $id_monitor  = (int)  ($_POST['monitor_id']  ?? 0);
    $id_material = (int)  ($_POST['material_id'] ?? 0);
    $cantidad    = max(1, (int) ($_POST['cantidad'] ?? 1));

    if (!$id_pista || !$fecha || !$hora_ini || !$hora_fin) {
        echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios.']);
        exit;
    }

    if ($hora_fin <= $hora_ini) {
        echo json_encode(['ok' => false, 'msg' => 'La hora de fin debe ser posterior a la de inicio.']);
        exit;
    }

    if ($hora_ini < '08:00') {
        echo json_encode(['ok' => false, 'msg' => 'Las reservas empiezan a las 08:00.']);
        exit;
    }

    // Obtener pista
    $st = $pdo->prepare("SELECT * FROM pistas WHERE id_pista = ? AND estado = 'disponible'");
    $st->execute([$id_pista]);
    $pista = $st->fetch();
    if (!$pista) {
        echo json_encode(['ok' => false, 'msg' => 'Pista no disponible.']);
        exit;
    }

    // Calcular coste pista
    $horas       = (strtotime($hora_fin) - strtotime($hora_ini)) / 3600;
    $coste_pista = round((float)$pista['precio_hora'] * $horas, 2);

    // Coste monitor — solo si disponibilidad = 1
    $coste_monitor = 0.0;
    $monitor_id_ok = null;
    if ($id_monitor > 0) {
        $st = $pdo->prepare('SELECT precio_sesion FROM monitores WHERE id_monitor = ? AND disponibilidad = 1');
        $st->execute([$id_monitor]);
        $mon = $st->fetchColumn();
        if ($mon !== false) {
            $coste_monitor = (float) $mon;
            $monitor_id_ok = $id_monitor;
        } else {
            echo json_encode(['ok' => false, 'msg' => 'El monitor seleccionado no está disponible.']);
            exit;
        }
    }

    // Coste material
    $coste_material = 0.0;
    $material_id_ok = null;
    if ($id_material > 0) {
        $st = $pdo->prepare('SELECT precio_alquiler, stock_total FROM material WHERE id_material = ?');
        $st->execute([$id_material]);
        $mat = $st->fetch();
        if ($mat && $mat['stock_total'] >= $cantidad) {
            $coste_material = round((float)$mat['precio_alquiler'] * $cantidad, 2);
            $material_id_ok = $id_material;
        } elseif ($mat) {
            echo json_encode(['ok' => false, 'msg' => 'Stock insuficiente para ese material.']);
            exit;
        }
    }

    $coste_total = $coste_pista + $coste_monitor + $coste_material;

    // Verificar saldo
    $st = $pdo->prepare('SELECT saldo FROM usuarios WHERE id_user = ?');
    $st->execute([$id_user]);
    $saldo_actual = (float) $st->fetchColumn();

    if ($saldo_actual < $coste_total) {
        echo json_encode(['ok' => false, 'msg' => 'Saldo insuficiente. Necesitas ' . number_format($coste_total, 2) . ' €.']);
        exit;
    }

    // Verificar solapamiento
    $st = $pdo->prepare("
        SELECT id_reserva FROM reservas
        WHERE id_pista   = ?
          AND fecha      = ?
          AND estado_pago != 'cancelada'
          AND hora_inicio < ?
          AND hora_fin    > ?
    ");
    $st->execute([$id_pista, $fecha, $hora_fin, $hora_ini]);
    if ($st->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'La pista ya está reservada en ese horario.']);
        exit;
    }

    // Crear reserva
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("
            INSERT INTO reservas (id_user, id_pista, id_monitor, fecha, hora_inicio, hora_fin, precio_final, estado_pago)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pagado')
        ");
        $st->execute([$id_user, $id_pista, $monitor_id_ok, $fecha, $hora_ini, $hora_fin, $coste_total]);
        $id_reserva_nueva = (int) $pdo->lastInsertId();

        if ($material_id_ok) {
            $st = $pdo->prepare("
                INSERT INTO reserva_material (id_reserva, id_material, cantidad) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)
            ");
            $st->execute([$id_reserva_nueva, $material_id_ok, $cantidad]);
        }

        $nuevo_saldo = $saldo_actual - $coste_total;
        $st = $pdo->prepare('UPDATE usuarios SET saldo = ? WHERE id_user = ?');
        $st->execute([$nuevo_saldo, $id_user]);

        $pdo->commit();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok'    => true,
            'msg'   => '¡Reserva confirmada! Total: ' . number_format($coste_total, 2) . ' €',
            'saldo' => number_format($nuevo_saldo, 2)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Error interno al realizar la reserva.']);
    }
    exit;
}

// ─── CANCELAR ─────────────────────────────────────────────────
if ($action === 'cancelar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = (int) ($_POST['reserva_id'] ?? 0);

    if (!$id_reserva) {
        echo json_encode(['ok' => false, 'msg' => 'ID de reserva inválido.']);
        exit;
    }

    $st = $pdo->prepare("
        SELECT id_reserva, precio_final, estado_pago
        FROM reservas
        WHERE id_reserva = ? AND id_user = ? AND estado_pago != 'cancelada'
    ");
    $st->execute([$id_reserva, $id_user]);
    $reserva = $st->fetch();

    if (!$reserva) {
        echo json_encode(['ok' => false, 'msg' => 'Reserva no encontrada o ya cancelada.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("UPDATE reservas SET estado_pago = 'cancelada' WHERE id_reserva = ?");
        $st->execute([$id_reserva]);

        $st = $pdo->prepare('UPDATE usuarios SET saldo = saldo + ? WHERE id_user = ?');
        $st->execute([$reserva['precio_final'], $id_user]);

        $pdo->commit();

        $st = $pdo->prepare('SELECT saldo FROM usuarios WHERE id_user = ?');
        $st->execute([$id_user]);
        $nuevo_saldo = (float) $st->fetchColumn();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok'    => true,
            'msg'   => 'Reserva cancelada. Se han devuelto ' . number_format($reserva['precio_final'], 2) . ' € a tu saldo.',
            'saldo' => number_format($nuevo_saldo, 2)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Error al cancelar la reserva.']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida.']);
