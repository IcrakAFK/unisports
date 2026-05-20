<?php
session_start();
require_once 'config.php';
requireRol('admin');

$db = getDB();
$accion = $_POST['action'] ?? '';

if ($accion === 'toggle_pista' && $id = (int)($_POST['id_pista'] ?? 0)) {
    $estado = in_array($_POST['estado'] ?? '', ['disponible', 'mantenimiento']) ? $_POST['estado'] : 'disponible';
    $db->prepare('UPDATE pistas SET estado = ? WHERE id_pista = ?')->execute([$estado, $id]);
    $_SESSION['admin_mensaje'] = 'Pista actualizada correctamente.';
}

if ($accion === 'cancelar_reserva' && $id = (int)($_POST['id_reserva'] ?? 0)) {
    $consulta = $db->prepare("SELECT id_user, precio_final FROM reservas WHERE id_reserva = ? AND estado_pago != 'cancelada'");
    $consulta->execute([$id]);
    if ($r = $consulta->fetch()) {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE reservas SET estado_pago = 'cancelada' WHERE id_reserva = ?")->execute([$id]);
            $db->prepare('UPDATE usuarios SET saldo = saldo + ? WHERE id_user = ?')->execute([$r['precio_final'], $r['id_user']]);
            $db->commit();
            $_SESSION['admin_mensaje'] = "Reserva #$id cancelada. Reembolso: " . number_format($r['precio_final'], 2) . ' €';
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['admin_mensaje'] = 'Error al cancelar la reserva.';
        }
    }
}

if ($accion === 'cambiar_rol' && $id = (int)($_POST['id_user'] ?? 0)) {
    if (in_array($_POST['nuevo_rol'] ?? '', ['alumno', 'externo', 'entrenador'])) {
        $db->prepare("UPDATE usuarios SET rol = ? WHERE id_user = ? AND rol != 'admin'")->execute([$_POST['nuevo_rol'], $id]);
        $_SESSION['admin_mensaje'] = 'Rol de usuario actualizado.';
    }
}

header('Location: admin.php');
exit;