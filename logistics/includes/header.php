<?php
// logistics/includes/header.php
$currentPage = basename($_SERVER['SCRIPT_NAME']);

$userName = isset($_SESSION['user']['name']) && !empty($_SESSION['user']['name']) ? trim($_SESSION['user']['name']) : 'Logística';
$userInitial = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/><title>XANARCHY Logística</title><link rel="preconnect" href="https://fonts.googleapis.com"/><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/><script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
        <div class="p-6 flex items-center gap-3">
            <div class="p-2 bg-[var(--primary-color)] rounded-lg text-white">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7V17L12 22L22 17V7L12 2Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2 7L12 12M22 7L12 12M12 22V12M17 4.5L7 9.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-[var(--text-primary)] leading-tight">Xanarchy</h1>
                <span class="inline-block mt-0.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                    Logística
                </span>
            </div>
        </div>
        <nav class="mt-2 px-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor"><path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.99 8.99a.75.75 0 1 1-1.06 1.06l-1.46-1.46V20a1.5 1.5 0 0 1-1.5 1.5H15a.75.75 0 0 1-.75-.75V16.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75v4.25c0 .414-.336.75-.75.75H6.5A1.5 1.5 0 0 1 5 20v-7.57l-1.46 1.46a.75.75 0 0 1-1.06-1.06l8.99-8.99Z"/></svg> Dashboard</a>
            <a href="products.php" class="sidebar-link <?php echo (in_array($currentPage, ['products.php', 'add_product.php', 'edit_product.php'], true)) ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2V4zm0 6a2 2 0 012-2h6a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2v-2z"></path></svg> Productos</a>
            <a href="inventory.php" class="sidebar-link <?php echo ($currentPage === 'inventory.php') ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg> Inventario</a>
            <a href="movements.php" class="sidebar-link <?php echo (in_array($currentPage, ['movements.php', 'add_movement.php'], true)) ? 'active' : ''; ?>"><svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg> Movimientos</a>
        </nav>
    </aside>
    <main class="flex-1">
        <header class="flex items-center justify-end whitespace-nowrap border-b border-[var(--border-color)] px-8 py-4 bg-white">
            <div class="flex items-center gap-4">
                <a href="inventory.php" class="text-gray-500 hover:text-gray-700" title="Alertas de Inventario">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </a>

                <div class="relative">
                    <button id="avatarButton" type="button" class="flex items-center justify-center size-10 rounded-full bg-[var(--primary-color)] text-white font-bold text-lg">
                        <?php echo $userInitial; ?>
                    </button>
                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border">
                        <div class="px-4 py-2 text-sm text-gray-700 border-b">
                            <p class="font-semibold">Hola, <?php echo htmlspecialchars($userName); ?></p>
                            <p class="text-xs text-gray-500 mt-0.5">Rol: Logística</p>
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
                        event.stopPropagation();
                        dropdownMenu.classList.toggle('hidden');
                    });

                    window.addEventListener('click', function(event) {
                        if (!dropdownMenu.classList.contains('hidden') && !dropdownMenu.contains(event.target)) {
                            dropdownMenu.classList.add('hidden');
                        }
                    });
                }
            });
        </script>

        <div class="p-8">

