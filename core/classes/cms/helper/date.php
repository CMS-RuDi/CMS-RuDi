<?php

namespace cms\helper;

/**
 * @package Classes
 * @subpackage Helper
 */
class date
{

    /**
     * Возвращает разницу между датами в виде массива
     *
     * Возвращает массив, в котором элементы:
     *  0 => число лет
     *  1 => число месяцев
     *  2 => число дней
     *  3 => число часов
     *  4 => число минут
     *  5 => число секунд
     *
     * @author Олег Савватеев @ http://savvateev.org
     *
     * @param string $date1
     * @param string $date2
     *
     * @return array
     */
    public static function diff($date1, $date2 = null)
    {
        $diff = array();

        if ( !is_string($date1) ) {
            return false;
        }

        // Если вторая дата не задана принимаем ее как текущую
        if ( !$date2 ) {
            $cd    = getdate();
            $date2 = $cd['year'] . '-' . $cd['mon'] . '-' . $cd['mday'] . ' ' . $cd['hours'] . ':' . $cd['minutes'] . ':' . $cd['seconds'];
        }

        // Преобразуем даты в массив
        $pattern = '/(\d+)-(\d+)-(\d+)(\s+(\d+):(\d+):(\d+))?/';
        preg_match($pattern, $date1, $matches);
        $d1      = array( (int) $matches[1], (int) $matches[2], (int) $matches[3], (int) $matches[5], (int) $matches[6], (int) $matches[7] );
        preg_match($pattern, $date2, $matches);
        $d2      = array( (int) $matches[1], (int) $matches[2], (int) $matches[3], (int) $matches[5], (int) $matches[6], (int) $matches[7] );

        // Если вторая дата меньше чем первая, меняем их местами
        for ( $i = 0; $i < count($d2); $i++ ) {
            if ( $d2[$i] > $d1[$i] ) {
                break;
            }

            if ( $d2[$i] < $d1[$i] ) {
                $t  = $d1;
                $d1 = $d2;
                $d2 = $t;
                break;
            }
        }

        // Вычисляем разность между датами (как в столбик)
        $md1   = array( 31, $d1[0] % 4 || (!($d1[0] % 100) && $d1[0] % 400) ? 28 : 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
        $md2   = array( 31, $d2[0] % 4 || (!($d2[0] % 100) && $d2[0] % 400) ? 28 : 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
        $min_v = array( NULL, 1, 1, 0, 0, 0 );
        $max_v = array( NULL, 12, $d2[1] == 1 ? $md2[11] : $md2[$d2[1] - 2], 23, 59, 59 );

        for ( $i = 5; $i >= 0; $i-- ) {
            if ( $d2[$i] < $min_v[$i] ) {
                $d2[$i - 1] --;
                $d2[$i] = $max_v[$i];
            }

            $diff[$i] = $d2[$i] - $d1[$i];

            if ( $diff[$i] < 0 ) {
                $d2[$i - 1] --;
                $i == 2 ? $diff[$i] += $md1[$d1[1] - 1] : $diff[$i] += $max_v[$i] - $min_v[$i] + 1;
            }
        }

        // Возвращаем результат
        return $diff;
    }

    /**
     * Выводит разницу между переданной датой и текущим временем
     * в виде читабельной строки со склонениями
     *
     * Пример вывода: "2 года 16 дней 5 часов 12 минут"
     *
     * @param string $date
     * @param array $options Массив элементов для перечисления: y, m, d, h, i, from_date
     * @param bool $is_add_back Добавлять к строке слово "назад"?
     *
     * @return string
     */
    public static function age($date, $options, $is_add_back = false)
    {
        if ( !$date ) {
            return;
        }

        $date2 = !empty($options['from_date']) ? $options['from_date'] : false;

        $diff = self::diff($date, $date2);

        $diff_str = array();

        $l = \cms\lang::getInstance();

        if ( in_array('y', $options) && $diff[0] ) {
            $diff_str[] = str::spellcount($diff[0], $l->year1, $l->year2, $l->year10);
        }

        if ( in_array('m', $options) && $diff[1] ) {
            $diff_str[] = str::spellcount($diff[1], $l->month1, $l->month2, $l->month10);
        }

        if ( in_array('d', $options) && $diff[2] ) {
            $diff_str[] = str::spellcount($diff[2], $l->day1, $l->day2, $l->day10);
        }

        if ( in_array('h', $options) && $diff[3] ) {
            $diff_str[] = str::spellcount($diff[3], $l->hour1, $l->hour2, $l->hour10);
        }

        if ( in_array('i', $options) && $diff[4] ) {
            $diff_str[] = str::spellcount($diff[4], $l->minute1, $l->minute2, $l->minute10);
        }

        if ( !$diff_str ) {
            return $l->less_minute;
        }
        else {
            $diff_str = trim(implode(' ', $diff_str));

            return $is_add_back ? sprintf($l->date_ago, $diff_str) : $diff_str;
        }
    }

    /**
     * Выводит максимальную разницу между переданной датой и текущим временем
     * в виде читабельной строки со склонением
     *
     * Пример вывода: "3 дня"
     *
     * @param string $date
     * @param bool $is_add_back Добавлять к строке слово "назад"?
     *
     * @return string
     */
    function ageMax($date, $is_add_back = false)
    {
        if ( !$date ) {
            return;
        }

        $age = self::age($date, [ 'y', 'm', 'd', 'h', 'i' ]);

        $l = \cms\lang::getInstance();

        if ( $l->less_minute == $age ) {
            return $l->just_now;
        }
        else {
            $parts = explode(' ', $age);

            return $is_add_back ? sprintf($l->date_ago, $age[0] . ' ' . $age[1]) : ($age[0] . ' ' . $age[1]);
        }
    }

    /**
     * Форматирует дату в формат "сегодня", "вчера", "1 января 2017"
     *
     * @param string $date Исходная дата. Может быть как отформатированном виде, так и timestamp
     * @param boolean $is_time Дополнять часом и минутами
     *
     * @return string
     */
    public static function format($date, $is_time = false)
    {
        if ( !$date ) {
            return '';
        }

        if ( !is_numeric($date) ) {
            $timestamp = strtotime($date);
        }
        else {
            $timestamp = $date;
        }

        $item_date = date('j F Y', $timestamp);

        $today_date     = date('j F Y');
        $yesterday_date = date('j F Y', time() - 3600 * 24);

        $l = \cms\lang::getInstance();

        switch ( $item_date ) {
            case $today_date:
                $result = $l->today;
                break;
            case $yesterday_date:
                $result = $l->yesterday;
                break;
            default:
                $result = self::langDate($item_date);
        }

        if ( $is_time ) {
            $result .= ' ' . $l->in . ' ' . date('H:i', $timestamp);
        }

        return $result;
    }

    /**
     * Возвращает массив с названиями месяцев в текущей локали
     *
     * @return array
     */
    public static function getLangMonths()
    {
        $l = \cms\lang::getInstance();

        return array(
            $l->month_01, $l->month_02, $l->month_03, $l->month_04, $l->month_05, $l->month_06,
            $l->month_07, $l->month_08, $l->month_09, $l->month_10, $l->month_11, $l->month_12
        );
    }

    /**
     * Возвращает название дней недели в тепкущей локали
     *
     * @param bool $short Указывает возвращать короткие названия дней недели или полные
     *
     * @return array
     */
    public static function getLangDays($short = true)
    {
        $l = \cms\lang::getInstance();

        if ( $short ) {
            return [ $l->sunday_short, $l->monday_short, $l->tuesday_short, $l->wednesday_short, $l->thursday_short, $l->friday_short, $l->saturday_short ];
        }
        else {
            return [ $l->sunday, $l->monday, $l->tuesday, $l->wednesday, $l->thursday, $l->friday, $l->saturday ];
        }
    }

    /**
     * Заменяет название месяцев в переданной дате на название в текущей локали
     *
     * @param string $date_string Строка исходной даты
     *
     * @return string Строка даты после замены
     */
    public static function langDate($date_string)
    {
        $eng_months = array(
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        );

        return str_replace($eng_months, self::getLangMonths(), $date_string);
    }

}
