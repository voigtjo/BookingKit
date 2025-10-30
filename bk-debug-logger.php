<?php
/**
 * Simple logger to verify WP_DEBUG_LOG path/permissions.
 */
add_action('plugins_loaded', function () {
    error_log('[BK-DEBUG] plugins_loaded fired');
    error_log('[BK-DEBUG] debug target: ' . (defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : 'not defined'));
    $p = WP_CONTENT_DIR . '/debug.log';
    error_log('[BK-DEBUG] is_writable(wp-content): ' . (is_writable(WP_CONTENT_DIR) ? 'yes' : 'no'));
    error_log('[BK-DEBUG] existing debug.log: ' . (file_exists($p) ? 'yes' : 'no') . ' @ ' . $p);
});
