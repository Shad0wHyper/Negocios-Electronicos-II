<?php
// includes/support_helpers.php - Utilidades compartidas para tickets de soporte

if (!function_exists('isAuthenticatedCustomer')) {
    function isAuthenticatedCustomer(): bool
    {
        return isset($_SESSION['user']) && !isStaffRole($_SESSION['user']['role'] ?? 'user');
    }
}

if (!function_exists('getSupportCategories')) {
    function getSupportCategories(): array
    {
        return [
            'Pedidos y Envíos',
            'Pagos y Facturación',
            'Productos y Tallas',
            'Devoluciones y Cambios',
            'Cuenta y Acceso',
            'Otro',
        ];
    }
}

if (!function_exists('generateTicketCode')) {
    function generateTicketCode(): string
    {
        return 'TKT-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    }
}

if (!function_exists('getTicketStatusMeta')) {
    function getTicketStatusMeta(string $status): array
    {
        switch ($status) {
            case 'en_proceso':
                return [
                    'label' => 'En Proceso',
                    'tw_class' => 'bg-blue-100 text-blue-800',
                    'client_class' => 'ticket-badge-progress',
                ];
            case 'cerrado':
                return [
                    'label' => 'Cerrado',
                    'tw_class' => 'bg-emerald-100 text-emerald-800',
                    'client_class' => 'ticket-badge-closed',
                ];
            case 'abierto':
            default:
                return [
                    'label' => 'Abierto',
                    'tw_class' => 'bg-amber-100 text-amber-800',
                    'client_class' => 'ticket-badge-open',
                ];
        }
    }
}

if (!function_exists('getTicketPriorityMeta')) {
    function getTicketPriorityMeta(string $priority): array
    {
        switch ($priority) {
            case 'alta':
                return [
                    'label' => 'Alta',
                    'tw_class' => 'bg-red-100 text-red-700',
                ];
            case 'baja':
                return [
                    'label' => 'Baja',
                    'tw_class' => 'bg-gray-100 text-gray-700',
                ];
            case 'normal':
            default:
                return [
                    'label' => 'Normal',
                    'tw_class' => 'bg-slate-100 text-slate-700',
                ];
        }
    }
}
?>
