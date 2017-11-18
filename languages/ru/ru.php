<?php

namespace languages\ru;

class ru
{

    public static function slug($string, $translit = true)
    {
        if ( $translit ) {
            $string = mb_strtolower(self::translit($string, '-'));
        }
        else {
            $string = strip_tags($string);
            $string = mb_strtolower($string);
        }

        $string = preg_replace('/[^a-zа-яё0-9\-\/]/isu', '-', $string);
        $string = preg_replace('#[-]{2,}#i', '-', trim($string, '-'));

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

        $string = preg_replace_callback('#(а|и|о|у|ы|э|ю|я|ъ|ь|\s)(е|ё)#isu', function ($matches) {
            if ( $matches[2] == 'е' || $matches[2] == 'ё' ) {
                return $matches[1] . 'ye';
            }
            else {
                return $matches[1] . 'Ye';
            }
        }, ' ' . $string);

        $string = str_replace(
                [ 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', ], [ 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'ts', 'ch', 'sh', 'shch', '"', 'y', "'", 'e', 'yu', 'ya', 'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'Kh', 'Ts', 'Ch', 'Sh', 'Shch', '"', 'Y', "'", 'E', 'Yu', 'Ya' ], $string
        );

        $string = trim(preg_replace('#\s+#ius', ' ', $string));

        return $string;
    }

    public static function setLocale()
    {
        if ( !defined('LC_LANGUAGE_TERRITORY') ) {
            define('LC_LANGUAGE_TERRITORY', 'ru_RU');
        }

        setlocale(LC_ALL, 'ru_RU.UTF-8');
        setlocale(LC_NUMERIC, 'POSIX');

        return true;
    }

}
