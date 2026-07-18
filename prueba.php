<?php
// demo.php
// Demo simple: "Hola Mundo" + formulario de registro con Bootstrap
// Uso: coloca este archivo en un servidor PHP (Apache, Nginx + PHP-FPM).
session_start();

// Ruta simple para almacenar usuarios (solo demo). No usar en producción sin revisar seguridad.
define('USERS_FILE', __DIR__ . '/users.json');

// Helper: cargar usuarios
function load_users() {
    if (!file_exists(USERS_FILE)) return [];
    $json = file_get_contents(USERS_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// Helper: guardar usuario
function save_user($user) {
    $users = load_users();
    $users[] = $user;
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// CSRF simple
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$errors = [];
$success = null;
$old = ['name'=>'','email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = "Token de seguridad inválido. Intenta recargar la página.";
    } else {
        // Recoger y sanitizar
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $old['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $old['email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        // Validaciones básicas
        if ($name === '') $errors[] = "El nombre es obligatorio.";
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido.";
        if (strlen($password) < 6) $errors[] = "La contraseña debe tener al menos 6 caracteres.";
        if ($password !== $confirm) $errors[] = "Las contraseñas no coinciden.";

        // Comprobar email duplicado (demo)
        $users = load_users();
        foreach ($users as $u) {
            if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
                $errors[] = "El email ya está registrado.";
                break;
            }
        }

        if (empty($errors)) {
            // Hash de contraseña (usar password_hash)
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $user = [
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'created_at' => date('c')
            ];

            // Guardar (archivo JSON)
            save_user($user);

            // Mensaje de éxito y limpiar campos
            $success = "Registro completado. ¡Hola, " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "!";
            $old = ['name'=>'','email'=>''];
            // Regenerar token para evitar reenvío
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Demo: Hola Mundo y Registro (PHP + Bootstrap)</title>
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand" href="#">Demo PHP Talendev - Azure Fundamentals</a>
    </div>
  </nav>

  <main class="container py-5">
    <div class="row">
      <div class="col-lg-6">
        <div class="card mb-4">
          <div class="card-body">
            <h1 class="h4">Hola Mundo</h1>
            <p class="mb-0">Esta es una demo simple en PHP con Bootstrap. Muestra un formulario de registro y guarda usuarios en un archivo JSON (solo para demostración).</p>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <h2 class="h5 mb-3">Formulario de registro de alumnos</h2>

            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <?php if ($success): ?>
              <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

              <div class="mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?= $old['name'] ?>">
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= $old['email'] ?>">
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="form-text">Mínimo 6 caracteres.</div>
              </div>

              <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              </div>

              <button type="submit" class="btn btn-primary">Registrar</button>
            </form>
          </div>
        </div>

      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h3 class="h6">Usuarios registrados Talendev-Academy</h3>
            <p class="text-muted small">Lista simple leída desde <code>users.json</code>. No mostrar en producción.</p>
            <ul class="list-group">
              <?php
                $users = load_users();
                if (empty($users)) {
                    echo '<li class="list-group-item">No hay usuarios registrados.</li>';
                } else {
                    foreach ($users as $u) {
                        $display = htmlspecialchars($u['name'] . ' — ' . $u['email'], ENT_QUOTES, 'UTF-8');
                        echo "<li class=\"list-group-item\">{$display}</li>";
                    }
                }
              ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="text-center py-3 text-muted">
    Demo PHP · No usar en producción sin revisar seguridad
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
