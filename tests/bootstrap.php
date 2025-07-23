<?php
// Minimal WordPress function stubs needed for tests
function __($text, $domain = null) {
    return $text;
}
function _e($text, $domain = null) {
    echo $text;
}
function add_filter($hook, $callback, $priority = 10, $args = 1) {}
function remove_filter($hook, $callback, $priority = 10) {}
function add_action($hook, $callback, $priority = 10, $args = 1) {}
function wp_kses($string, $allowed_html, $allowed_protocols = array()) { return $string; }
function sanitize_title($title) { return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($title))); }
function get_option($name) { return array(); }
function set_transient($key, $value, $expiration) { return true; }
function get_transient($key) { return false; }
function delete_transient($key) { return true; }
function wp_strip_all_tags($string) { return strip_tags($string); }
?>
