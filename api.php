<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autenticado']);
    exit;
}

$id_user = (int) $_SESSION['usuario_id'];
$accion  = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo     = getDB();

//SALDO
if ($accion == 'saldo') {
    $consulta = $pdo->prepare('SELECT saldo 
                               FROM usuarios
                               WHERE id_user = ?'
                             );
    $consulta->execute([$id_user]);
    $fila = $consulta->fetch();
    echo json_encode(['ok' => true, 'saldo' => number_format($fila['saldo'], 2)]);
    exit;
}

//PISTAS
if ($accion == 'pistas') {
    $consulta = $pdo->query("SELECT id_pista AS id, nombre_pista AS nombre, tipo_deporte AS deporte, precio_hora AS precio 
                             FROM pistas 
                             WHERE estado = 'disponible'
                           ");
    echo json_encode(['ok' => true, 'pistas' => $consulta->fetchAll()]);
    exit;
}

//MONITORES
if ($accion == 'monitores') {
    $consulta = $pdo->query("SELECT id_monitor AS id, nombre, especialidad, precio_sesion AS precio, disponibilidad 
                             FROM monitores
                           ");
    echo json_encode(['ok' => true, 'monitores' => $consulta->fetchAll()]);
    exit;
}

//MATERIAL
if ($accion == 'materiales') {
    $consulta = $pdo->query("SELECT id_material AS id, nombre_material AS nombre, precio_alquiler AS precio, stock_total AS stock 
                             FROM material 
                             WHERE stock_total > 0
                           ");
    echo json_encode(['ok' => true, 'materiales' => $consulta->fetchAll()]);
    exit;
}

//RESERVAS
if ($accion == 'reservas') {
    $consulta = $pdo->prepare("SELECT r.id_reserva AS id, p.nombre_pista AS pista, p.tipo_deporte AS deporte, r.fecha, r.hora_inicio, r.hora_fin, r.estado_pago, r.precio_final, r.cancelada, m.nombre AS monitor_nombre 
                               FROM reservas r JOIN pistas p ON p.id_pista = r.id_pista LEFT JOIN monitores m ON m.id_monitor = r.id_monitor 
                               WHERE r.id_user = ? AND r.cancelada = 0 AND r.fecha >= CURDATE() 
                               ORDER BY r.fecha, r.hora_inicio 
                               LIMIT 10
                             ");
    $consulta->execute([$id_user]);
    echo json_encode(['ok' => true, 'reservas' => $consulta->fetchAll()]);
    exit;
}

//HISTORIAL
if ($accion == 'historial') {
    $consulta = $pdo->prepare("SELECT r.id_reserva AS id, p.nombre_pista AS pista, p.tipo_deporte AS deporte, r.fecha, r.hora_inicio, r.hora_fin, r.estado_pago, r.precio_final, m.nombre AS monitor_nombre, GROUP_CONCAT(mat.nombre_material) AS materiales 
                               FROM reservas r JOIN pistas p ON p.id_pista = r.id_pista LEFT JOIN monitores m ON m.id_monitor = r.id_monitor LEFT JOIN reserva_material rm ON rm.id_reserva = r.id_reserva LEFT JOIN material mat ON mat.id_material = rm.id_material 
                               WHERE r.id_user = ? 
                               GROUP BY r.id_reserva 
                               ORDER BY r.fecha DESC, r.hora_inicio DESC
                             ");
    
    $consulta->execute([$id_user]);
    echo json_encode(['ok' => true, 'reservas' => $consulta->fetchAll()]);
    exit;
}

//RESERVAR
if ($accion == 'reservar' && $_SERVER['REQUEST_METHOD'] == 'POST') {

    $id_pista = (int) ($_POST['pista_id'] ?? 0);
    $fecha = trim($_POST['fecha'] ?? '');
    $hora_inicio = trim($_POST['hora_inicio'] ?? '');
    $hora_fin = trim($_POST['hora_fin'] ?? '');
    $id_monitor = (int) ($_POST['monitor_id'] ?? 0);
    $id_material = (int) ($_POST['material_id'] ?? 0);
    $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));

    //VALIDAR
    if (!$id_pista || !$fecha || !$hora_inicio || !$hora_fin) {
        echo json_encode(['ok' => false, 'mensaje' => 'Faltan datos obligatorios.']); exit;
    }
    if ($hora_fin <= $hora_inicio) {
        echo json_encode(['ok' => false, 'mensaje' => 'La hora de fin debe ser posterior a la de inicio.']); exit;
    }
    if ($hora_inicio < '08:00') {
        echo json_encode(['ok' => false, 'mensaje' => 'Las reservas empiezan a las 08:00.']); exit;
    }

    //DISPONIBLIDAD PISTAS
    $consulta = $pdo->prepare("SELECT * 
                               FROM pistas 
                               WHERE id_pista = ? AND estado = 'disponible'
                             ");
    $consulta->execute([$id_pista]);
    $pista = $consulta->fetch();
    if (!$pista) {
        echo json_encode(['ok' => false, 'mensaje' => 'Pista no disponible.']); exit;
    }

    //COSTES
    $horas = (strtotime($hora_fin) - strtotime($hora_inicio)) / 3600;
    $coste_total = round($pista['precio_hora'] * $horas, 2);

    $monitor_id_ok  = null;
    $material_id_ok = null;

    //MONITOR Y MATERIAL
    if ($id_monitor > 0) {
        $consulta = $pdo->prepare('SELECT precio_sesion 
                                   FROM monitores 
                                   WHERE id_monitor = ? AND disponibilidad = 1
                                 ');
        $consulta->execute([$id_monitor]);
        $precio = $consulta->fetchColumn();
        if ($precio === false) {
            echo json_encode(['ok' => false, 'mensaje' => 'El monitor no está disponible.']); exit;
        }
        $coste_total  += (float) $precio;
        $monitor_id_ok = $id_monitor;
    }

    if ($id_material > 0) {
        $consulta = $pdo->prepare('SELECT precio_alquiler, stock_total 
                                   FROM material 
                                   WHERE id_material = ?
                                 ');
        $consulta->execute([$id_material]);
        $mat = $consulta->fetch();
        if (!$mat || $mat['stock_total'] < $cantidad) {
            echo json_encode(['ok' => false, 'mensaje' => 'Stock insuficiente.']); exit;
        }
        $coste_total   += round((float)$mat['precio_alquiler'] * $cantidad, 2);
        $material_id_ok = $id_material;
    }

    //SALDO
    $consulta = $pdo->prepare('SELECT saldo 
                               FROM usuarios 
                               WHERE id_user = ?
                             ');
    $consulta->execute([$id_user]);
    $saldo_actual = (float) $consulta->fetchColumn();
    if ($saldo_actual < $coste_total) {
        echo json_encode(['ok' => false, 'mensaje' => 'Saldo insuficiente. Necesitas ' . number_format($coste_total, 2) . ' €.']); exit;
    }

    //SOLAPACION
    $consulta = $pdo->prepare("SELECT id_reserva 
                               FROM reservas 
                               WHERE id_pista = ? AND fecha = ? AND estado_pago != 'cancelada' AND hora_inicio < ? AND hora_fin > ?
                             ");
    $consulta->execute([$id_pista, $fecha, $hora_fin, $hora_inicio]);
    if ($consulta->fetch()) {
        echo json_encode(['ok' => false, 'mensaje' => 'La pista ya está reservada en ese horario.']); exit;
    }

    //INSERTAR RESERVA
    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("INSERT INTO reservas (id_user, id_pista, id_monitor, fecha, hora_inicio, hora_fin, precio_final, estado_pago) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'pagado')
                                 ");
        $consulta->execute([$id_user, $id_pista, $monitor_id_ok, $fecha, $hora_inicio, $hora_fin, $coste_total]);
        $id_nueva = (int) $pdo->lastInsertId();

        if ($material_id_ok) {
            $consulta = $pdo->prepare("INSERT INTO reserva_material (id_reserva, id_material, cantidad) 
                                       VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)
                                     ");
            $consulta->execute([$id_nueva, $material_id_ok, $cantidad]);
        }

        $nuevo_saldo = $saldo_actual - $coste_total;
        $pdo->prepare('UPDATE usuarios 
                       SET saldo = ? 
                       WHERE id_user = ?
                     ')->execute([$nuevo_saldo, $id_user]);

        $pdo->commit();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok' => true,
            'mensaje' => '¡Reserva confirmada! Total: ' . number_format($coste_total, 2) . ' €',
            'saldo' => number_format($nuevo_saldo, 2),
            'ticket' => [
                'id' => $id_nueva,
                'pista' => $pista['nombre_pista'],
                'deporte' => $pista['tipo_deporte'],
                'fecha' => $fecha,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin,
                'total' => number_format($coste_total, 2),
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'mensaje' => 'Error interno al realizar la reserva.']);
    }
    exit;
}

//CANCELAR
if ($accion == 'cancelar' && $_SERVER['REQUEST_METHOD'] == 'POST') {

    $id_reserva = (int) ($_POST['reserva_id'] ?? 0);
    if (!$id_reserva) {
        echo json_encode(['ok' => false, 'mensaje' => 'ID de reserva inválido.']); exit;
    }

    $consulta = $pdo->prepare("SELECT id_reserva, precio_final 
                               FROM reservas 
                               WHERE id_reserva = ? AND id_user = ? AND cancelada = 0
                             ");
    $consulta->execute([$id_reserva, $id_user]);
    $reserva = $consulta->fetch();
    if (!$reserva) {
        echo json_encode(['ok' => false, 'mensaje' => 'Reserva no encontrada o ya cancelada.']); exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE reservas 
                       SET cancelada = 1, estado_pago = 'cancelada'
                       WHERE id_reserva = ?
                      ")->execute([$id_reserva]);
                     
        $pdo->prepare("UPDATE usuarios 
                       SET saldo = saldo + ? 
                       WHERE id_user = ?
                     ")->execute([$reserva['precio_final'], $id_user]);
        $pdo->commit();

        $consulta2 = $pdo->prepare('SELECT saldo 
                                    FROM usuarios 
                                    WHERE id_user = ?
                                  ');
        $consulta2->execute([$id_user]);
        $nuevo_saldo = (float) $consulta2->fetchColumn();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok' => true, 
            'mensaje' => 'Reserva cancelada. Se han devuelto ' . number_format($reserva['precio_final'], 2) . ' € a tu saldo.', 
            'saldo' => number_format($nuevo_saldo, 2)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'mensaje' => 'Error al cancelar la reserva.']);
    }
    exit;
}

echo json_encode(['ok' => false, 'mensaje' => 'Acción no reconocida.']);