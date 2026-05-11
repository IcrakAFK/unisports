<?php
// =============================================
//  UNISPORT BOOKING - api.php
//  Endpoints:
//    GET  action=saldo
//    GET  action=pistas
//    GET  action=reservas
//    POST action=reservar
//    POST action=cancelar
// =============================================
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
    $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id_user = ?');
    $stmt->execute([$id_user]);
    $fila = $stmt->fetch();
    echo json_encode(['ok' => true, 'saldo' => number_format($fila['saldo'], 2)]);
    exit;
}

// ─── PISTAS DISPONIBLES ───────────────────────────────────────
// api.php
if ($action === 'pistas') {
    // Hemos quitado "descripcion" de la lista de campos
    $stmt = $pdo->prepare("
        SELECT 
            id_pista AS id, 
            nombre_pista AS nombre, 
            tipo_deporte AS deporte, 
            precio_hora AS precio 
        FROM pistas 
        WHERE estado = 'disponible'
    ");
    $stmt->execute();
    echo json_encode(['ok' => true, 'pistas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ─── PRÓXIMAS RESERVAS DEL USUARIO ───────────────────────────
if ($action === 'reservas') {
    $stmt = $pdo->prepare('
        SELECT 
            r.id_reserva AS id, 
            p.nombre_pista AS pista, 
            p.tipo_deporte AS deporte,
            r.fecha, 
            r.hora_inicio, 
            r.hora_fin, 
            r.estado_pago
        FROM reservas r
        JOIN pistas p ON p.id_pista = r.id_pista
        WHERE r.id_user     = ?
          AND r.estado_pago != "cancelada"
          AND r.fecha       >= CURDATE()
        ORDER BY r.fecha, r.hora_inicio
        LIMIT 10
    ');
    $stmt->execute([$id_user]);
    echo json_encode(['ok' => true, 'reservas' => $stmt->fetchAll()]);
    exit;
}

// ─── RESERVAR ─────────────────────────────────────────────────
if ($action === 'reservar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pista   = (int)  ($_POST['pista_id']    ?? 0);
    $fecha      = trim(   $_POST['fecha']        ?? '');
    $hora_ini   = trim(   $_POST['hora_inicio']  ?? '');
    $hora_fin   = trim(   $_POST['hora_fin']     ?? '');

    if (!$id_pista || !$fecha || !$hora_ini || !$hora_fin) {
        echo json_encode(['ok' => false, 'msg' => 'Faltan datos.']);
        exit;
    }

    // Obtener pista
    $stmt = $pdo->prepare("SELECT * FROM pistas WHERE id_pista = ? AND estado = 'disponible'");
    $stmt->execute([$id_pista]);
    $pista = $stmt->fetch();
    if (!$pista) {
        echo json_encode(['ok' => false, 'msg' => 'Pista no disponible.']);
        exit;
    }

    // Verificar saldo
    $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id_user = ?');
    $stmt->execute([$id_user]);
    $saldo_actual = (float) $stmt->fetchColumn();

    if ($saldo_actual < $pista['precio_hora']) {
        echo json_encode(['ok' => false, 'msg' => 'Saldo insuficiente.']);
        exit;
    }

    // Verificar solapamiento de horario
    $stmt = $pdo->prepare('
        SELECT id_reserva FROM reservas
        WHERE id_pista    = ?
          AND fecha       = ?
          AND estado_pago != "cancelada"
          AND hora_inicio  < ?
          AND hora_fin     > ?
    ');
    $stmt->execute([$id_pista, $fecha, $hora_fin, $hora_ini]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'La pista ya está reservada en ese horario.']);
        exit;
    }

    // Crear reserva y descontar saldo
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            INSERT INTO reservas (id_user, id_pista, fecha, hora_inicio, hora_fin, precio_final, estado_pago)
            VALUES (?, ?, ?, ?, ?, ?, "pagado")
        ');
        $stmt->execute([$id_user, $id_pista, $fecha, $hora_ini, $hora_fin, $pista['precio_hora']]);

        $nuevo_saldo = $saldo_actual - $pista['precio_hora'];
        $stmt = $pdo->prepare('UPDATE usuarios SET saldo = ? WHERE id_user = ?');
        $stmt->execute([$nuevo_saldo, $id_user]);

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

// ─── CANCELAR ─────────────────────────────────────────────────
if ($action === 'cancelar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = (int) ($_POST['reserva_id'] ?? 0);

    if (!$id_reserva) {
        echo json_encode(['ok' => false, 'msg' => 'ID de reserva inválido.']);
        exit;
    }

    // Verificar que la reserva pertenece al usuario
    $stmt = $pdo->prepare('
        SELECT r.*, p.precio_hora FROM reservas r
        JOIN pistas p ON p.id_pista = r.id_pista
        WHERE r.id_reserva = ? AND r.id_user = ? AND r.estado_pago != "cancelada"
    ');
    $stmt->execute([$id_reserva, $id_user]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        echo json_encode(['ok' => false, 'msg' => 'Reserva no encontrada.']);
        exit;
    }

    // Cancelar y devolver saldo
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE reservas SET estado_pago = "cancelada" WHERE id_reserva = ?');
        $stmt->execute([$id_reserva]);

        $stmt = $pdo->prepare('UPDATE usuarios SET saldo = saldo + ? WHERE id_user = ?');
        $stmt->execute([$reserva['precio_hora'], $id_user]);

        $pdo->commit();

        $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id_user = ?');
        $stmt->execute([$id_user]);
        $nuevo_saldo = (float) $stmt->fetchColumn();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok'    => true,
            'msg'   => 'Reserva cancelada. Saldo devuelto.',
            'saldo' => number_format($nuevo_saldo, 2)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Error al cancelar.']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);