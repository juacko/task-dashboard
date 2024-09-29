<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header('HTTP/1.1 403 Forbidden');
  exit();
}

include('conexion.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
  $stmt = $conn->prepare("SELECT tareas.id, tareas.nombre, tareas.codigo, tareas.descripcion, tareas.fecha_de_registro, tareas.fecha_culminacion, tareas.fecha_finalizacion, colaborador.nombres, colaborador.apellidos, tareas.estado, tareas.adjunto
                            FROM tareas
                            LEFT JOIN colaborador ON tareas.responsable = colaborador.id
                            WHERE tareas.id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Formatear nombres y apellidos
    $row['nombres'] = ucwords(strtolower($row['nombres']));
    $row['apellidos'] = ucwords(strtolower($row['apellidos']));
    $row['responsable'] = $row['nombres'] . ' ' . $row['apellidos'];
    echo json_encode($row);
  } else {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Tarea no encontrada']);
  }
} else {
  header('HTTP/1.1 400 Bad Request');
  echo json_encode(['error' => 'ID de tarea no válido']);
}
