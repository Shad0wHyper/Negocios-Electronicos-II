<?php
// Define la página actual para el enlace activo
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// Obtener la información del usuario logueado dinámicamente
$userName = isset($_SESSION['user']['name']) && !empty($_SESSION['user']['name']) ? trim($_SESSION['user']['name']) : 'Admin';
$userInitial = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/><title>XANARCHY Admin</title><link rel="preconnect" href="https://fonts.googleapis.com"/><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/><script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style type="text/tailwindcss">
        :root {--primary-color: #3d98f4;--primary-color-hover: #2b7ed6;--background-color: #f8fafc;--sidebar-bg: #ffffff;--text-primary: #1e293b;--text-secondary: #64748b;--border-color: #e2e8f0;}
        body { font-family: 'Inter', sans-serif; background-color: var(--background-color); }
        .sidebar-link { @apply flex items-center px-4 py-3 text-gray-700 rounded-md hover:bg-gray-100 hover:text-[var(--primary-color)] transition-colors duration-200; }
        .sidebar-link.active { @apply bg-blue-50 text-[var(--primary-color)] font-semibold; }
    </style>
</head>
<body class="text-[var(--text-primary)]">
<div class="relative flex min-h-screen">
    <aside class="w-64 bg-[var(--sidebar-bg)] border-r border-[var(--border-color)] flex-shrink-0">
        <div class="p-6 flex items-center gap-3"><div class="p-2 bg-[var(--primary-color)] rounded-lg text-white"><svg class="size-6" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7V17L12 22L22 17V7L12 2Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2 7L12 12M22 7L12 12M12 22V12M17 4.5L7 9.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg></div><h1 class="text-xl font-bold text-[var(--text-primary)]">Xanarchy</h1></div>
        <nav class="mt-6 px-4 space-y-2"><a href="dashboard.php" class="sidebar-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor"><path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.99 8.99a.75.75 0 1 1-1.06 1.06l-1.46-1.46V20a1.5 1.5 0 0 1-1.5 1.5H15a.75.75 0 0 1-.75-.75V16.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75v4.25c0 .414-.336.75-.75.75H6.5A1.5 1.5 0 0 1 5 20v-7.57l-1.46 1.46a.75.75 0 0 1-1.06-1.06l8.99-8.99Z"/></svg> Dashboard</a><a href="crm.php" class="sidebar-link <?php echo ($currentPage == 'crm.php' || $currentPage == 'crm_client.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg> CRM</a><a href="activity.php" class="sidebar-link <?php echo ($currentPage == 'activity.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg> Mi actividad</a><a href="orders.php" class="sidebar-link <?php echo ($currentPage == 'orders.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path clip-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" fill-rule="evenodd"></path></svg> Orders</a><a href="users.php" class="sidebar-link <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" fill-rule="evenodd"></path></svg> Users</a><a href="products.php" class="sidebar-link <?php echo ($currentPage == 'products.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2V4zm0 6a2 2 0 012-2h6a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2v-2z"></path></svg> Products</a></nav>
    </aside>
    <main class="flex-1">
        <header class="flex items-center justify-end whitespace-nowrap border-b border-[var(--border-color)] px-8 py-4 bg-white">
            <div class="flex items-center gap-4">
                <a href="reports.php" class="text-sm font-semibold text-gray-500 hover:text-[var(--primary-color)]">
                    Reportes
                </a>
                <a href="orders.php" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </a>

                <div class="relative">
                    <button id="avatarButton" type="button" class="flex items-center justify-center size-10 rounded-full bg-[var(--primary-color)] text-white font-bold text-lg">
                        <?php echo $userInitial; ?>
                    </button>
                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border">
                        <div class="px-4 py-2 text-sm text-gray-700 border-b">
                            <p class="font-semibold">Hola,</p>
                            <p class="truncate"><?php echo htmlspecialchars($userName); ?></p>
                        </div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Cerrar Sesión</a>
                    </div>
                </div>

            </div>
        </header>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const avatarButton = document.getElementById('avatarButton');
                const dropdownMenu = document.getElementById('dropdownMenu');

                if (avatarButton && dropdownMenu) {
                    avatarButton.addEventListener('click', function(event) {
                        // Evita que el clic en el botón cierre el menú inmediatamente
                        event.stopPropagation();
                        // Muestra u oculta el menú
                        dropdownMenu.classList.toggle('hidden');
                    });

                    // Cierra el menú si se hace clic fuera de él
                    window.addEventListener('click', function(event) {
                        if (!dropdownMenu.classList.contains('hidden') && !dropdownMenu.contains(event.target)) {
                            dropdownMenu.classList.add('hidden');
                        }
                    });
                }
            });
        </script>

        <div class="p-8">