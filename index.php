<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header('Location: login.php');
  exit();
}

include('conexion.php');

$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$searchQuery = $search ? "WHERE tareas.nombre LIKE ? OR tareas.descripcion LIKE ? OR tareas.codigo LIKE ? OR colaborador.nombres LIKE ? OR colaborador.apellidos LIKE ? OR tareas.estado LIKE ?" : '';

$limit = 10; // Número de tareas por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Preparar la consulta para contar tareas
$stmt = $conn->prepare("SELECT COUNT(tareas.id) AS id FROM tareas 
                        LEFT JOIN colaborador ON tareas.responsable = colaborador.id 
                        $searchQuery");
if ($search) {
  $searchParam = "%$search%";
  $stmt->bind_param('ssssss', $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
}
$stmt->execute();
$result = $stmt->get_result();
$taskCount = $result->fetch_assoc();
$total = $taskCount['id'];
$pages = ceil($total / $limit);

// Preparar la consulta para obtener tareas
$stmt = $conn->prepare("SELECT tareas.id, tareas.nombre, tareas.codigo, CONCAT(colaborador.nombres, ' ', colaborador.apellidos) AS responsable, tareas.estado, tareas.adjunto, tareas.fecha_de_registro
                        FROM tareas
                        LEFT JOIN colaborador ON tareas.responsable = colaborador.id
                        $searchQuery
                        LIMIT ?, ?");
if ($search) {
  $stmt->bind_param('ssssssii', $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $start, $limit);
} else {
  $stmt->bind_param('ii', $start, $limit);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<?php
function getColorClass($estado)
{
  switch (strtolower($estado)) {
    case 'nuevo':
      return 'badge text-white bg-warning';
    case 'en curso':
      return 'badge text-white bg-primary';
    case 'culminado':
      return 'badge text-white bg-success';
    case 'revisado':
      return 'badge text-white bg-secondary';
    default:
      return 'badge text-white bg-secondary';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Document</title>
  <style>
    .modal-dialog {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
  </style>
</head>
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" />
<link href="https://bootswatch.com/5/litera/bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="sidebar.css" />

<body id="body-pd">
  <header class="header" id="header">
    <div class="header_toggle">
      <i class="bx bx-menu" id="header-toggle"></i>
    </div>
    <!-- Search form -->
    <form class="d-flex align-items-center w-100 p-2" method="GET" action="index.php">
      <input class="form-control mb-1=== w-100" id="searchInput" name="search" type="text" placeholder="Buscar tareas..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
    </form>
    <div class="header_img">
      <img src="https://i.imgur.com/hczKIze.jpg" alt="" />

    </div class=header_img>
    <div class="p-2">
      <a href="logout.php"> Hola, <?php echo $_SESSION['usuario']; ?></a>
    </div>

  </header>
  <div class="l-navbar" id="nav-bar">
    <nav class="nav">
      <div>
        <a href="#" class="nav_logo">
          <i class="bx bx-layer nav_logo-icon"></i>
          <span class="nav_logo-name">Mastnac Team</span>
        </a>
        <div class="nav_list">
          <a href="#" class="nav_link active">
            <i class='bx bx-list-ul'></i>
            <span class="nav_name">Tareas</span>
          </a>
          <a href="#" class="nav_link">
            <i class='bx bxs-grid-alt'></i>
            <span class="nav_name">Vista Grid</span>
          </a>
          <a href="#" class="nav_link">
            <i class="bx bx-user nav_icon"></i>
            <span class="nav_name">Usuarios</span>
          </a>
          <a href="#" class="nav_link">
            <i class="bx bx-message-square-detail nav_icon"></i>
            <span class="nav_name">Messages</span>
          </a>
          <a href="#" class="nav_link">
            <i class="bx bx-bookmark nav_icon"></i>
            <span class="nav_name">Bookmark</span>
          </a>
          <a href="#" class="nav_link">
            <i class="bx bx-folder nav_icon"></i>
            <span class="nav_name">Files</span>
          </a>
          <a href="#" class="nav_link">
            <i class="bx bx-bar-chart-alt-2 nav_icon"></i>
            <span class="nav_name">Stats</span>
          </a>
        </div>
      </div>
      <a href="logout.php" class="nav_link">
        <i class="bx bx-log-out nav_icon"></i>
        <span class="nav_name">Cerrar Sesión</span>
      </a>
    </nav>
  </div>
  <!--Container Main start-->
  <div class="p-1">
    <div class="container mt-2">
      <div class="row d-flex align-items-center">
        <h3 class="col-md-10 text-dark d-flex align-items-center"><b>Lista de Tareas</b></h3>
        <a href="create.php" class="btn btn-primary col-md-2 mb-2 p-1">Crear Nueva Tarea</a>
      </div>
      <!-- <form method="GET" action="index.php">
            <input class="form-control mb-3" id="searchInput" name="search" type="text" placeholder="Buscar..." value="<?php echo $search; ?>">
        </form> -->
      <div class="table-responsive">
        <table class="table table-bordered" id="tasksTable">
          <thead class="table-primary">
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Código</th>
              <th>fecha de registro</th>
              <th>Responsable</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $colorClass = getColorClass($row['estado']);
                $adjunto = htmlspecialchars($row['adjunto']);
                echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['nombre']}</td>
                                <td>{$row['codigo']}</td>
                                <td>{$row['fecha_de_registro']}</td>
                                <td>{$row['responsable']}</td>
                                <td><span class='{$colorClass}'>{$row['estado']}</span></td>
                                <td>
                                    <a href='#' class='btn btn-success btn-sm view-btn' data-id='{$row['id']}'><i class='bi bi-eye-fill'></i></a>
                                    <a href='edit.php?id={$row['id']}' class='btn btn-primary btn-sm'><i class='bi bi-pencil-fill'></i></a>
                                    <button class='btn btn-danger btn-sm delete-btn' data-id='{$row['id']}' data-toggle='modal' data-target='#confirmDeleteModal'><i class='bi bi-x-lg'></i></button>
                                    " . (!empty($adjunto) ? "<a href='files/{$adjunto}' class='btn btn-warning btn-sm' target='_blank'><i class='bi bi-file-earmark-text'></i></a>" : "") . "
                                </td>
                              </tr>";
              }
            } else {
              echo "<tr><td colspan='6' class='text-center'>No hay tareas</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <nav>
        <ul class="pagination">
          <li class="page-item <?php if ($page <= 1) {
                                  echo 'disabled';
                                } ?>">
            <a class="page-link" href="<?php if ($page > 1) {
                                          echo "?page=" . ($page - 1) . "&search=" . $search;
                                        } ?>">Anterior</a>
          </li>
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?php if ($page == $i) {
                                    echo 'active';
                                  } ?>">
              <a class="page-link" href="index.php?page=<?= $i; ?>&search=<?= $search; ?>"><?= $i; ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?php if ($page >= $pages) {
                                  echo 'disabled';
                                } ?>">
            <a class="page-link" href="<?php if ($page < $pages) {
                                          echo "?page=" . ($page + 1) . "&search=" . $search;
                                        } ?>">Siguiente</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
  <!--Container Main end-->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
          var id = this.getAttribute('data-id');
          Swal.fire({
            title: '¿Estás seguro?',
            text: "No podrás revertir esto.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = 'delete.php?id=' + id;
            }
          });
        });
      });

      document.querySelectorAll('.view-btn').forEach(function(button) {
        button.addEventListener('click', function(event) {
          event.preventDefault(); // Evita la redirección
          var id = this.getAttribute('data-id');
          fetch('get_task_info.php?id=' + id)
            .then(response => response.json())
            .then(data => {
              if (data.error) {
                Swal.fire({
                  icon: 'error',
                  title: 'Oops...',
                  text: data.error
                });
              } else {
                Swal.fire({
                  title: 'Información de la tarea',
                  html: `
                    <p><strong>Código:</strong> ${data.codigo}</p>
                    <p><strong>Nombre:</strong> ${data.nombre}</p>
                    <p><strong>Descripción:</strong> ${data.descripcion}</p>
                    <p><strong>Fecha de registro:</strong> ${data.fecha_de_registro}</p>
                    <p><strong>Fecha de culminación:</strong> ${data.fecha_culminacion}</p>
                    <p><strong>Fecha de finalización:</strong> ${data.fecha_finalizacion}</p>
                    <p><strong>Responsable:</strong> ${data.responsable}</p>
                    <p><strong>Estado:</strong> ${data.estado}</p>
                    <p><strong>Adjunto:</strong> ${data.adjunto ? `<a href="files/${data.adjunto}" target="_blank">Ver adjunto</a>` : 'Sin adjunto'}</p>
                  `,
                  icon: 'info'
                });
              }
            })
            .catch(error => {
              Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Hubo un error al cargar la información de la tarea.'
              });
            });
        });
      });
    });
  </script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="stilos.js"></script>
</body>

</html>