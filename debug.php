<?php
// Simple script to check for PHP syntax errors in plugin files

// List of files to check
$files = [
    'school-sports-api.php',
    'includes/class-school-sports-api.php',
    'includes/class-school-sports-api-loader.php',
    'includes/class-school-sports-api-i18n.php',
    'includes/class-school-sports-api-activator.php',
    'includes/class-school-sports-api-deactivator.php',
    'includes/class-school-sports-api-cache.php',
    'includes/class-school-sports-api-api.php',
    'includes/class-school-sports-api-shortcodes.php',
    'includes/class-school-sports-api-realtime.php',
    'includes/class-school-sports-api-public.php',
    'admin/class-school-sports-api-admin.php',
    'admin/partials/school-sports-api-admin-display.php',
    'admin/partials/school-sports-api-shortcode-popup.php'
];

echo "Checking for syntax errors in plugin files...\n";

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (!file_exists($fullPath)) {
        echo htmlspecialchars("ERROR: File not found: {$file}\n", ENT_QUOTES, 'UTF-8');
        continue;
    }
    
    // Check for syntax errors
    $output = [];
    $return_var = 0;
    exec("php -l {$fullPath}", $output, $return_var);
    
    if ($return_var !== 0) {
        echo htmlspecialchars("ERROR in {$file}:\n", ENT_QUOTES, 'UTF-8');
        echo htmlspecialchars(implode("\n", $output) . "\n", ENT_QUOTES, 'UTF-8');
    } else {
        echo htmlspecialchars("OK: {$file}\n", ENT_QUOTES, 'UTF-8');
    }
}

echo htmlspecialchars("Syntax check complete.\n", ENT_QUOTES, 'UTF-8');
