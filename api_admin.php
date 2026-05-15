<?php
session_start();
require_once 'config.php';
requireRol('admin');

$db     = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'toggle_pista') {
    $id_pista = (int) ($_POST['id_pista'] ?? 0);
    $estado   = in_array($_POST['estado'], ['disponible','mantenimiento']) ? $_POST['estado'] : 'disponible';
    if ($id_pista) {
        $st = $db->prepare('UPDATE pistas SET estado = ? WHERE id_pista = ?');
        $st->execute([$estado, $id_pista]);
        $_SESSION['admin_msg'] = 'Pista actualizada correctamente.';
    }
}

if ($action === 'cancelar_reserva') {
    $id_reserva = (int) ($_POST['id_reserva'] ?? 0);
    if ($id_reserva) {
        // Obtener precio_final para reembolsar
        $st = $db->prepare("SELECT id_user, precio_final FROM reservas WHERE id_reserva = ? AND estado_pago != 'cancelada'");
        $st->execute([$id_reserva]);
        $r = $st->fetch();
        if ($r) {
            $db->beginTransaction();
            try {
                $st = $db->prepare("UPDATE reservas SET estado_pago = 'cancelada' WHERE id_reserva = ?");
                $st->execute([$id_reserva]);
                $st = $db->prepare('UPDATE usuarios SET saldo = saldo + ? WHERE id_user = ?');
                $st->execute([$r['precio_final'], $r['id_user']]);
                $db->commit();
                $_SESSION['admin_msg'] = 'Reserva #'.$id_reserva.' cancelada. Reembolso: '.number_format($r['precio_final'],2).' €';
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['admin_msg'] = 'Error al cancelar la reserva.';
            }
        }
    }
}

if ($action === 'cambiar_rol') {
    $id_user   = (int) ($_POST['id_user'] ?? 0);
    $nuevo_rol = $_POST['nuevo_rol'] ?? '';
    $roles_ok  = ['alumno','externo','entrenador'];
    if ($id_user && in_array($nuevo_rol, $roles_ok)) {
        $st = $db->prepare("UPDATE usuarios SET rol = ? WHERE id_user = ? AND rol != 'admin'");
        $st->execute([$nuevo_rol, $id_user]);
        $_SESSION['admin_msg'] = 'Rol de usuario actualizado.';
    }
}

header('Location: admin.php');
exit;
