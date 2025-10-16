<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_ClosedDays_Admin {
    public static function register_cpt() {
        $labels = ['name' => __('Closed Days', 'bookingkit-mvp'),'singular_name' => __('Closed Day', 'bookingkit-mvp')];
        register_post_type('bk_closed_day', [
            'labels' => $labels,'public' => false,'show_ui' => true,'menu_icon' => 'dashicons-no-alt','supports' => ['title'],'show_in_menu' => 'bookingkit',
        ]);
        add_filter('manage_bk_closed_day_posts_columns', [__CLASS__, 'cols']);
        add_action('manage_bk_closed_day_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
        add_filter('manage_edit-bk_closed_day_sortable_columns', [__CLASS__, 'sortable']);
        add_action('pre_get_posts', [__CLASS__, 'default_order']);
    }
    public static function cols($cols){
        $new = []; $new['cb']=$cols['cb']; $new['title']=__('Title'); $new['_bk_date']=__('Date','bookingkit-mvp'); $new['_bk_reason']=__('Reason','bookingkit-mvp'); return $new;
    }
    public static function col_content($col, $post_id){
        if ($col === '_bk_date') echo esc_html(get_post_meta($post_id, '_bk_date', true));
        if ($col === '_bk_reason') echo esc_html(get_post_meta($post_id, '_bk_reason', true));
    }
    public static function sortable($cols){ $cols['_bk_date']='_bk_date'; return $cols; }
    public static function default_order($q){
        if (!is_admin() || $q->get('post_type') !== 'bk_closed_day') return;
        if (!$q->get('orderby')){ $q->set('meta_key','_bk_date'); $q->set('orderby','meta_value'); $q->set('order','ASC'); }
        elseif ($q->get('orderby') === '_bk_date'){ $q->set('meta_key','_bk_date'); $q->set('orderby','meta_value'); }
    }
    public static function register_metabox() {
        add_meta_box('bk_closed_day_meta', __('Closed Day Details', 'bookingkit-mvp'), [__CLASS__, 'render_metabox'], 'bk_closed_day', 'normal', 'default');
    }
    public static function render_metabox($post) {
        wp_nonce_field('bk_closed_day_meta', 'bk_closed_day_meta_nonce');
        $date = get_post_meta($post->ID, '_bk_date', true); $reason = get_post_meta($post->ID, '_bk_reason', true);
        ?>
        <p><label for="bk_date"><strong><?php esc_html_e('Date (YYYY-MM-DD)', 'bookingkit-mvp'); ?></strong></label><br/>
        <input type="date" id="bk_date" name="bk_date" value="<?php echo esc_attr($date); ?>" /></p>
        <p><label for="bk_reason"><strong><?php esc_html_e('Reason (optional)', 'bookingkit-mvp'); ?></strong></label><br/>
        <input type="text" id="bk_reason" name="bk_reason" value="<?php echo esc_attr($reason); ?>" class="regular-text" /></p>
        <?php
    }
    public static function save_metabox($post_id) {
        if ( !isset($_POST['bk_closed_day_meta_nonce']) || !wp_verify_nonce($_POST['bk_closed_day_meta_nonce'], 'bk_closed_day_meta')) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return; if ( ! current_user_can('edit_post', $post_id) ) return;
        $date = sanitize_text_field($_POST['bk_date'] ?? ''); $reason = sanitize_text_field($_POST['bk_reason'] ?? '');
        update_post_meta($post_id, '_bk_date', $date); update_post_meta($post_id, '_bk_reason', $reason);
        if ( empty(get_the_title($post_id)) && $date ) { wp_update_post(['ID'=>$post_id, 'post_title'=> sprintf(__('Closed: %s','bookingkit-mvp'), $date)]); }
    }
    public static function is_closed_on($ymd) {
        $q = new WP_Query(['post_type' => 'bk_closed_day','posts_per_page' => -1,'post_status' => 'publish','meta_query' => [[ 'key' => '_bk_date','value' => $ymd,'compare' => '=',]]]);
        $closed = $q->have_posts(); wp_reset_postdata(); return $closed;
    }
}
