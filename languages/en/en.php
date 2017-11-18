<?php

namespace languages\en;

class en
{

    public static function slug($string, $translit = true)
    {
        $string = trim(strip_tags($string));
        $string = mb_strtolower($string);

        $string = preg_replace('/[^a-z0-9\-\/]/u', '-', $string);
        $string = preg_replace('/([-]{2,})/i', '-', $string);
        $string = trim($string, '-');

        if ( empty($string) ) {
            $string = 'untitled';
        }

        if ( is_numeric($string) ) {
            $string .= strtolower(date('F'));
        }

        return $string;
    }

    public static function translit($string)
    {
        $string = strip_tags($string);

        $string = preg_replace('#\s+#ius', ' ', $string);

        $string = trim($string);

        return $string;
    }

    public static function setLocale()
    {
        if ( !defined('LC_LANGUAGE_TERRITORY') ) {
            define('LC_LANGUAGE_TERRITORY', 'ru_RU');
        }

        setlocale(LC_ALL, 'en_US.UTF-8');

        return true;
    }

}
