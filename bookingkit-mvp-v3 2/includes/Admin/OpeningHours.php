<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_OpeningHours_Admin {
    public static function register_menu() {
        add_menu_page(__('BookingKit', 'bookingkit-mvp'), __('BookingKit', 'bookingkit-mvp'), 'manage_options', 'bookingkit', [__CLASS__, 'render_opening_hours_page'], 'dashicons-clock', 26);
        add_submenu_page('bookingkit', __('Opening Hours', 'bookingkit-mvp'), __('Opening Hours', 'bookingkit-mvp'), 'manage_options', 'bookingkit', [__CLASS__, 'render_opening_hours_page']);
        add_submenu_page('bookingkit', __('Closed Days', 'bookingkit-mvp'), __('Closed Days', 'bookingkit-mvp'), 'manage_options', 'edit.php?post_type=bk_closed_day');
        add_submenu_page('bookingkit', __('Reservations', 'bookingkit-mvp'), __('Reservations', 'bookingkit-mvp'), 'manage_options', 'edit.php?post_type=bk_reservation');
    }
    public static function default_hours() {
        return [1=>['closed'=>1,'from'=>'','to'=>''],2=>['closed'=>0,'from'=>'16:30','to'=>'22:00'],3=>['closed'=>0,'from'=>'16:30','to'=>'22:00'],4=>['closed'=>0,'from'=>'16:30','to'=>'22:00'],5=>['closed'=>0,'from'=>'16:30','to'=>'22:00'],6=>['closed'=>0,'from'=>'10:00','to'=>'open end'],7=>['closed'=>0,'from'=>'10:00','to'=>'open end']];
    }
    public static function render_opening_hours_page() {
        if ( isset($_POST['bkit_hours_nonce']) && wp_verify_nonce($_POST['bkit_hours_nonce'], 'save_bkit_hours') ) {
            $hours = [];
            for ($d=1;$d<=7;$d++) {
                $hours[$d] = ['closed' => isset($_POST["day{$d}_closed"]) ? 1 : 0,'from' => sanitize_text_field($_POST["day{$d}_from"] ?? ''),'to' => sanitize_text_field($_POST["day{$d}_to"] ?? ''),];
            }
            update_option('bkit_mvp_opening_hours', $hours);
            echo '<div class="updated"><p>'.esc_html__('Saved.', 'bookingkit-mvp').'</p></div>';
        }
        $hours = get_option('bkit_mvp_opening_hours', self::default_hours());
        $days = [1=>__('Monday'),2=>__('Tuesday'),3=>__('Wednesday'),4=>__('Thursday'),5=>__('Friday'),6=>__('Saturday'),7=>__('Sunday')];
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Opening Hours', 'bookingkit-mvp'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('save_bkit_hours','bkit_hours_nonce'); ?>
                <table class="form-table bk-table-hours">
                    <thead><tr><th><?php esc_html_e('Day'); ?></th><th><?php esc_html_e('Closed'); ?></th><th><?php esc_html_e('From'); ?></th><th><?php esc_html_e('To'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($days as $idx=>$label): $row = $hours[$idx] ?? ['closed'=>0,'from'=>'','to'=>'']; ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($label); ?></th>
                            <td><label><input type="checkbox" name="day<?php echo $idx; ?>_closed" <?php checked(1, intval($row['closed'])); ?> /> <?php esc_html_e('Closed'); ?></label></td>
                            <td><input type="text" name="day<?php echo $idx; ?>_from" value="<?php echo esc_attr($row['from']); ?>" class="regular-text" /></td>
                            <td><input type="text" name="day<?php echo $idx; ?>_to" value="<?php echo esc_attr($row['to']); ?>" class="regular-text" /></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save'); ?></button>
                    <a href="<?php echo esc_url(admin_url()); ?>" class="button"><?php esc_html_e('Cancel'); ?></a>
                </p>
            </form>
            <p><?php esc_html_e('Use shortcodes: [bk_opening_hours], [bk_status_today], [bk_calendar]', 'bookingkit-mvp'); ?></p>
        </div>
        <?php
    }
    public static function get_hours() { $hours = get_option('bkit_mvp_opening_hours', self::default_hours()); return is_array($hours) ? $hours : self::default_hours(); }
}
