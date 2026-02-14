<?php
defined('_JEXEC') or die;

class ModWorkStatusHelper
{
    public static function getStatus($params)
    {
        $announcement = trim($params->get('announcement', ''));
        $ending = trim($params->get('ending_text', ''));

        // Приоритет: Объявление
        if ($announcement !== '') {
            return [
                'text'              => $announcement,
                'class'             => 'uk-text-danger',
                'is_announcement'   => true,
                'is_time_related'   => false,
            ];
        }

        $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $dayOfWeek = (int)$now->format('w'); // 0 = ВС, 1 = ПН, ..., 6 = СБ
        $currentMinutes = (int)$now->format('H') * 60 + (int)$now->format('i');

        $schedule = [
            1 => ['open_h' => $params->get('mon_open_hour'), 'open_m' => $params->get('mon_open_min'), 'close_h' => $params->get('mon_close_hour'), 'close_m' => $params->get('mon_close_min')],
            2 => ['open_h' => $params->get('tue_open_hour'), 'open_m' => $params->get('tue_open_min'), 'close_h' => $params->get('tue_close_hour'), 'close_m' => $params->get('tue_close_min')],
            3 => ['open_h' => $params->get('wed_open_hour'), 'open_m' => $params->get('wed_open_min'), 'close_h' => $params->get('wed_close_hour'), 'close_m' => $params->get('wed_close_min')],
            4 => ['open_h' => $params->get('thu_open_hour'), 'open_m' => $params->get('thu_open_min'), 'close_h' => $params->get('thu_close_hour'), 'close_m' => $params->get('thu_close_min')],
            5 => ['open_h' => $params->get('fri_open_hour'), 'open_m' => $params->get('fri_open_min'), 'close_h' => $params->get('fri_close_hour'), 'close_m' => $params->get('fri_close_min')],
            6 => ['open_h' => $params->get('sat_open_hour'), 'open_m' => $params->get('sat_open_min'), 'close_h' => $params->get('sat_close_hour'), 'close_m' => $params->get('sat_close_min')],
            0 => ['open_h' => $params->get('sun_open_hour'), 'open_m' => $params->get('sun_open_min'), 'close_h' => $params->get('sun_close_hour'), 'close_m' => $params->get('sun_close_min')],
        ];

        $today = $schedule[$dayOfWeek] ?? null;
        if (!$today || !self::isValidTime($today)) {
            $nextOpen = self::findNextOpenDay($schedule, $dayOfWeek);
            if ($nextOpen === null) {
                return ['text' => 'закрыто', 'class' => 'uk-text-danger', 'is_announcement' => false, 'is_time_related' => false];
            }
            $nextDayName = self::getDayName($nextOpen['day']);
            return ['text' => "закрыто до {$nextDayName}", 'class' => 'uk-text-danger', 'is_announcement' => false, 'is_time_related' => false];
        }

        $openMin = (int)$today['open_h'] * 60 + (int)$today['open_m'];
        $closeMin = (int)$today['close_h'] * 60 + (int)$today['close_m'];

        if ($currentMinutes >= $openMin && $currentMinutes < $closeMin) {
            $closeTime = self::formatTime((int)$today['close_h'], (int)$today['close_m']);
            return [
                'text'              => "открыто до {$closeTime}",
                'class'             => 'uk-text-success',
                'is_announcement'   => false,
                'is_time_related'   => true,
            ];
        } elseif ($currentMinutes < $openMin) {
            $openTime = self::formatTime((int)$today['open_h'], (int)$today['open_m']);
            return [
                'text'              => "закрыто до {$openTime}",
                'class'             => 'uk-text-danger',
                'is_announcement'   => false,
                'is_time_related'   => true,
            ];
        } else {
            $nextOpen = self::findNextOpenDay($schedule, $dayOfWeek);
            if ($nextOpen === null) {
                return ['text' => 'закрыто', 'class' => 'uk-text-danger', 'is_announcement' => false, 'is_time_related' => false];
            }
            if ($nextOpen['day'] === ($dayOfWeek + 1) % 7) {
                return ['text' => 'закрыто до завтра', 'class' => 'uk-text-danger', 'is_announcement' => false, 'is_time_related' => false];
            } else {
                $nextDayName = self::getDayName($nextOpen['day']);
                return ['text' => "закрыто до {$nextDayName}", 'class' => 'uk-text-danger', 'is_announcement' => false, 'is_time_related' => false];
            }
        }
    }

    private static function isValidTime($entry)
    {
        return isset($entry['open_h'], $entry['open_m'], $entry['close_h'], $entry['close_m']) &&
               $entry['open_h'] !== '' && $entry['open_m'] !== '' &&
               $entry['close_h'] !== '' && $entry['close_m'] !== '';
    }

    private static function findNextOpenDay($schedule, $currentDay)
    {
        for ($i = 1; $i <= 7; $i++) {
            $nextDay = ($currentDay + $i) % 7;
            if (self::isValidTime($schedule[$nextDay])) {
                return ['day' => $nextDay, 'time' => $schedule[$nextDay]];
            }
        }
        return null;
    }

    private static function getDayName($dayIndex)
    {
        $names = [
            0 => 'воскресенья',
            1 => 'понедельника',
            2 => 'вторника',
            3 => 'среды',
            4 => 'четверга',
            5 => 'пятницы',
            6 => 'субботы',
        ];
        return $names[$dayIndex] ?? 'следующего дня';
    }

    private static function formatTime($h, $m)
    {
        return sprintf('%d:%02d', $h, $m);
    }

    public static function getScheduleHtml($params)
    {
        $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $currentDayIndex = (int)$now->format('w');

        $days = [
            1 => ['label' => 'Понедельник', 'open_h' => $params->get('mon_open_hour'), 'open_m' => $params->get('mon_open_min'), 'close_h' => $params->get('mon_close_hour'), 'close_m' => $params->get('mon_close_min'), 'note' => $params->get('mon_note', '')],
            2 => ['label' => 'Вторник', 'open_h' => $params->get('tue_open_hour'), 'open_m' => $params->get('tue_open_min'), 'close_h' => $params->get('tue_close_hour'), 'close_m' => $params->get('tue_close_min'), 'note' => $params->get('tue_note', '')],
            3 => ['label' => 'Среда', 'open_h' => $params->get('wed_open_hour'), 'open_m' => $params->get('wed_open_min'), 'close_h' => $params->get('wed_close_hour'), 'close_m' => $params->get('wed_close_min'), 'note' => $params->get('wed_note', '')],
            4 => ['label' => 'Четверг', 'open_h' => $params->get('thu_open_hour'), 'open_m' => $params->get('thu_open_min'), 'close_h' => $params->get('thu_close_hour'), 'close_m' => $params->get('thu_close_min'), 'note' => $params->get('thu_note', '')],
            5 => ['label' => 'Пятница', 'open_h' => $params->get('fri_open_hour'), 'open_m' => $params->get('fri_open_min'), 'close_h' => $params->get('fri_close_hour'), 'close_m' => $params->get('fri_close_min'), 'note' => $params->get('fri_note', '')],
            6 => ['label' => 'Суббота', 'open_h' => $params->get('sat_open_hour'), 'open_m' => $params->get('sat_open_min'), 'close_h' => $params->get('sat_close_hour'), 'close_m' => $params->get('sat_close_min'), 'note' => $params->get('sat_note', '')],
            0 => ['label' => 'Воскресенье', 'open_h' => $params->get('sun_open_hour'), 'open_m' => $params->get('sun_open_min'), 'close_h' => $params->get('sun_close_hour'), 'close_m' => $params->get('sun_close_min'), 'note' => $params->get('sun_note', '')],
        ];

        $html = '';
        foreach ($days as $dayIndex => $day) {
            $isOpen = self::isValidTime([
                'open_h' => $day['open_h'], 'open_m' => $day['open_m'],
                'close_h' => $day['close_h'], 'close_m' => $day['close_m']
            ]);

            $note = trim($day['note'] ?? '');
            if ($note !== '') {
                $timeText = $note;
            } else {
                $timeText = $isOpen
                    ? sprintf('%d:%02d – %d:%02d', (int)$day['open_h'], (int)$day['open_m'], (int)$day['close_h'], (int)$day['close_m'])
                    : 'Выходной';
            }

            $class = '';
            if ($dayIndex === $currentDayIndex) {
                $class = $isOpen ? 'uk-text-success' : 'uk-text-danger';
            }

            $html .= '<li class="el-item">';
            $html .= '<div class="uk-child-width-auto@s uk-grid-small' . ($class ? ' ' . $class : '') . '" uk-grid>';
            $html .= '<div class="uk-width-expand"><div class="el-title uk-margin-remove">' . htmlspecialchars($day['label'], ENT_QUOTES, 'UTF-8') . '</div></div>';
            $html .= '<div><div class="el-content uk-panel">' . $timeText . '</div></div>';
            $html .= '</div>';
            $html .= '</li>';
        }

        return $html;
    }
}