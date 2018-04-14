<?php

namespace cms\helper;

/**
 * @package Classes
 * @subpackage Helper
 */
class arrs
{

    /**
     * Возвращает значение ячейки массива по переданной вложенности $needle
     *
     * @param array|string $needle Путь до необходимого ключа, например key:subkey:subsubkey
     * @param array $haystack Массив, в котором ищем
     * @param string $delimiter Разделитель ключей в пути, если $needle строка
     *
     * @return mixed Значение или null, если ключ не найден
     */
    public static function getValueRecursive($needle, $haystack, $delimiter = ':')
    {
        if ( !is_array($haystack) ) {
            return null;
        }

        $name_parts = !is_array($needle) ? explode($delimiter, $needle) : $needle;

        foreach ( $name_parts as $name ) {
            if ( !is_array($haystack) || !array_key_exists($name, $haystack) ) {
                return null;
            }
            else {
                $haystack = $haystack[$name];

                if ( $haystack === null ) {
                    $haystack = false;
                }
            }
        }

        return $haystack;
    }

    /**
     * Устанавливает значение ключа массив по переданной вложенности ключей $path
     *
     * @param array|string $path Путь до необходимого ключа, например key:subkey:subsubkey
     * @param array $array Изменяемый массив
     * @param mixed $value Значение ключа
     * @param string $delimiter Разделитель ключей в пути, если $path строка
     *
     * @return mixed Возвращает изменённый массив $array
     */
    public static function setValueRecursive($path, $array, $value, $delimiter = ':')
    {
        $name_parts = !is_array($path) ? explode($delimiter, $path) : $path;

        self::setKeysListValue($name_parts, $array, $value);

        return $array;
    }

    /**
     * Изменяет переданный по ссылке массив $array добавляя в него значение $value
     * по переданной вложенности ключей $keys
     *
     * @param array $keys Путь до необходимого ключа в виде массива, например array('key', 'subkey', 'subsubkey')
     * @param array $array Изменяемый массив
     * @param mixed $value Значение ключа
     *
     * @return null
     */
    public static function setKeysListValue(&$keys, &$array, $value)
    {
        $key = array_shift($keys);

        if ( !isset($array[$key]) && !empty($keys) ) {
            $array[$key] = [];
        }
        else {
            $array[$key] = $value;
            return;
        }

        return self::setKeysListValue($keys, $array[$key], $value);
    }

    public static function unsetValueRecursive($path, $haystack, $delimiter = ':')
    {
        $name_parts = !is_array($path) ? explode($delimiter, $path) : $path;

        self::unsetKeysListValue($name_parts, $haystack);

        return $haystack;
    }

    public static function unsetKeysListValue(&$keys, &$haystack)
    {
        $key = array_shift($keys);

        if ( array_key_exists($key, $haystack) ) {
            if ( empty($keys) ) {
                unset($haystack[$key]);
                return true;
            }

            return self::unsetKeysListValue($keys, $haystack[$key]);
        }

        return false;
    }

    /**
     * Сортирует двумерный ассоциативный массив по полю (полям)
     *
     * $fields может содержать как просто имя поля для сортировки,
     * так и массив полей с направлениями сортировок, например:
     * array(array('by' => 'ordering', 'to' => 'asc'), array('by' => 'title', 'to' => 'desc'))
     *
     * @param array &$array
     * @param string | array $fields
     * @param string $direction
     *
     * @return boolean
     */
    public static function orderBy(&$array, $fields, $direction = 'asc')
    {
        if ( !$array ) {
            return false;
        }

        if ( is_string($fields) ) {
            $list = array( array(
                    'by' => $fields,
                    'to' => $direction
                ) );
        }
        else {
            $list = $fields;
        }

        $args = array();

        foreach ( $array as $k => $item ) {
            $key = 0;

            foreach ( $list as $order ) {
                $args[$key][$k] = $item[$order['by']];
                $key++;
                $args[$key]     = constant('SORT_' . strtoupper($order['to']));
                $key++;
            }
        }

        $args[] = &$array;

        return array_multisort(...$args);
    }

    public static function multiUnique($array)
    {
        $result = array_map('unserialize', array_unique(array_map('serialize', $array)));

        foreach ( $result as $key => $value ) {
            if ( is_array($value) ) {
                $result[$key] = self::multiUnique($value);
            }
        }

        return $result;
    }

}
