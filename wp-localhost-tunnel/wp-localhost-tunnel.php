<?php
/**
 * Plugin Name: WP Localhost Tunnel
 * Description: Expose your local XAMPP-based WordPress site to the internet using your own tunnel server.
 * Version: 1.0
 * Author: Vineet Tiwari
 */

defined('ABSPATH') || exit;

define('WPLT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPLT_CLIENT_PATH', WPLT_PLUGIN_DIR . 'client/tunnel-client.php');
define('WPLT_CLIENT_ID', 'myapp'); // Change if needed
define('WPLT_PUBLIC_URL', 'http://your-vps-ip-or-domain:8080/' . WPLT_CLIENT_ID);

add_action('admin_menu', function () {
    add_management_page(
        'Localhost Tunnel',
        'Localhost Tunnel',
        'manage_options',
        'wp-localhost-tunnel',
        'wplt_render_admin_page'
    );
});

function wplt_render_admin_page() {
    include WPLT_PLUGIN_DIR . 'admin/settings-page.php';
}

add_action('admin_post_wplt_start_tunnel', function () {
    // Run PHP tunnel client in background
    $cmd = 'php ' . escapeshellarg(WPLT_CLIENT_PATH) . ' > /dev/null 2>&1 &';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = 'start /B ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(WPLT_CLIENT_PATH);
    }
    exec($cmd);
    wp_redirect(admin_url('tools.php?page=wp-localhost-tunnel&started=1'));
    exit;
});

add_action('admin_post_wplt_stop_tunnel', function () {
    // Kill PHP tunnel client
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec('taskkill /F /IM php.exe');
    } else {
        exec('pkill -f tunnel-client.php');
    }
    wp_redirect(admin_url('tools.php?page=wp-localhost-tunnel&stopped=1'));
    exit;
});
