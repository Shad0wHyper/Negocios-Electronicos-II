<?php
$json = file_get_contents('ui_map.json');
$map = json_decode($json, true);

$replacements = [];
foreach ($map as $key => $value) {
    // Extract everything starting from "Imagenes/"
    $pos = strpos($key, 'Imagenes/');
    if ($pos !== false) {
        $relativePath = substr($key, $pos);
        $replacements[$relativePath] = $value;
    }
}

// Ensure we also replace URL encoded paths in CSS if any
$replacementsEscaped = [];
foreach ($replacements as $key => $value) {
    // We should also replace rawurlencode versions if any
    $replacementsEscaped[str_replace(' ', '%20', $key)] = $value;
}
$allReplacements = array_merge($replacements, $replacementsEscaped);

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
$filesModified = 0;

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $ext = strtolower($file->getExtension());
        if ($ext === 'php' || $ext === 'css') {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            
            $newContent = strtr($content, $allReplacements);
            // Also replace cases where they might have used "Imagenes\..."
            $newContent = str_replace(array_keys($allReplacements), array_values($allReplacements), $newContent);
            
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Modificado: $path\n";
                $filesModified++;
            }
        }
    }
}

echo "Total de archivos modificados: $filesModified\n";
?>
