<?php

namespace cms\helper;

/**
 * @package Classes
 * @subpackage Helper
 */
class str
{

    /**
     * Получает строку вида "8M" или "1024K" и возвращает значение в байтах
     * Полезно при получении max_upload_size из php.ini
     *
     * @param string $value
     *
     * @return int
     */
    public static function bytesConvert($value)
    {
        if ( is_numeric($value) ) {
            return $value;
        }
        else {
            $value_length = strlen($value);
            $qty          = substr($value, 0, $value_length - 1);
            $unit         = strtolower(substr($value, $value_length - 1));

            switch ( $unit ) {
                case 'k':
                    $qty *= 1024;
                    break;
                case 'm':
                    $qty *= 1048576;
                    break;
                case 'g':
                    $qty *= 1073741824;
                    break;
            }

            return $qty;
        }

        return $value;
    }

    /**
     * Переводит байты в Гб, Мб или Кб и возвращает полученное число + единицу измерения
     * в виде единой строки
     *
     * @param int $bytes
     *
     * @return string
     */
    public static function bytesFormat($bytes)
    {
        $kb = 1024;
        $mb = 1048576;
        $gb = 1073741824;

        if ( round($bytes / $gb) > 0 ) {
            return round(($bytes / $gb), 1, PHP_ROUND_HALF_UP) . ' ' . \cms\lang::getInstance()->size_gb;
        }

        if ( round($bytes / $mb) > 0 ) {
            return ceil($bytes / $mb) . ' ' . \cms\lang::getInstance()->size_mb;
        }

        if ( round($bytes / $kb) > 0 ) {
            return ceil($bytes / $kb) . ' ' . \cms\lang::getInstance()->size_kb;
        }

        return $bytes . ' ' . \cms\lang::getInstance()->size_b;
    }

    /**
     * Разбивает строку по разделителю, затем собирает обратно в camelCase
     * Например
     * "my_own_string" => "myOwnString", разделитель "_" $first_uc = false
     * "my_own_string" => "MyOwnString", разделитель "_" $first_uc = true
     *
     * @param char $delimiter Разделитель
     * @param string $string Исходная строка
     * @param bool $first_uc Флаг указывающий нужно ли переводить в верхний регистр первую букву в финальной строке
     *
     * @return string
     */
    public static function toCamel($delimiter, $string, $first_uc = false)
    {
        $result = '';
        $words  = explode($delimiter, mb_strtolower($string));

        foreach ( $words as $k => $word ) {
            if ( $k > 0 ) {
                $result .= ucfirst($word);
            }
        }

        return $result;
    }

    /**
     * Вырезает теги <br> из строки
     *
     * @param string $string
     *
     * @return string
     */
    public static function stripBr($string)
    {
        return str_replace('<br>', '', str_replace('<br/>', '', $string));
    }

    /**
     * Преобразует строку с маской URL в обычное регулярное выражение
     *
     * Пример:
     *      "my*mask is %st place" => "my(.*)mask is ([0-9]+) place"
     *
     * @param string $mask
     *
     * @return string
     */
    public static function maskToRegular($mask)
    {
        return str_replace([ '%', '/', '*', '?', '{slug}' ], [ '([0-9]+)', '\/', '(.*)', '\?', '([a-z0-9\-]*)' ], trim($mask));
    }

    /**
     * Разбивает текст на строки, а каждую строку на ID и VALUE, разделенные |,
     * массив, если строка обернута в фигурные строки то такие строки пропускаются
     * для не аворизованных пользоваелей
     *
     * Пример входящей строки:
     *      "id1 | value1 \n id2 | value2"
     *
     * Пример результата:
     *      array(array('id' => 'id1', 'value' => 'value1'), array('id' => 'id2', 'value' => 'value2'))
     *
     * @param string $string_list
     *
     * @return array
     */
    public static function parseList($string_list)
    {
        if ( !$string_list ) {
            return [];
        }

        $user = \cmsUser::getInstance();

        $rows = explode("\n", $string_list);

        $list = [];

        foreach ( $rows as $row ) {
            if ( !$row ) {
                continue;
            }

            $row = trim($row);

            if ( preg_match('/^{(.*)}$/i', $row, $matches) ) {
                if ( !$user->id ) {
                    continue;
                }

                $row = trim($matches[1]);
            }

            if ( !mb_strstr($row, '|') ) {
                $list[] = [ 'value' => trim($row) ];
            }
            else {
                list($id, $value) = explode('|', $row);

                $list[] = [
                    'id'    => trim($id),
                    'value' => trim($value)
                ];
            }
        }

        return $list;
    }

    /**
     * Разбивает текст на строки, а каждую строку на ID и VALUE, разделенные |,
     * формируя ассоциативный массив
     *
     * Пример входящей строки:
     *      "id1 | value1 \n id2 | value2"
     *
     * Пример результата:
     *      array('id1' => 'value1', 'id2' => 'value2')
     *
     * @param string $string_list
     *
     * @return array
     */
    public static function explodeList($string_list)
    {
        $items = [];

        $rows = explode("\n", trim($string_list));

        if ( is_array($rows) ) {
            foreach ( $rows as $count => $row ) {
                if ( mb_strpos($row, '|') ) {
                    list($index, $value) = explode('|', trim($row));
                }
                else {
                    $index = $count + 1;
                    $value = $row;
                }

                $items[trim($index)] = trim($value);
            }
        }

        return $items;
    }

    /**
     * Получает список аналогично self::parseList() и ищет вхождение в него
     * заданной строки
     *
     * @param string $string
     * @param string $mask_list
     *
     * @return boolean
     */
    public static function inMaskList($string, $mask_list)
    {
        if ( !$mask_list ) {
            return false;
        }

        $mask_list = explode("\n", $mask_list);

        foreach ( $mask_list as $item ) {
            $regular = self::maskToRegular($item);
            $regular = "/^{$regular}$/iu";

            if ( preg_match($regular, $string) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Генерирует случайную последовательность символов заданной длины
     *
     * @param int $length
     *
     * @return string
     */
    public static function random($length = 32, $seed = '')
    {
        $string = md5(mt_rand(1000000, 9999999) . microtime(true) . $seed . mt_rand(1000000, 9999999));

        if ( $length < 32 ) {
            $string = substr($string, 0, $length);
        }
        elseif ( $length > 32 ) {
            $string .= self::random($length - 32, $seed);
        }

        return $string;
    }

    /**
     * Возвращает число с числительным в нужном склонении
     *
     * @param int $num
     * @param string $one
     * @param string $two
     * @param string $many
     * @param string $zero_text
     *
     * @return string
     */
    public static function spellcount($num, $one, $two = false, $many = false, $zero_text = '')
    {
        if ( !$two && !$many ) {
            list($one, $two, $many) = explode('|', $one);
        }

        if ( !$num ) {
            return ($zero_text ?: \cms\lang::getInstance()->no) . ' ' . $many;
        }

        return $num . ' ' . self::spellcountOnly($num, $one, $two, $many);
    }

    /**
     * @see str::spellcount()
     */
    public static function spellcountOnly($num, $one, $two = false, $many = false)
    {
        if ( !$two && !$many ) {
            list($one, $two, $many) = explode('|', $one);
        }

        if ( strpos($num, '.') !== false ) {
            return $two;
        }

        if ( $num % 10 == 1 && $num % 100 != 11 ) {
            return $one;
        }
        elseif ( $num % 10 >= 2 && $num % 10 <= 4 && ($num % 100 < 10 || $num % 100 >= 20) ) {
            return $two;
        }
        else {
            return $many;
        }

        return $one;
    }

    /**
     * Находит в строке все выражения вида {user.property} и заменяет property
     * на соответствующее свойство объекта cmsUser
     *
     * @param string $string
     *
     * @return string
     */
    public static function replaceUserProperties($string)
    {
        $matches_count = preg_match_all('/{user.([a-z0-9_]+)}/i', $string, $matches);

        if ( $matches_count ) {
            $user = \cmsUser::getInstance();

            for ( $i = 0; $i < $matches_count; $i++ ) {
                $tag      = $matches[0][$i];
                $property = $matches[1][$i];

                if ( isset($user->$property) ) {
                    $string = str_replace($tag, $user->$property, $string);
                }
            }
        }

        return $string;
    }

    /**
     * Находит внутри строки $string все выражения вида {key}, где key - это ключ
     * массива $data и заменяет на значение соответствующего элемента
     *
     * @param string $string
     * @param array $data
     *
     * @return string
     */
    public static function replaceKeysValues($string, $data)
    {
        if ( strpos($string, '{') === false ) {
            return $string;
        }

        foreach ( $data as $k => $v ) {
            if ( is_array($v) || is_object($v) ) {
                unset($data[$k]);
            }
        }

        $keys = array_map(function($key) {
            return '{' . $key . '}';
        }, array_keys($data));

        return str_replace($keys, array_values($data), $string);
    }

    /**
     * Находит внутри строки $string все выражения вида {key}, где key - это ключ
     * массива $data и заменяет на значение соответствующего элемента
     * отличительной особенностью от функции выше является возможность обработки значений функциями, методами классов.
     *
     * Обработка функцией:
     * выражение {nickname|str_replace:Аноним:Автор материала Василий} после обработки станет "Автор материала Аноним"
     * при значении nickname в массиве $data "Василий"
     *
     * Обработка статичным методом класса:
     * выражение {age|str::spellcount:год:года:лет} после обработки напишет "21 год, 22 года, 29 лет"
     * при значении age 21, 22 и 29 соответственно
     * str это класс \cms\helpers\str пространство имен \cms\helpers\ указывать не нужно он добавляется автоматически,
     * в целях безопасности для вызова доступны только методы классов пакета Helpers
     *
     * Если функция или метод не указаны то используется функция sprintf
     * выражение {nickname:профиль пользователя %s самый лучший} после обработки станет "профиль пользователя Василий самый лучший"
     * при значении поля nickname в массиве $data "Василий"
     *
     * @param string $string
     * @param array $data
     *
     * @return string
     */
    public static function replaceKeysValuesExtended($string, $data)
    {
        $matches_count = preg_match_all('/{([^}]+)}/ui', $string, $matches);

        if ( $matches_count ) {
            for ( $i = 0; $i < $matches_count; $i++ ) {
                $tag      = $matches[0][$i];
                $property = $matches[1][$i];

                $class                    = false;
                $func                     = false;
                $func_params              = array();
                $func_params_property_key = 0;

                // есть ли обработка функцией или методом
                if ( strpos($property, '|') !== false ) {
                    $params      = explode('|', $property);
                    // первый параметр остаётся как $property
                    $property    = $params[0];
                    // второй параметр - функция
                    $func        = $params[1];
                    // $property ставим как первый параметр функции
                    $func_params = array( $property );

                    // смотрим передан ли метод класса
                    if ( strpos($func, '::') !== false ) {
                        $par   = explode(':', $func);
                        $class = '\\cms\\helpers\\' . $par[0];

                        unset($par[0]);
                        unset($par[1]);

                        $func = implode(':', $par);
                    }

                    // смотрим есть ли у функции параметры
                    if ( strpos($func, ':') !== false ) {
                        $par  = explode(':', $func);
                        $func = $par[0];
                        unset($par[0]);

                        foreach ( $par as $k => $p ) {
                            // если параметр - массив
                            if ( strpos($p, '=') !== false ) {
                                $out     = array();
                                parse_str($p, $out);
                                $par[$k] = $out;
                            }
                        }

                        $func_params = $func_params + $par;
                    }
                }
                else
                // нужно прогнать через sprintf
                if ( strpos($property, ':') !== false ) {
                    $params                   = explode(':', $property);
                    $property                 = $params[0];
                    $func                     = 'sprintf';
                    $func_params              = array_reverse($params);
                    $func_params_property_key = 1;
                }

                if ( isset($data[$property]) && !is_array($data[$property]) && !is_object($data[$property]) ) {
                    $data_property                          = $data[$property];
                    $func_params[$func_params_property_key] = $data_property;

                    if ( !$class && $func && function_exists($func) ) {
                        $data_property = $func(...$func_params);
                    }
                    else if ( $class && class_exists($class) && method_exists($class, $func) ) {
                        $data_property = $class::{$func}(...$func_params);
                    }

                    $string = str_replace($tag, $data_property, $string);
                }
                else {
                    $string = str_replace($tag, '', $string);
                }
            }
        }

        return $string;
    }

    /**
     * Делает активными гиперссылки внутри строки
     *
     * @param string $string
     *
     * @return string
     */
    public static function makeLinks($string)
    {
        return preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $string);
    }

//============================================================================//

    /**
     * Возвращает строку с перечислением самых часто используемых
     * слов из исходного текста
     *
     * @param string $text
     * @param int $min_length Минимальная длина каждого слова
     * @param int $limit Количество слов в результирующей строке
     *
     * @return string
     */
    public static function getMetaKeywords($text, $min_length = 5, $limit = 10)
    {
        $stat = array();

        $text = str_replace(array( "\n", '<br>', '<br/>' ), ' ', $text);
        $text = strip_tags($text);
        $text = mb_strtolower($text);

        $stopwords = self::getStopwords(\cmsConfig::getConfig('lang'));

        $words = explode(' ', $text);

        foreach ( $words as $word ) {
            $word = trim($word);
            $word = str_replace(array( '(', ')', '+', '-', '.', '!', ':', '{', '}', '|', '"', ',', "'" ), '', $word);
            $word = preg_replace("/\.,\(\)\{\}/i", '', $word);

            if ( $stopwords && in_array($word, $stopwords) ) {
                continue;
            }

            if ( mb_strlen($word) >= $min_length ) {
                $stat[$word] = isset($stat[$word]) ? $stat[$word] + 1 : 1;
            }
        }

        asort($stat);
        $stat = array_reverse($stat, true);
        $stat = array_slice($stat, 0, $limit, true);

        return implode(', ', array_keys($stat));
    }

    /**
     * Подготавливает текст для использования в теге meta description
     *
     * @param string $text
     * @param int $limit Максимальная длина результата
     *
     * @return string
     */
    public static function getMetaDescription($text, $limit = 250)
    {
        return self::short($text, $limit);
    }

    /**
     * Возвращает массив стоп слов
     *
     * @staticvar array $words
     * @param string $lang Язык, например ru, en
     *
     * @return array
     */
    public static function getStopwords($lang = 'ru')
    {
        static $words = null;

        if ( isset($words[$lang]) ) {
            return $words[$lang];
        }

        $file = PATH . '/languages/' . $lang . '/stopwords/stopwords.php';

        if ( file_exists($file) ) {
            $words[$lang] = include $file;
        }
        else {
            $words[$lang] = array();
        }

        return $words[$lang];
    }

    /**
     * Удаляет HTML теги, оставляя читабельным текст без переносов
     * иначе после strip_tags не будет пробелов между словами
     *
     * @param string $text
     *
     * @return string
     */
    public static function strip_tags($text)
    {
        $text = str_replace(array( "\n", "\r", '<br>', '<br/>', '</p>', '</div>' ), ' ', $text);

        $text = strip_tags($text);

        $text = trim($text);

        return $text;
    }

    /**
     * Обрезает исходный текст до указанной длины (или последнего предложения),
     * удаляя HTML-разметку
     *
     * @param string $text
     * @param int $limit Максимальная длина результата
     *
     * @return string
     */
    public static function short($text, $limit = 0)
    {
        $text = self::strip_tags($text);

        $text = preg_replace('/\s+/', ' ', $text);

        if ( !$limit || mb_strlen($text) <= $limit ) {
            return $text;
        }

        $text = mb_substr($text, 0, $limit);

        preg_match('/^(.*)([.!?])(.*)$/i', $text, $matches);

        if ( !$matches ) {
            return $text;
        }
        else {
            return $matches[1] . $matches[2];
        }

        return $text;
    }

    /**
     * Обрезает текст до положения первого пробельного символа от указанной длины,
     * удаляя HTML теги
     *
     * @param string $text Текст для обрезки
     * @param int $length Желаемая длина строки
     * @param string $etc Приставка в конце обрезанной строки
     * @param string $mode Режим обрезки: auto - до ближайщего пробела от указанной длины, left - до пробела чье положение меньще указанной длины, right - до пробела чье положение больще указанной длины
     *
     * @return string
     */
    public static function crop($text, $length = 0, $etc = '', $mode = 'auto')
    {
        $text = self::strip_tags($text);

        $text = preg_replace('/\s+/', ' ', $text);

        if ( !$length || mb_strlen($text) <= $length ) {
            return $text;
        }

        $text_length = mb_strlen($text);

        $pos_max = mb_strpos($text, ' ', $length) ?: $text_length;

        $offset = 0;

        while ( $offset < $pos_max ) {
            $min = mb_strpos($text, ' ', $offset) ?: $text_length;

            if ( $min !== false && $min < $pos_max ) {
                $pos_min = $min;
            }

            $offset = $min;
        }

        if ( ($mode == 'auto' && ($pos_max - $length) < ($length - $pos_min)) || ($mode == 'right') ) {
            $text = mb_substr($text, 0, $pos_max);
        }
        else {
            $text = mb_substr($text, 0, $pos_min);
        }

        return $text . ($text_length > mb_strlen($text) ? $etc : '');
    }

    /**
     * Вырезает из строки CSS/JS-комментарии, табуляции, переносы строк и лишние пробелы
     *
     * @param string $string
     *
     * @return string
     */
    public static function compress($string)
    {
        $string = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $string);
        $string = str_replace(array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $string);

        return $string;
    }

}
