<?php
require_once 'includes/config.php';

$baseDir = __DIR__ . '/Imagenes';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));

$map = [];

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $path = $file->getPathname();
        // Convert paths to standard format (forward slashes)
        $relativePath = str_replace('\\', '/', str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path));
        
        // Skip product images to save space/time, we only want UI assets
        if (strpos($relativePath, 'Productos') !== false) continue;
        if (strpos(basename($relativePath), 'prod_') === 0) continue;
        if (strpos(strtolower(basename($relativePath)), 'playera') === 0) continue;
        
        $objectName = 'ui-assets/' . basename($relativePath);
        echo "Subiendo $relativePath a Storage...\n";
        
        try {
            $bucket->upload(
                fopen($path, 'r'),
                ['name' => $objectName]
            );
            
            $publicUrl = sprintf(
                'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media',
                $bucket->name(),
                rawurlencode($objectName)
            );
            
            // Map original relative path to the new URL
            // Original references use "Imagenes/...", so we map that exact string
            $map[$relativePath] = $publicUrl;
            echo "Éxito: $publicUrl\n";
        } catch (Exception $e) {
            echo "Error al subir $relativePath: " . $e->getMessage() . "\n";
        }
    }
}

file_put_contents('ui_map.json', json_encode($map, JSON_PRETTY_PRINT));
echo "¡Migración completada! Mapa guardado en ui_map.json\n";
?>
