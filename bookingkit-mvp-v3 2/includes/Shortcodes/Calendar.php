<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_Shortcode_Calendar {
    public static function render($atts = []) {
        $atts = shortcode_atts(['month' => '','show_legend' => '1','max_width' => '380px'], $atts, 'bk_calendar');
        $tz = new DateTimeZone(wp_timezone_string());
        $req_month = isset($_GET['bk_month']) ? sanitize_text_field($_GET['bk_month']) : '';
        if (!empty($req_month)) { $atts['month'] = $req_month; }
        $d = empty($atts['month']) ? new DateTime('first day of this month', $tz) : DateTime::createFromFormat('Y-m', $atts['month'], $tz);
        if (!$d) $d = new DateTime('first day of this month', $tz);
        $year = intval($d->format('Y')); $month = intval($d->format('n')); $firstDow = intval($d->format('N')); $daysInMonth = intval($d->format('t'));
        $hours = BKIT_MVP_OpeningHours_Admin::get_hours(); $cells = [];
        $today = (new DateTime('now', $tz))->format('Y-m-d');
        for ($day=1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dow  = intval((new DateTime($date, $tz))->format('N'));
            $closed_by_rule = !empty($hours[$dow]['closed']); $closed_by_event = BKIT_MVP_ClosedDays_Admin::is_closed_on($date);
            $state = ($closed_by_rule || $closed_by_event) ? 'closed' : 'open';
            $past = ($date < $today);
            $cells[] = ['day'=>$day, 'state'=>$state, 'date'=>$date, 'past'=>$past];
        }
        ob_start(); ?>
        <div class="bkit-calendar" style="max-width: <?php echo esc_attr($atts['max_width']); ?>">
            <?php $curFirst = new DateTime('first day of this month', $tz); $curFirst->setTime(0,0,0);
            $next = (clone $d)->modify('+1 month'); $prev = (clone $d)->modify('-1 month');
            $prev_allowed = $prev >= $curFirst; $prev_q = esc_url(add_query_arg(['bk_month'=>$prev->format('Y-m')])); $next_q = esc_url(add_query_arg(['bk_month'=>$next->format('Y-m')])); ?>
            <div class="bkit-cal-head"><a class="bkit-nav prev<?php echo $prev_allowed?'':' disabled'; ?>" href="<?php echo $prev_allowed ? $prev_q : '#'; ?>" aria-label="<?php esc_attr_e('Previous month'); ?>">‹</a><span class="bkit-cal-title"><?php echo esc_html(date_i18n('F Y', $d->getTimestamp())); ?></span><a class="bkit-nav next" href="<?php echo $next_q; ?>" aria-label="<?php esc_attr_e('Next month'); ?>">›</a></div>
            <div class="bkit-grid" data-bk-cal>
                <?php $weekdays = [__('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun')];
                foreach ($weekdays as $wd) { echo '<div class="bkit-cell bkit-wd">'.esc_html($wd).'</div>'; }
                for ($i=1; $i<$firstDow; $i++) echo '<div class="bkit-cell bkit-empty"></div>';
                foreach ($cells as $c) {
                    $classes = 'bkit-cell day ' . ($c['past'] ? 'past' : $c['state']) . ((!$c['past'] && $c['state']==='open') ? ' clickable' : ' disabled');
                    printf('<button class="%s" data-date="%s" type="button"><span class="num">%d</span></button>', esc_attr($classes), esc_attr($c['date']), intval($c['day']));
                } ?>
            </div>
            <?php if ($atts['show_legend'] === '1'): ?>
                <div class="bkit-legend"><span class="legend open"><?php esc_html_e('Open', 'bookingkit-mvp'); ?></span><span class="legend closed"><?php esc_html_e('Closed', 'bookingkit-mvp'); ?></span></div>
            <?php endif; ?>
            <div class="bkit-modal" style="display:none;"><div class="bkit-modal-box">
                <div class="bkit-modal-head"><span class="title"><?php esc_html_e('Reservation request', 'bookingkit-mvp'); ?></span>
                <button class="bkit-close" type="button" aria-label="<?php esc_attr_e('Close'); ?>">×</button></div>
                <form class="bkit-res-form">
                    <input type="hidden" name="date" value="">
                    <div class="row"><label><?php esc_html_e('Date'); ?></label><input type="text" name="date_view" value="" readonly></div>
                    <div class="row-2"><div><label><?php esc_html_e('Time'); ?></label><input type="time" name="time"></div>
                    <div><label><?php esc_html_e('Persons'); ?></label><input type="number" name="persons" min="1" max="20" value="2"></div></div>
                    <div class="row-2"><div><label><?php esc_html_e('Name'); ?></label><input type="text" name="name" required></div>
                    <div><label><?php esc_html_e('Phone'); ?></label><input type="tel" name="phone"></div></div>
                    <div class="row"><label><?php esc_html_e('Email'); ?></label><input type="email" name="email" required></div>
                    <div class="row"><label><?php esc_html_e('Message'); ?></label><textarea name="message" rows="3"></textarea></div>
                    <div class="actions"><button type="submit" class="button button-primary"><?php esc_html_e('Send request'); ?></button>
                    <button type="button" class="button bkit-cancel"><?php esc_html_e('Cancel'); ?></button></div>
                </form>
                <div class="bkit-modal-foot bkit-feedback" style="display:none;"></div>
            </div></div>
        </div>
        <?php return ob_get_clean();
    }
}
