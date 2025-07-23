<?php
/**
 * Debug Verbose Script - FOR DEVELOPMENT USE ONLY
 *
 * This file is intended for development and debugging purposes only.
 * It should never be deployed to a production environment.
 *
 * @package School_Sports_API
 */

// phpcs:disable WordPress.PHP.DevelopmentFunctions
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions

// Enable error reporting
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
error_reporting(E_ALL);
// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
ini_set('display_errors', 1);

// Define plugin path
define('PLUGIN_PATH', __DIR__);

echo "Starting plugin debug...\n\n";

// Function to check file existence
function check_file_exists($file_path) {
    $full_path = PLUGIN_PATH . '/' . $file_path;
    if (file_exists($full_path)) {
        echo htmlspecialchars("✓ File exists: {$file_path}\n", ENT_QUOTES, 'UTF-8');
        return true;
    } else {
        echo htmlspecialchars("✗ File MISSING: {$file_path}\n", ENT_QUOTES, 'UTF-8');
        return false;
    }
}

// Function to check PHP syntax
function check_syntax($file_path) {
    $full_path = PLUGIN_PATH . '/' . $file_path;
    if (!file_exists($full_path)) {
        return false;
    }
    
    $output = shell_exec("php -l \"{$full_path}\" 2>&1");
    if (strpos($output, 'No syntax errors detected') !== false) {
        echo htmlspecialchars("✓ Syntax OK: {$file_path}\n", ENT_QUOTES, 'UTF-8');
        return true;
    } else {
        echo htmlspecialchars("✗ Syntax ERROR in {$file_path}:\n", ENT_QUOTES, 'UTF-8');
        echo htmlspecialchars($output . "\n", ENT_QUOTES, 'UTF-8');
        return false;
    }
}

// List of required files
$required_files = [
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

// Check file existence
echo "Checking file existence...\n";
$all_files_exist = true;
foreach ($required_files as $file) {
    if (!check_file_exists($file)) {
        $all_files_exist = false;
    }
}
echo "\n";

// Check syntax if all files exist
if ($all_files_exist) {
    echo "Checking PHP syntax...\n";
    $all_syntax_ok = true;
    foreach ($required_files as $file) {
        if (!check_syntax($file)) {
            $all_syntax_ok = false;
        }
    }
    echo "\n";
} else {
    echo "Skipping syntax check due to missing files.\n\n";
}

// Try to include the main plugin file
echo "Attempting to include main plugin file...\n";
try {
    // Save current error reporting level
    $old_error_level = error_reporting();
    
    // Set error reporting to catch all errors
    error_reporting(E_ALL);
    
    // Start output buffering to capture any errors
    ob_start();
    
    // Include the main plugin file
    include_once PLUGIN_PATH . '/school-sports-api.php';
    
    // Get any output (including errors)
    $output = ob_get_clean();
    
    // Restore error reporting level
    error_reporting($old_error_level);
    
    if (empty($output)) {
        echo htmlspecialchars("✓ Main plugin file included successfully.\n", ENT_QUOTES, 'UTF-8');
    } else {
        echo htmlspecialchars("✗ Errors when including main plugin file:\n", ENT_QUOTES, 'UTF-8');
        echo htmlspecialchars($output . "\n", ENT_QUOTES, 'UTF-8');
    }
} catch (Throwable $e) {
    // Catch any exceptions
    ob_end_clean();
    echo htmlspecialchars("✗ Exception when including main plugin file:\n", ENT_QUOTES, 'UTF-8');
    echo htmlspecialchars($e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n", ENT_QUOTES, 'UTF-8');
}

echo "\nDebug complete.\n";
