<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    crearTiquete();
    return;
} elseif ($method === 'GET') {
    listarTiquetesUsuario();
    return;
}

function crearTiquete()
{
    $usuarioId = trim($_POST['usuario_id'] ?? null);
    $monto = trim($_POST['monto'] ?? null);

    // Validación de campos completos
    if ($usuarioId === null || $monto === null) {
        jsonResponse(['error' => 'Los campos usuario id y monto son obligatorios.'], 400);
    }

    if ((!is_float($monto) && !is_int($monto)) || $monto <= 0) {
        jsonResponse(['error' => 'Monto debe ser un número positivo.'], 400);
    }

    $monto = (float) $monto;

    try {
        $pdo = getDB();

        $stmt = $pdo->prepare('SELECT id, saldo FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();

        // Validación de existencia de usuario
        if (!$usuario) {
            jsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        // Validación de saldo suficienta para realizar la transacción
        if ((float) $usuario['saldo'] < $monto) {
            jsonResponse([
                'error' => 'Saldo insuficiente.',
                'saldo_disponible' => (float) $usuario['saldo'],
                'monto_solicitado' => $monto,
            ], 422);
        }

        $pdo->beginTransaction();

        try {
            // Descontar monto del saldo
            $lock = $pdo->prepare(
                'SELECT saldo FROM usuarios WHERE id = ? FOR UPDATE'  // Bloquear fila
            );
            $lock->execute([$usuarioId]);
            $filaActual = $lock->fetch();

            $updateSaldo = $pdo->prepare(
                'UPDATE usuarios SET saldo = saldo - ? WHERE id = ?'
            );
            $updateSaldo->execute([$monto, $usuarioId]);

            $insertTiquete = $pdo->prepare(
                'INSERT INTO tiquetes (usuario_id, monto, estado) VALUES (?, ?, ?)'
            );
            $insertTiquete->execute([$usuarioId, $monto, 'pendiente']);

            $tiqueteId = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Tiquete creado correctamente
        jsonResponse([
            'mensaje' => 'Tiquete creado correctamente.',
            'tiquete' => [
                'id' => $tiqueteId,
                'usuario_id' => $usuarioId,
                'monto' => $monto,
                'estado' => 'pendiente',
            ],
        ], 201);
    } catch (PDOException $e) {
        // Error del servidor
        error_log('Error en crearTiquete: ' . $e->getMessage());
        jsonResponse(['error' => 'Error de base de datos al crear el tiquete.'], 500);
    }
}

function listarTiquetesUsuario()
{
    $usuarioId = trim($_GET['usuario_id'] ?? null);

    if ($id === null || (int) $id <= 0) {
        jsonResponse(['error' => 'El parámetro id debe ser un entero positivo.'], 400);
    }

    $usuarioId = (int) $id;

    try {
        $pdo = getDB();

        $stmtUsuario = $pdo->prepare('SELECT id, nombre, saldo FROM usuarios WHERE id = ? LIMIT 1');
        $stmtUsuario->execute([$usuarioId]);
        $usuario = $stmtUsuario->fetch();

        // Validación de existencia de usuario
        if (!$usuario) {
            jsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        $stmtTiquetes = $pdo->prepare(
            'SELECT id, monto, estado, creado_en
         FROM tiquetes
         WHERE usuario_id = ?
         ORDER BY creado_en DESC'
        );
        $stmtTiquetes->execute([$usuarioId]);
        $tiquetes = $stmtTiquetes->fetchAll();

        //Respuesta exitosa
        jsonResponse([
            'usuario' => [
                'id' => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'saldo' => (float) $usuario['saldo'],
            ],
            'tiquetes' => $tiquetes,
            'total' => count($tiquetes),
        ], 200);

    } catch (PDOException $e) {
        //Error del servidor
        error_log('Error en listarTiquetes: ' . $e->getMessage());
        jsonResponse(['error' => 'Error de base de datos al listar el historial de tiquetes.'], 500);
    }
}

function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
