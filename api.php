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

// --- OBTENER MONITORES ---
if ($action === 'monitores') {
    // Usamos "precio_sesion AS precio" para que JS encuentre "precio"
    $stmt = $pdo->query("SELECT id_monitor AS id, nombre, precio_sesion AS precio FROM monitores WHERE disponibilidad = 1");
    echo json_encode(['ok' => true, 'monitores' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// --- OBTENER MATERIAL ---
if ($action === 'materiales') {
    // OJO: Tu tabla se llama "material", no "materiales"
    // OJO: Tu columna se llama "nombre_material", usamos AS para JS
    $stmt = $pdo->query("SELECT id_material AS id, nombre_material AS nombre, precio_alquiler AS precio FROM material");
    echo json_encode(['ok' => true, 'materiales' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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
    $id_pista    = (int)  ($_POST['pista_id']    ?? 0);
    $fecha       = trim(   $_POST['fecha']        ?? '');
    $hora_ini    = trim(   $_POST['hora_inicio']  ?? '');
    $hora_fin    = trim(   $_POST['hora_fin']     ?? '');
    $id_monitor  = (int)  ($_POST['monitor_id']  ?? 0);
    $id_material = (int)  ($_POST['material_id'] ?? 0);
    $cantidad    = max(1, (int) ($_POST['cantidad'] ?? 1));

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

    // Calcular horas y coste de pista
    $minutos    = (strtotime($hora_fin) - strtotime($hora_ini)) / 60;
    $horas      = $minutos / 60;
    $coste_pista = round((float)$pista['precio_hora'] * $horas, 2);

    // Coste monitor (opcional)
    $coste_monitor = 0.0;
    if ($id_monitor > 0) {
        $stmt = $pdo->prepare('SELECT precio_sesion FROM monitores WHERE id_monitor = ? AND disponibilidad = 1');
        $stmt->execute([$id_monitor]);
        $mon = $stmt->fetchColumn();
        if ($mon !== false) $coste_monitor = (float)$mon;
    }

    // Coste material (opcional)
    $coste_material = 0.0;
    if ($id_material > 0) {
        $stmt = $pdo->prepare('SELECT precio_alquiler FROM material WHERE id_material = ?');
        $stmt->execute([$id_material]);
        $mat = $stmt->fetchColumn();
        if ($mat !== false) $coste_material = round((float)$mat * $cantidad, 2);
    }

    $coste_total = $coste_pista + $coste_monitor + $coste_material;

    // Verificar saldo
    $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id_user = ?');
    $stmt->execute([$id_user]);
    $saldo_actual = (float) $stmt->fetchColumn();

    if ($saldo_actual < $coste_total) {
        echo json_encode(['ok' => false, 'msg' => 'Saldo insuficiente. Necesitas ' . number_format($coste_total, 2) . ' €.']);
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
        $stmt->execute([$id_user, $id_pista, $fecha, $hora_ini, $hora_fin, $coste_total]);
        $id_reserva_nueva = $pdo->lastInsertId();

        // Guardar monitor si se eligió
        if ($id_monitor > 0) {
            $stmt = $pdo->prepare('
                INSERT IGNORE INTO reserva_monitor (id_reserva, id_monitor) VALUES (?, ?)
            ');
            $stmt->execute([$id_reserva_nueva, $id_monitor]);
        }

        // Guardar material si se eligió
        if ($id_material > 0) {
            $stmt = $pdo->prepare('
                INSERT IGNORE INTO reserva_material (id_reserva, id_material, cantidad) VALUES (?, ?, ?)
            ');
            $stmt->execute([$id_reserva_nueva, $id_material, $cantidad]);
        }

        $nuevo_saldo = $saldo_actual - $coste_total;
        $stmt = $pdo->prepare('UPDATE usuarios SET saldo = ? WHERE id_user = ?');
        $stmt->execute([$nuevo_saldo, $id_user]);

        $pdo->commit();
        $_SESSION['usuario_saldo'] = $nuevo_saldo;

        echo json_encode([
            'ok'    => true,
            'msg'   => '¡Reserva realizada con éxito! Total: ' . number_format($coste_total, 2) . ' €',
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