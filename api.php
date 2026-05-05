<?php
// =============================================
//  UNISPORT BOOKING - api.php
//  Endpoints AJAX:
//    POST action=reservar        → crear reserva
//    POST action=cancelar        → cancelar reserva
//    GET  action=saldo           → devuelve saldo actual
//    GET  action=pistas          → lista pistas disponibles
//    GET  action=reservas        → próximas reservas del usuario
// =============================================
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Protección: debe estar logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo        = getDB();

// ─── GET: SALDO ───────────────────────────────────────────────
if ($action === 'saldo') {
    $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id = ?');
    $stmt->execute([$usuario_id]);
    $row = $stmt->fetch();
    echo json_encode(['ok' => true, 'saldo' => number_format($row['saldo'], 2)]);
    exit;
}

// ─── GET: PISTAS DISPONIBLES ──────────────────────────────────
if ($action === 'pistas') {
    $fecha      = $_GET['fecha'] ?? date('Y-m-d');
    $hora_ini   = $_GET['hora_inicio'] ?? '10:00';
    $hora_fin   = $_GET['hora_fin']    ?? '11:00';

    // Pistas sin reserva activa en ese tramo horario y fecha
    $stmt = $pdo->prepare('
        SELECT p.* FROM pistas p
        WHERE p.activa = 1
          AND p.id NOT IN (
            SELECT r.pista_id FROM reservas r
            WHERE r.fecha   = ?
              AND r.estado  = "activa"
              AND r.hora_inicio < ?
              AND r.hora_fin    > ?
          )
    ');
    $stmt->execute([$fecha, $hora_fin, $hora_ini]);
    echo json_encode(['ok' => true, 'pistas' => $stmt->fetchAll()]);
    exit;
}

// ─── GET: PRÓXIMAS RESERVAS ───────────────────────────────────
if ($action === 'reservas') {
    $stmt = $pdo->prepare('
        SELECT r.id, p.nombre AS pista, p.deporte, r.fecha, r.hora_inicio, r.hora_fin, r.estado
        FROM reservas r
        JOIN pistas p ON p.id = r.pista_id
        WHERE r.usuario_id = ?
          AND r.estado      = "activa"
          AND r.fecha      >= CURDATE()
        ORDER BY r.fecha, r.hora_inicio
        LIMIT 10
    ');
    $stmt->execute([$usuario_id]);
    echo json_encode(['ok' => true, 'reservas' => $stmt->fetchAll()]);
    exit;
}

// ─── POST: RESERVAR ───────────────────────────────────────────
if ($action === 'reservar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pista_id   = (int)   ($_POST['pista_id']    ?? 0);
    $fecha      = trim(    $_POST['fecha']        ?? '');
    $hora_ini   = trim(    $_POST['hora_inicio']  ?? '');
    $hora_fin   = trim(    $_POST['hora_fin']     ?? '');

    if (!$pista_id || !$fecha || !$hora_ini || !$hora_fin) {
        echo json_encode(['ok' => false, 'msg' => 'Faltan datos.']);
        exit;
    }

    // Verificar que la pista existe y está activa
    $stmt = $pdo->prepare('SELECT * FROM pistas WHERE id = ? AND activa = 1');
    $stmt->execute([$pista_id]);
    $pista = $stmt->fetch();
    if (!$pista) {
        echo json_encode(['ok' => false, 'msg' => 'Pista no encontrada.']);
        exit;
    }

    // Verificar saldo suficiente
    $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id = ?');
    $stmt->execute([$usuario_id]);
    $saldo_actual = (float) $stmt->fetchColumn();

    if ($saldo_actual < $pista['precio']) {
        echo json_encode(['ok' => false, 'msg' => 'Saldo insuficiente.']);
        exit;
    }

    // Verificar que no haya solapamiento
    $stmt = $pdo->prepare('
        SELECT id FROM reservas
        WHERE pista_id    = ?
          AND fecha       = ?
          AND estado      = "activa"
          AND hora_inicio < ?
          AND hora_fin    > ?
    ');
    $stmt->execute([$pista_id, $fecha, $hora_fin, $hora_ini]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'La pista ya está reservada en ese horario.']);
        exit;
    }

    // Crear reserva y descontar saldo (transacción)
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            INSERT INTO reservas (usuario_id, pista_id, fecha, hora_inicio, hora_fin)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$usuario_id, $pista_id, $fecha, $hora_ini, $hora_fin]);

        $nuevo_saldo = $saldo_actual - $pista['precio'];
        $stmt = $pdo->prepare('UPDATE usuarios SET saldo = ? WHERE id = ?');
        $stmt->execute([$nuevo_saldo, $usuario_id]);

        $pdo->commit();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok'    => true,
            'msg'   => '¡Reserva realizada con éxito!',
            'saldo' => number_format($nuevo_saldo, 2)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Error al realizar la reserva.']);
    }
    exit;
}

// ─── POST: CANCELAR ───────────────────────────────────────────
if ($action === 'cancelar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reserva_id = (int) ($_POST['reserva_id'] ?? 0);

    if (!$reserva_id) {
        echo json_encode(['ok' => false, 'msg' => 'ID de reserva inválido.']);
        exit;
    }

    // Verificar que la reserva pertenece al usuario y está activa
    $stmt = $pdo->prepare('
        SELECT r.*, p.precio FROM reservas r
        JOIN pistas p ON p.id = r.pista_id
        WHERE r.id = ? AND r.usuario_id = ? AND r.estado = "activa"
    ');
    $stmt->execute([$reserva_id, $usuario_id]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        echo json_encode(['ok' => false, 'msg' => 'Reserva no encontrada.']);
        exit;
    }

    // Cancelar y devolver saldo (transacción)
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE reservas SET estado = "cancelada" WHERE id = ?');
        $stmt->execute([$reserva_id]);

        $stmt = $pdo->prepare('UPDATE usuarios SET saldo = saldo + ? WHERE id = ?');
        $stmt->execute([$reserva['precio'], $usuario_id]);

        $pdo->commit();

        // Leer nuevo saldo
        $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id = ?');
        $stmt->execute([$usuario_id]);
        $nuevo_saldo = (float) $stmt->fetchColumn();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok'    => true,
            'msg'   => 'Reserva cancelada. Saldo devuelto.',
            'saldo' => number_format($nuevo_saldo, 2)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Error al cancelar la reserva.']);
    }
    exit;
}

// Acción no reconocida
http_response_code(400);
echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
