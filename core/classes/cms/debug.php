<?php

namespace cms;

class debug
{

    const DECIMALS = 5;

    /**
     * Отладочная информация
     * @var array
     */
    protected static $debug = [
        'db'       => [],
        'events'   => [],
        'modules'  => [],
        'notice'   => [],
        'includes' => []
    ];

    /**
     * Общее время работы
     * @var array
     */
    protected static $total_time = [];

    /**
     * Массив с таймерами
     * @var array
     */
    protected static $timer = [];

    /**
     * Запускает таймер события и возвращает его идентификатор
     * @return string
     */
    public static function startTimer($key = null)
    {
        $time = microtime(true);

        if ( empty($key) ) {
            $key = substr(md5($time . mt_rand(0, 1000000)), 0, 8);
        }

        self::$timer[$key] = $time;

        return $key;
    }

    /**
     * Возвращает время с момента запуска таймера
     * @param string $key
     * @return float
     */
    public static function getTime($key, $decimals = self::DECIMALS)
    {
        $time = 0;

        if ( isset(self::$timer[$key]) ) {
            $time = number_format((microtime(true) - self::$timer[$key]), $decimals);
        }

        return $time;
    }

    /**
     * Сохраняет отладочную информацию
     * @param string $name
     * @param string $tkey
     * @param string $text
     */
    public static function setDebugInfo($name, $text = false, $tkey = false, $offset = 2)
    {
        $backtrace = debug_backtrace();

        while ( ($backtrace && !isset($backtrace[0]['line']) ) ) {
            array_shift($backtrace);
        }

        if ( !isset($backtrace[$offset]) ) {
            $offset -= 1;
        }

        $_offset = $offset + 1;

        $call = $backtrace[$offset];

        if ( empty($call['file']) ) {
            $_offset = $offset;
            $call    = $backtrace[$offset - 1];
        }

        if ( isset($backtrace[$_offset]) ) {
            if ( isset($backtrace[$_offset]['class']) ) {
                $call['function'] = $backtrace[$_offset]['class'] . $backtrace[$_offset]['type'] . $backtrace[$_offset]['function'] . '()';
            }
            else {
                $call['function'] = $backtrace[$_offset]['function'] . '()';
            }
        }
        else {
            if ( isset($backtrace[$offset]['class']) ) {
                $call['function'] = $backtrace[$offset]['class'] . $backtrace[$offset]['type'] . $backtrace[$offset]['function'] . '()';
            }
            elseif ( isset($backtrace[$offset]['function']) ) {
                $call['function'] = $backtrace[$offset]['function'] . '()';
            }
            else {
                $call['function'] = '';
            }
        }

        $src = str_replace(PATH, '', $call['file']) . ' => ' . $call['line'] . ($call['function'] ? ' => ' . $call['function'] : '');

        $time = false;
        $tkey = empty($tkey) ? $name : $tkey;

        if ( !empty($tkey) ) {
            $time = self::getTime($tkey);

            if ( !isset(self::$total_time[$name]) ) {
                self::$total_time[$name] = 0;
            }

            self::$total_time[$name] += $time;
        }

        self::$debug[$name][] = [
            'src'  => $src,
            'text' => $text,
            'time' => $time
        ];
    }

    /**
     * Возвращает всю отладочную информацию по указанному разделу
     * @param string $name
     * @return array|false
     */
    public static function getDebugInfo($name = '')
    {
        self::loadIncludedFiles();

        if ( isset(self::$debug[$name]) ) {
            return self::$debug[$name];
        }

        return self::$debug;
    }

    public static function getDebagTargets()
    {
        global $_LANG;

        $_targets = array_keys(self::$debug);

        $targets = [];

        foreach ( $_targets as $target ) {
            $targets[$target] = [
                'title' => isset($_LANG['DEBUG_TAB_' . strtoupper($target)]) ? $_LANG['DEBUG_TAB_' . strtoupper($target)] : 'DEBUG_TAB_' . strtoupper($target),
                'count' => count(self::$debug[$target])
            ];
        }

        return $targets;
    }

    /**
     * Возвращает общее время выполнения выполнения для указанных разделов
     * @param string $name
     * @return float
     */
    public static function getTotalRunTime($name = '')
    {
        if ( !empty($name) ) {
            if ( isset(self::$total_time[$name]) ) {
                return self::$total_time[$name];
            }

            return false;
        }

        return self::$total_time;
    }

    /**
     * Обработчик ошибок, сохраняет список ошибок для удобного вывода пользователю
     * @param integer $errno
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $errortype = [
            E_WARNING     => 'E_WARNING',
            E_NOTICE      => 'E_NOTICE',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT      => 'E_STRICT',
            E_DEPRECATED  => 'E_DEPRECATED',
        ];

        if ( !isset($errortype[$errno]) ) {
            return false;
        }

        self::$debug['notice'][] = [
            'src'  => $errfile . ' - LINE ' . $errline,
            'text' => $errortype[$errno] . PHP_EOL . $errstr,
            'time' => false
        ];

        return true;
    }

    public static function loadIncludedFiles()
    {
        $_files = get_included_files();

        foreach ( $_files as $path ) {
            self::$debug['includes'][] = array(
                'src'  => str_replace(PATH, '', $path),
                'text' => '',
                'time' => false,
            );
        }
    }

}
