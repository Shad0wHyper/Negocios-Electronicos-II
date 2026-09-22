<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $supplierId = trim($_GET['id']);
    try {
        $db->collection('scm_suppliers')->document($supplierId)->delete();
    } catch (Exception $e) {
        // Log error
    }
}
header('Location: scm_suppliers.php');
exit;
