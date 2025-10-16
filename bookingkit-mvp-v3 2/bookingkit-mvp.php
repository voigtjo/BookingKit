<?php
/**
 * Plugin Name: BookingKit MVP
 * Description: Reservations helper with opening hours, closed days, clickable calendar + request modal.
 * Version: 0.3.0
 * Author: Jörn / ChatGPT
 * Text Domain: bookingkit-mvp
 */
if ( ! defined( 'ABSPATH' ) ) exit;
define( 'BKIT_MVP_PATH', plugin_dir_path( __FILE__ ) );
define( 'BKIT_MVP_URL', plugin_dir_url( __FILE__ ) );
require_once BKIT_MVP_PATH . 'includes/CPT/Reservation.php';
require_once BKIT_MVP_PATH . 'includes/Admin/OpeningHours.php';
require_once BKIT_MVP_PATH . 'includes/Admin/ClosedDays.php';
require_once BKIT_MVP_PATH . 'includes/Shortcodes/Calendar.php';
require_once BKIT_MVP_PATH . 'includes/Shortcodes/OpeningHours.php';
require_once BKIT_MVP_PATH . 'includes/Shortcodes/StatusToday.php';
class BookingKit_MVP {
    public function __construct() {
        add_action('init', ['BKIT_MVP_Reservation', 'register_cpt']);
        add_action('init', ['BKIT_MVP_ClosedDays_Admin', 'register_cpt']);
        add_action('admin_menu', ['BKIT_MVP_OpeningHours_Admin', 'register_menu']);
        add_action('add_meta_boxes', ['BKIT_MVP_ClosedDays_Admin', 'register_metabox']);
        add_action('save_post_bk_closed_day', ['BKIT_MVP_ClosedDays_Admin', 'save_metabox']);
        add_action('add_meta_boxes', ['BKIT_MVP_Reservation', 'register_metabox']);
        add_action('save_post_bk_reservation', ['BKIT_MVP_Reservation', 'save_metabox']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_shortcode('bk_calendar', ['BKIT_MVP_Shortcode_Calendar', 'render']);
        add_shortcode('bk_opening_hours', ['BKIT_MVP_Shortcode_OpeningHours', 'render']);
        add_shortcode('bk_status_today', ['BKIT_MVP_Shortcode_StatusToday', 'render']);
        add_action('wp_ajax_bkit_mvp_submit_res', [$this, 'ajax_submit_res']);
        add_action('wp_ajax_nopriv_bkit_mvp_submit_res', [$this, 'ajax_submit_res']);
    }
    public function enqueue_assets() {
        wp_enqueue_style('bookingkit-mvp', BKIT_MVP_URL . 'assets/css/bookingkit.css', [], '0.3.0');
        wp_enqueue_script('bookingkit-mvp', BKIT_MVP_URL . 'assets/js/bookingkit.js', ['jquery'], '0.3.0', true);
        wp_localize_script('bookingkit-mvp', 'BKIT_MVP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('bkit_mvp_nonce'),
        ]);
    }
    public function enqueue_admin_assets($hook) {
        if ( strpos($hook, 'bookingkit') !== false || in_array(get_post_type(), ['bk_closed_day','bk_reservation'], true) ) {
            wp_enqueue_style('bookingkit-mvp', BKIT_MVP_URL . 'assets/css/bookingkit.css', [], '0.3.0');
        }
    }
    public static function activate() {
        BKIT_MVP_Reservation::register_cpt();
        BKIT_MVP_ClosedDays_Admin::register_cpt();
        flush_rewrite_rules();
    }
    public static function uninstall() {
        delete_option('bkit_mvp_opening_hours');
    }
    public function ajax_submit_res() {
        check_ajax_referer('bkit_mvp_nonce', 'nonce');
        $date = sanitize_text_field($_POST['date'] ?? '');
        $time = sanitize_text_field($_POST['time'] ?? '');
        $persons = intval($_POST['persons'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        if ( empty($date) || empty($name) || empty($email) ) {
            wp_send_json_error(['msg'=>__('Please fill required fields', 'bookingkit-mvp')], 400);
        }
        $post_id = wp_insert_post([
            'post_type' => 'bk_reservation',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Reservation request %s – %s', 'bookingkit-mvp'), $date, $name),
            'post_content' => $message,
        ], true);
        if ( is_wp_error($post_id) ) {
            wp_send_json_error(['msg'=>$post_id->get_error_message()], 500);
        }
        update_post_meta($post_id, '_bk_date', $date);
        update_post_meta($post_id, '_bk_time', $time);
        update_post_meta($post_id, '_bk_persons', $persons);
        update_post_meta($post_id, '_bk_name', $name);
        update_post_meta($post_id, '_bk_phone', $phone);
        update_post_meta($post_id, '_bk_email', $email);
        wp_send_json_success(['msg'=>__('Request received', 'bookingkit-mvp')]);
    }
}
new BookingKit_MVP();
register_activation_hook(__FILE__, ['BookingKit_MVP', 'activate']);
register_uninstall_hook(__FILE__, ['BookingKit_MVP', 'uninstall']);
