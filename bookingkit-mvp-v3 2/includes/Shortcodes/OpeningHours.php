<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_Shortcode_OpeningHours {
    public static function render($atts = []) {
        $hours = BKIT_MVP_OpeningHours_Admin::get_hours();
        $names = [1=>__('Monday'),2=>__('Tuesday'),3=>__('Wednesday'),4=>__('Thursday'),5=>__('Friday'),6=>__('Saturday'),7=>__('Sunday')];
        ob_start(); ?>
        <div class="bkit-opening-hours">
            <h3><?php esc_html_e('Opening Hours', 'bookingkit-mvp'); ?></h3>
            <ul>
                <?php foreach ($names as $i=>$label): $r = $hours[$i]; ?>
                    <li><span class="day"><?php echo esc_html($label); ?>:</span>
                        <span class="time"><?php if (!empty($r['closed'])) { esc_html_e('Closed','bookingkit-mvp'); } else { echo esc_html(trim(($r['from'] ?? '').' - '.($r['to'] ?? ''))); } ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php return ob_get_clean();
    }
}
