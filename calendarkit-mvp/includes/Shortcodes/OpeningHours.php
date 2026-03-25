<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_Shortcode_OpeningHours {

    /**
     * Originaler Shortcode [bk_opening_hours]
     * Unverändert gelassen, damit bestehende Seiten weiter funktionieren.
     */
    public static function render($atts = []) {
        if ( !class_exists('BKIT_MVP_OpeningHours_Admin') ) return '';

        $atts = shortcode_atts([
            'title' => '1', // "0" = keine Überschrift
        ], $atts, 'bk_opening_hours');

        $hours = BKIT_MVP_OpeningHours_Admin::get_hours();
        // Sprache fix auf Deutsch (MVP)
        $names = [
            1=>'Montag',
            2=>'Dienstag',
            3=>'Mittwoch',
            4=>'Donnerstag',
            5=>'Freitag',
            6=>'Samstag',
            7=>'Sonntag'
        ];
        ob_start(); ?>
        <div class="bkit-opening-hours">
            <?php if ($atts['title'] !== '0'): ?>
                <h3><?php echo esc_html('Öffnungszeiten'); ?></h3>
            <?php endif; ?>

            <table class="bkit-oh-table" role="table">
                <tbody>
                <?php foreach ($names as $i=>$label):
                    $r = $hours[$i] ?? [];
                    $closed = !empty($r['closed']);
                    $from = trim($r['from'] ?? '');
                    $to   = trim($r['to']   ?? '');
                    $timeText = $closed
                        ? 'geschlossen'
                        : ($from . ' – ' . ($to !== '' ? $to : 'open end'));
                ?>
                    <tr>
                        <th scope="row" class="day"><?php echo esc_html($label); ?></th>
                        <td class="time<?php echo $closed ? ' is-closed' : ''; ?>"><?php echo esc_html($timeText); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Neuer Shortcode [bk_opening_hours_pretty group="0|1"]
     * - zweispaltig, sauber vertikal ausgerichtet
     * - optional gleiche Zeiten über mehrere Tage zusammenfassen (group="1")
     * Funktioniert mit deinem CSS-Block (.bk-oh-grid ...).
     */
    public static function render_pretty($atts = []) {
        if ( !class_exists('BKIT_MVP_OpeningHours_Admin') ) return '';

        $atts = shortcode_atts([
            'group' => '0', // '1' = gleiche Zeiten gruppieren
        ], $atts, 'bk_opening_hours_pretty');

        $hours = BKIT_MVP_OpeningHours_Admin::get_hours();

        // Labels für Mo..So (1..7)
        $labels = [
            1 => 'Mo',
            2 => 'Di',
            3 => 'Mi',
            4 => 'Do',
            5 => 'Fr',
            6 => 'Sa',
            7 => 'So',
        ];

        // Zeittext für eine Zeile
        $timeLabel = function($row){
            if (!empty($row['closed'])) return 'geschlossen';
            $from = trim($row['from'] ?? '');
            $to   = trim($row['to']   ?? '');
            $to   = ($to === '') ? 'open end' : $to;
            return esc_html($from . ' – ' . $to);
        };

        // Reihen (Tage) vorbereiten – ggf. gruppiert
        $rows = [];
        if ($atts['group'] === '1') {
            $i = 1;
            while ($i <= 7) {
                $j = $i;
                $sig = (!empty($hours[$i]['closed'])) ? 'closed' : (($hours[$i]['from'] ?? '').'|'.($hours[$i]['to'] ?? ''));
                while ($j+1 <= 7) {
                    $sig2 = (!empty($hours[$j+1]['closed'])) ? 'closed' : (($hours[$j+1]['from'] ?? '').'|'.($hours[$j+1]['to'] ?? ''));
                    if ($sig2 !== $sig) break;
                    $j++;
                }
                $rows[] = ['start'=>$i, 'end'=>$j, 'data'=>$hours[$i]];
                $i = $j + 1;
            }
        } else {
            for ($d=1; $d<=7; $d++) {
                $rows[] = ['start'=>$d, 'end'=>$d, 'data'=>$hours[$d]];
            }
        }

        // Ausgabe (Grid: 2 Spalten – .day/.time)
        ob_start(); ?>
        <div class="bk-oh-pretty">
            <ul class="bk-oh-grid">
                <?php foreach ($rows as $r):
                    $range = ($r['start'] === $r['end'])
                        ? $labels[$r['start']]
                        : $labels[$r['start']] . '–' . $labels[$r['end']];
                    $closed = !empty($r['data']['closed']);
                ?>
                <li>
                    <span class="day"><?php echo esc_html($range); ?></span>
                    <span class="time<?php echo $closed ? ' is-closed' : ''; ?>">
                        <?php echo $timeLabel($r['data']); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Registrierung NUR des neuen Pretty-Shortcodes hier.
 * Der klassische [bk_opening_hours] wird weiterhin im Hauptplugin registriert.
 */
add_action('init', function () {
    add_shortcode('bk_opening_hours_pretty', ['BKIT_MVP_Shortcode_OpeningHours', 'render_pretty']);
});
