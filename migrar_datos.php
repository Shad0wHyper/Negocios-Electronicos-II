<?php
// migrar_datos.php
// IMPORTANTE: Este archivo lee de MySQL y escribe en Firestore.
// Una vez completada la migración, se puede eliminar.

require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

// 1. Conexión a Firebase Firestore
try {
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/firebase_credentials.json');
    $factory = (new Factory)->withServiceAccount(__DIR__ . '/firebase_credentials.json');
    $firestore = $factory->createFirestore();
    $db = $firestore->database();
    echo "Conectado a Firebase correctamente.<br>";
} catch (Exception $e) {
    die("Error de conexión Firebase: " . $e->getMessage());
}

// 2. Conexión a MySQL local antigua
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=xanarchy_bd;charset=utf8",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "Conectado a MySQL local correctamente.<br>";
} catch (PDOException $e) {
    die("Error de conexión BD MySQL: " . $e->getMessage());
}

echo "<h3>Iniciando Migración...</h3>";

// --- MIGRAR USUARIOS ---
echo "Migrando Usuarios...<br>";
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
$usersCollection = $db->collection('users');

foreach ($users as $user) {
    // Usamos el ID de MySQL como ID del documento para mantener consistencia
    $docRef = $usersCollection->document((string)$user['id']);
    $docRef->set([
        'name' => $user['name'],
        'email' => $user['email'],
        'password' => $user['password'],
        'role' => $user['role'],
        'created_at' => $user['created_at'],
        'crm_stage' => $user['crm_stage'],
        'crm_stage_manual' => (bool)$user['crm_stage_manual']
    ]);
}
echo "Usuarios migrados: " . count($users) . "<br>";

// --- MIGRAR PRODUCTOS ---
echo "Migrando Productos...<br>";
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll();
$productsCollection = $db->collection('products');

foreach ($products as $product) {
    $docRef = $productsCollection->document((string)$product['id']);
    $docRef->set([
        'name' => $product['name'],
        'description' => $product['description'],
        'price' => (float)$product['price'],
        'discount_percentage' => (float)$product['discount_percentage'],
        'image' => $product['image'],
        'stock' => (int)$product['stock'],
        'created_at' => $product['created_at']
    ]);
}
echo "Productos migrados: " . count($products) . "<br>";

// --- MIGRAR ORDENES ---
echo "Migrando Órdenes...<br>";
$stmt = $pdo->query("SELECT * FROM orders");
$orders = $stmt->fetchAll();
$ordersCollection = $db->collection('orders');

foreach ($orders as $order) {
    $docRef = $ordersCollection->document((string)$order['id']);
    $docRef->set([
        'user_id' => (string)$order['user_id'],
        'total' => (float)$order['total'],
        'status' => $order['status'],
        'created_at' => $order['created_at']
    ]);
}
echo "Órdenes migradas: " . count($orders) . "<br>";

// --- MIGRAR ITEMS DE ORDENES (Subcolección o Array) ---
echo "Migrando Items de Órdenes...<br>";
$stmt = $pdo->query("SELECT * FROM order_items");
$orderItems = $stmt->fetchAll();
// En NoSQL es mejor guardar los items dentro del documento de la orden.
$ordersWithItems = [];
foreach ($orderItems as $item) {
    $ordersWithItems[$item['order_id']][] = [
        'product_id' => (string)$item['product_id'],
        'quantity' => (int)$item['quantity'],
        'unit_price' => (float)$item['unit_price']
    ];
}

foreach ($ordersWithItems as $orderId => $items) {
    $docRef = $ordersCollection->document((string)$orderId);
    $docRef->set(['items' => $items], ['merge' => true]);
}
echo "Items de Órdenes migrados y anidados en las órdenes.<br>";

// --- MIGRAR DIRECCIONES ---
echo "Migrando Direcciones...<br>";
$stmt = $pdo->query("SELECT * FROM addresses");
$addresses = $stmt->fetchAll();
$addressesCollection = $db->collection('addresses');

foreach ($addresses as $address) {
    $docRef = $addressesCollection->document((string)$address['id']);
    $docRef->set([
        'user_id' => (string)$address['user_id'],
        'first_name' => $address['first_name'],
        'last_name' => $address['last_name'],
        'country' => $address['country'],
        'address' => $address['address'],
        'city' => $address['city'],
        'state' => $address['state'],
        'zip' => $address['zip'],
        'phone' => $address['phone'],
        'notes' => $address['notes'],
        'created_at' => $address['created_at']
    ]);
}
echo "Direcciones migradas: " . count($addresses) . "<br>";

echo "<h3>¡Migración Completada con Éxito!</h3>";
?>
