<?php
session_start();
require_once '../config/config.php';
requireRol('admin');

$db = getDB();
$accion = $_POST['action'] ?? '';

// ABRIR/CERRAR PISTA
if ($accion === 'estado_pista' && $id = (int)($_POST['id_pista'] ?? 0)) {
    $estado = in_array($_POST['estado'] ?? '', ['disponible', 'mantenimiento']) ? $_POST['estado'] : 'disponible';
    $db->prepare('UPDATE pistas SET estado = ? WHERE id_pista = ?')->execute([$estado, $id]);
    $_SESSION['admin_mensaje'] = 'Pista actualizada correctamente.';
}

// CANCELAR RESERVAS
if ($accion === 'cancelar_reserva' && $id = (int)($_POST['id_reserva'] ?? 0)) {
    $consulta = $db->prepare("SELECT id_reserva FROM reservas WHERE id_reserva = ? AND estado_pago != 'cancelada'");
    $consulta->execute([$id]);

    if ($consulta->fetch()) {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE reservas SET estado_pago = 'cancelada', cancelada = 1 WHERE id_reserva = ?")->execute([$id]);
            $db->commit();
            $_SESSION['admin_mensaje'] = "Reserva #$id cancelada correctamente.";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['admin_mensaje'] = 'Error al cancelar la reserva.';
        }
    }
}

// CAMBIAR ROL USUARIO
if ($accion === 'cambiar_rol' && $id = (int)($_POST['id_user'] ?? 0)) {
    $nuevo_rol = $_POST['nuevo_rol'] ?? '';

    if (in_array($nuevo_rol, ['alumno', 'externo', 'entrenador'])) {
        $db->prepare("UPDATE usuarios SET rol = ? WHERE id_user = ? AND rol != 'admin'")->execute([$nuevo_rol, $id]);
        $_SESSION['admin_mensaje'] = 'Rol de usuario actualizado.';
    }
}

// REDIRIGIR A ADMIN.PHP
header('Location: ../../public/admin.php');
exit;