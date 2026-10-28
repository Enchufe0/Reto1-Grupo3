<?php
session_start();

$page = $_GET['page'] ?? 'login';
$allowed = ['login', 'equipamientos', 'ubicaciones', 'notificaciones', 'usuarios', 'historico'];
if (!in_array($page, $allowed)) { $page = 'login'; }

$isLogged = isset($_SESSION['usuario_id']);
$rol = $_SESSION['rol'] ?? '';

if (!$isLogged && $page !== 'login') { header('Location: index.php?page=login'); exit; }
if ($isLogged && $page === 'login') { header('Location: index.php?page=equipamientos'); exit; }
if (($page === 'usuarios' || $page === 'ubicaciones') && $rol !== 'ADMIN') { header('Location: index.php?page=equipamientos'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reto 2DAW - <?= ucfirst($page) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if ($page === 'login'): ?>
        <link rel="stylesheet" href="view/css/login.css">
    <?php endif; ?>

    <?php if (in_array($page, ['equipamientos', 'usuarios', 'ubicaciones', 'historico'])): ?>
        <link rel="stylesheet" href="view/css/equipamiento.css">
    <?php endif; ?>

    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; background: #fff7d3; color: #111827; }

        <?php if ($isLogged): ?>
            html, body { height: auto; min-height: 100vh; overflow-x: hidden; overflow-y: auto; }
            main { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
            
            .top-navbar { background: #fff2b3; display: flex; justify-content: space-between; align-items: center; height: 84px; padding: 0 40px; border-bottom: 1px solid rgba(0,0,0,0.04); position: sticky; top: 0; z-index: 1000; }
            .nav-tabs { display: flex; gap: 8px; align-items: center; flex: 1; }
            .nav-tabs a { text-decoration: none; color: #4b5563; font-weight: 500; font-size: 14px; padding: 10px 20px; border-radius: 20px; transition: 0.2s; background: #efece3; }
            .nav-tabs a:hover { background: #e5e1d5; }
            .nav-tabs a.active { background: #0b1b42; color: white; }
            
            .nav-logo-center { display: flex; align-items: center; gap: 15px; flex: 0 0 auto; }
            .login-logo-img { width: 45px; height: 45px; object-fit: contain; }
            .login-logo-text { font-size: 18px; font-weight: 500; color: #11225b; letter-spacing: 8px; }
            
            .nav-profile { display: flex; justify-content: flex-end; align-items: center; gap: 20px; flex: 1; }
            .nav-profile .bell { color: #6b7280; font-size: 18px; cursor: pointer; }
            .user-avatar { width: 34px; height: 34px; background: #e0e7ff; color: #1d4ed8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }
            
            .dropdown-container { position: relative; }
            .admin-dropdown { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; color: #374151; background: #efece3; padding: 8px 16px; border-radius: 20px; cursor: pointer; transition: 0.2s; }
            .admin-dropdown:hover { background: #e5e1d5; }
            .dropdown-menu { display: none; position: absolute; top: 110%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 140px; padding: 8px 0; z-index: 1001; }
            .dropdown-menu.show { display: block; }
            .dropdown-item { display: block; padding: 10px 16px; color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; }
            .dropdown-item:hover { background: #fef2f2; }
        <?php else: ?>
            html, body { height: 100vh; overflow: hidden; }
            main { width: 100%; height: 100%; margin: 0; padding: 0; max-width: none; }
        <?php endif; ?>
    </style>

    <script> window.ROL = "<?= $rol ?>"; </script>
</head>
<body>

    <?php if ($isLogged): ?>
    <nav class="top-navbar">
        <div class="nav-tabs">
            <a href="index.php?page=equipamientos" class="<?= $page === 'equipamientos' ? 'active' : '' ?>">Equipamiento</a>
            <?php if ($rol === 'ADMIN'): ?>
                <a href="index.php?page=ubicaciones" class="<?= $page === 'ubicaciones' ? 'active' : '' ?>">Ubicaciones</a>
            <?php endif; ?>
            <a href="index.php?page=historico" class="<?= $page === 'historico' ? 'active' : '' ?>">Historial</a>
            <?php if ($rol === 'ADMIN'): ?>
                <a href="index.php?page=usuarios" class="<?= $page === 'usuarios' ? 'active' : '' ?>">Usuarios</a>
            <?php endif; ?>
        </div>

        <div class="nav-logo-center">
            <img src="img/logo.svg" alt="Logo" class="login-logo-img">
            <div class="login-logo-text">r d e n z a</div>
        </div>

        <div class="nav-profile">
            <i class="fa-regular fa-bell bell"></i>
            <div class="user-avatar">AD</div>
            <div class="dropdown-container">
                <div class="admin-dropdown" onclick="toggleDropdown(event)">
                    Administración <i class="fa-solid fa-chevron-down" style="font-size: 10px; color: #6b7280; margin-left: 5px;"></i>
                </div>
                <div class="dropdown-menu" id="adminMenu">
                    <a class="dropdown-item" onclick="logout()">Salir</a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main>
        <?php require "view/html/{$page}.html"; ?>
    </main>

    <!--JS IS LOADED-->
    <?php if ($page === 'equipamientos'): ?>
        <script src="view/js/Equipamiento.js"></script>
    <?php elseif ($page === 'historico'): ?>
        <script src="view/js/Historico.js"></script>
    <?php elseif ($page === 'login'): ?>
        <script src="view/js/Login.js"></script>
    <?php endif; ?>

    <?php if ($isLogged): ?>
    <script>
        function toggleDropdown(event) {
            event.stopPropagation();
            document.getElementById('adminMenu').classList.toggle('show');
        }
        window.onclick = function(event) {
            const menu = document.getElementById('adminMenu');
            if (menu && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        }
        async function logout() {
            await fetch('api/Login.php', { method: 'DELETE' });
            window.location.href = 'index.php?page=login';
        }
    </script>
    <?php endif; ?>

</body>
</html>