<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $query = "SELECT * FROM user WHERE username = '$username' AND password = '$password'";
  $result = mysqli_query($conn, $query);

  if (mysqli_num_rows($result) == 1) {
    $_SESSIO['loogedin'] = true;
    header('Location: dashboard.php');
    exit;
  } else {
    $error = "Usuario o contraseña incorrectos";
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>login</title>
  <link rel="stylesheet" href="css/style.css" />
</head>

<body>
  <div class="container">
    <h1>Iniciar Sesion</h1>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Usuario" required /><br /><br />
      <input type="password" name="password" placeholder="Contraseña" required /><br /><br/>
      <button type="submit">Iniciar Sesión</button>
    </form>

  </div>

</body>

</html>