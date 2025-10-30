<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_Shortcode_Calendar {

    public static function render($atts = []) {

        $atts = shortcode_atts([
            'month'       => '',
            'show_legend' => '1',
            'max_width'   => '380px',
        ], $atts, 'bk_calendar');

        $tz = new DateTimeZone( wp_timezone_string() );

        $req_month = isset($_GET['bk_month']) ? sanitize_text_field($_GET['bk_month']) : '';
        if (!empty($req_month)) {
            $atts['month'] = $req_month;
        }

        $d = empty($atts['month'])
            ? new DateTime('first day of this month', $tz)
            : DateTime::createFromFormat('Y-m', $atts['month'], $tz);

        if (!$d) {
            $d = new DateTime('first day of this month', $tz);
        }

        $year        = (int) $d->format('Y');
        $month       = (int) $d->format('n');
        $daysInMonth = (int) $d->format('t');

        // robustes Mapping 0=So..6=Sa -> 1=Mo..7=So
        $dow0_to_N = static function (int $dow0): int { return ($dow0 === 0) ? 7 : $dow0; };

        // erster Wochen-Tag (1..7)
        if (function_exists('jddayofweek') && function_exists('cal_to_jd')) {
            $firstDow0 = jddayofweek(cal_to_jd(CAL_GREGORIAN, $month, 1, $year), 0);
            $firstDowN = $dow0_to_N($firstDow0);
        } else {
            $firstDowN = (int) (new DateTime(sprintf('%04d-%02d-01', $year, $month), $tz))->format('N');
        }

        $hours = BKIT_MVP_OpeningHours_Admin::get_hours();
        $today = (new DateTime('now', $tz))->format('Y-m-d');

        $getHoursRow = function(int $dowN) use ($hours) {
            if (isset($hours[$dowN]) && is_array($hours[$dowN])) return $hours[$dowN];
            $dow0 = ($dowN + 6) % 7;
            if (isset($hours[$dow0]) && is_array($hours[$dow0])) return $hours[$dow0];
            return ['closed' => 0];
        };

        $cells = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

            if (function_exists('jddayofweek') && function_exists('cal_to_jd')) {
                $dow0 = jddayofweek(cal_to_jd(CAL_GREGORIAN, $month, $day, $year), 0);
                $dowN = $dow0_to_N($dow0);
            } else {
                $dowN = (int) (new DateTime($date, $tz))->format('N');
            }

            $cfg = $getHoursRow($dowN);

            $closed_by_rule  = !empty($cfg['closed']);
            $closed_by_event = BKIT_MVP_ClosedDays_Admin::is_closed_on($date);

            $state = ($closed_by_rule || $closed_by_event) ? 'closed' : 'open';
            $past  = ($date < $today);

            $cells[] = ['day'=>$day, 'date'=>$date, 'state'=>$state, 'past'=>$past];
        }

        ob_start(); ?>
        <div class="bkit-calendar" style="max-width: <?php echo esc_attr($atts['max_width']); ?>">

            <?php
            $curFirst = new DateTime('first day of this month', $tz); $curFirst->setTime(0,0,0);
            $next = (clone $d)->modify('+1 month');
            $prev = (clone $d)->modify('-1 month');

            $prev_allowed = ($prev >= $curFirst);
            $prev_q = esc_url( add_query_arg(['bk_month' => $prev->format('Y-m')]) );
            $next_q = esc_url( add_query_arg(['bk_month' => $next->format('Y-m')]) );
            ?>
            <div class="bkit-cal-head">
                <a class="bkit-nav prev<?php echo $prev_allowed ? '' : ' disabled'; ?>"
                   href="<?php echo $prev_allowed ? $prev_q : '#'; ?>"
                   aria-label="<?php esc_attr_e('Previous month', 'bookingkit-mvp'); ?>">‹</a>
                <span class="bkit-cal-title"><?php echo esc_html( date_i18n('F Y', $d->getTimestamp()) ); ?></span>
                <a class="bkit-nav next" href="<?php echo $next_q; ?>"
                   aria-label="<?php esc_attr_e('Next month', 'bookingkit-mvp'); ?>">›</a>
            </div>

            <div class="bkit-grid" data-bk-cal>
                <?php
                // Wochentagsköpfe (Montag zuerst), ohne Indizes
                global $wp_locale;
                $wd_abbr      = array_values($wp_locale->weekday_abbrev);             // [So, Mo, Di, Mi, Do, Fr, Sa]
                $wd_mon_first = array_merge(array_slice($wd_abbr, 1), [$wd_abbr[0]]); // [Mo..Sa, So]
                foreach ($wd_mon_first as $wd) {
                    echo '<div class="bkit-cell bkit-wd">'. esc_html($wd) .'</div>';
                }

                // Leere Felder bis zum ersten Tag
                for ($i = 1; $i < $firstDowN; $i++) {
                    echo '<div class="bkit-cell bkit-empty"></div>';
                }

                foreach ($cells as $c) {
                    $isClickable = (!$c['past'] && $c['state'] === 'open');
                    $classes = 'bkit-cell day ' . ($c['past'] ? 'past' : $c['state']) . ($isClickable ? ' clickable' : ' disabled');
                    printf(
                        '<button class="%s" data-date="%s" type="button" %s><span class="num">%d</span></button>',
                        esc_attr($classes),
                        esc_attr($c['date']),
                        $isClickable ? '' : 'aria-disabled="true"',
                        (int) $c['day']
                    );
                }
                ?>
            </div>

            <?php if ($atts['show_legend'] === '1'): ?>
                <div class="bkit-legend">
                    <span class="legend open"><?php esc_html_e('Open', 'bookingkit-mvp'); ?></span>
                    <span class="legend closed"><?php esc_html_e('Closed', 'bookingkit-mvp'); ?></span>
                </div>
            <?php endif; ?>

            <div class="bkit-modal" style="display:none;">
                <div class="bkit-modal-box">
                    <div class="bkit-modal-head">
                        <span class="title"><?php esc_html_e('Reservation request', 'bookingkit-mvp'); ?></span>
                        <button class="bkit-close" type="button" aria-label="<?php esc_attr_e('Close','bookingkit-mvp'); ?>">×</button>
                    </div>
                    <form class="bkit-res-form">
                        <input type="hidden" name="date" value="">
                        <div class="row"><label><?php esc_html_e('Date','bookingkit-mvp'); ?></label><input type="text" name="date_view" value="" readonly></div>
                        <div class="row-2">
                            <div><label><?php esc_html_e('Time','bookingkit-mvp'); ?></label><input type="time" name="time"></div>
                            <div><label><?php esc_html_e('Persons','bookingkit-mvp'); ?></label><input type="number" name="persons" min="1" max="20" value="2"></div>
                        </div>
                        <div class="row-2">
                            <div><label><?php esc_html_e('Name','bookingkit-mvp'); ?></label><input type="text" name="name" required></div>
                            <div><label><?php esc_html_e('Phone','bookingkit-mvp'); ?></label><input type="tel" name="phone"></div>
                        </div>
                        <div class="row"><label><?php esc_html_e('Email','bookingkit-mvp'); ?></label><input type="email" name="email" required></div>
                        <div class="row"><label><?php esc_html_e('Message','bookingkit-mvp'); ?></label><textarea name="message" rows="3"></textarea></div>
                        <div class="actions">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Send request','bookingkit-mvp'); ?></button>
                            <button type="button" class="button bkit-cancel"><?php esc_html_e('Cancel','bookingkit-mvp'); ?></button>
                        </div>
                    </form>
                    <div class="bkit-modal-foot bkit-feedback" style="display:none;"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
