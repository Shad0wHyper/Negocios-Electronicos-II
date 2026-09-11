<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$adminId = (string)$_SESSION['user']['id'];
$adminName = $_SESSION['user']['name'] ?? 'Administrador';

$fromDate = isset($_GET['from']) && is_string($_GET['from']) ? $_GET['from'] : '';
$toDate = isset($_GET['to']) && is_string($_GET['to']) ? $_GET['to'] : '';

$datePattern = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($datePattern, $fromDate)) {
    $fromDate = '';
}
if (!preg_match($datePattern, $toDate)) {
    $toDate = '';
}

// Obtener actividades
$interQuery = $db->collection('crm_interactions')->where('admin_id', '=', $adminId);
$interDocs = $interQuery->documents();
$activities = [];

$usersCache = [];

foreach ($interDocs as $doc) {
    if ($doc->exists()) {
        $ci = $doc->data();
        $createdAt = $ci['created_at'] ?? '0';
        
        $match = true;
        if ($fromDate !== '') {
            $fromTs = $fromDate . ' 00:00:00';
            if ($createdAt < $fromTs) {
                $match = false;
            }
        }
        if ($match && $toDate !== '') {
            $toTs = $toDate . ' 23:59:59';
            if ($createdAt > $toTs) {
                $match = false;
            }
        }
        
        if ($match) {
            $userId = $ci['user_id'] ?? '';
            if (!isset($usersCache[$userId])) {
                if ($userId) {
                    $uDoc = $db->collection('users')->document($userId)->snapshot();
                    if ($uDoc->exists()) {
                        $usersCache[$userId] = $uDoc->data();
                    } else {
                        $usersCache[$userId] = null;
                    }
                } else {
                    $usersCache[$userId] = null;
                }
            }
            
            $u = $usersCache[$userId];
            $ci['client_name'] = $u['name'] ?? 'Desconocido';
            $ci['client_email'] = $u['email'] ?? 'Desconocido';
            $activities[] = $ci;
        }
    }
}

usort($activities, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-3xl font-bold text-[var(--text-primary)]">Mi actividad</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    Activo
                </span>
            </div>
            <p class="mt-2 text-sm text-[var(--text-secondary)]">
                Historial de interacciones registradas por <?php echo htmlspecialchars($adminName); ?>.
            </p>
        </div>
        <a href="crm.php" class="inline-flex items-center justify-center rounded-md border border-[var(--border-color)] bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            &larr; Volver al CRM
        </a>
    </div>

    <div class="mb-6 rounded-lg border border-[var(--border-color)] bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div>
                <label for="from" class="mb-1 block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" id="from" name="from" value="<?php echo htmlspecialchars($fromDate); ?>" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-[var(--primary-color)] focus:ring-blue-200">
            </div>
            <div>
                <label for="to" class="mb-1 block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" id="to" name="to" value="<?php echo htmlspecialchars($toDate); ?>" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-[var(--primary-color)] focus:ring-blue-200">
            </div>
            <button type="submit" class="rounded-md bg-[var(--primary-color)] px-5 py-2 text-sm font-semibold text-white hover:bg-[var(--primary-color-hover)]">
                Filtrar
            </button>
            <?php if ($fromDate !== '' || $toDate !== ''): ?>
                <a href="activity.php" class="text-sm font-medium text-gray-500 hover:text-gray-700">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-white shadow-sm">
        <div class="flex items-center justify-between border-b bg-gray-50 p-4">
            <h3 class="text-lg font-semibold text-gray-800">Historial de actividad</h3>
            <span class="text-sm font-medium text-gray-500"><?php echo count($activities); ?> actividades</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">Fecha</th>
                        <th scope="col" class="px-6 py-3">Cliente</th>
                        <th scope="col" class="px-6 py-3">Tipo</th>
                        <th scope="col" class="px-6 py-3">Descripción</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($activities): ?>
                    <?php foreach ($activities as $activity): ?>
                        <tr class="border-b bg-white hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4"><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['client_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($activity['client_email']); ?></p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                                    <?php echo htmlspecialchars($activity['type']); ?>
                                </span>
                            </td>
                            <td class="min-w-[20rem] px-6 py-4 text-gray-700"><?php echo nl2br(htmlspecialchars($activity['description'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">No hay actividades registradas para este periodo.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
