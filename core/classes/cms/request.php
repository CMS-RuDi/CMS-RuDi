<?php

namespace cms;

class request
{

    use \Singeltone;

    const CTX_AUTO_DETECT = 0;
    const CTX_STANDARD    = 1;
    const CTX_INTERNAL    = 2;
    const CTX_AJAX        = 3;

    protected $context;
    protected static $request     = [];
    protected static $device_type = null;
    public static $device_types   = [ 'desktop', 'mobile', 'tablet' ];

    //========================================================================//

    public static function getScheme()
    {
        $cscheme = \cmsConfig::getConfig('scheme');

        if ( PHP_SAPI == 'cli' ) {
            return !empty($cscheme) ? $cscheme : 'http';
        }

        if ( isset($_SERVER['HTTP_SCHEME']) ) {
            $scheme = $_SERVER['HTTP_SCHEME'];
        }
        else if ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }
        else if ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443 ) {
            $scheme = 'https';
        }
        else {
            $scheme = 'http';
        }

        if ( $scheme != 'https' && $scheme != 'http' ) {
            $scheme = 'http';
        }

        if ( $cscheme == 'https' && $cscheme != $scheme ) {
            \cmsCore::redirect($cscheme . '://' . self::getHost() . '/' . ltrim($_SERVER['REQUEST_URI'], '/'), 301);
        }

        return $scheme;
    }

    public static function getHost()
    {
        // если вызван из командной строки
        // ожидаем параметр с именем домена, например команда для CRON
        // php -f /path_to_site/cron.php site.ru
        if ( PHP_SAPI == 'cli' ) {
            global $argv;
            return isset($argv[1]) ? $argv[1] : '';
        }

        // если интернационализованный домен
        if ( mb_strpos($_SERVER['HTTP_HOST'], 'xn--') !== false ) {
            $IDN = new \idna_convert();

            return $IDN->decode($_SERVER['HTTP_HOST']);
        }

        return $_SERVER['HTTP_HOST'];
    }

    //========================================================================//

    /**
     * Устанавливает контекст
     * @param int $context Контекст (если не указан, определяется автоматически)
     */
    public function setContext($context = self::CTX_AUTO_DETECT)
    {
        if ( $context == self::CTX_AUTO_DETECT ) {
            $this->context = $this->detectContext();
        }
        else {
            $this->context = $context;
        }
    }

    /**
     * Определяет контекст текущего запроса (стандартный или ajax)
     * @return int
     */
    protected function detectContext()
    {
        if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) {
            return self::CTX_AJAX;
        }
        else {
            return self::CTX_STANDARD;
        }
    }

    /**
     * Возвращает текущий контекст
     * @return int
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Возвращает true, если запрос вызван через URL
     * @return bool
     */
    public function isStandard()
    {
        return ($this->context == self::CTX_STANDARD);
    }

    /**
     * Возвращает true, если запрос вызван другим контроллером
     * @return bool
     */
    public function isInternal()
    {
        return ($this->context == self::CTX_INTERNAL);
    }

    /**
     * Возвращает true, если запрос вызван через AJAX
     * @return bool
     */
    public function isAjax()
    {
        return ($this->context == self::CTX_AJAX);
    }

    //========================================================================//

    /**
     * Возврает новый экземпляр класса
     * @param array $request
     * @return \self
     */
    public static function init()
    {
        return new self();
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return isset(self::$request[$name]) ? self::$request[$name] : false;
    }

    public function __unset($name)
    {
        $this->delete($name);
    }

    /**
     * Добавляет переменную во внутренний список
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        self::$request[$name] = $value;
    }

    /**
     * Удаляет переменную из внутреннего списка
     * @param type $name
     */
    public function delete($name)
    {
        if ( isset(self::$request[$name]) ) {
            unset(self::$request[$name]);
        }
    }

    /**
     * Добавляет массив переменных во внутренний список
     * @param string $name
     * @param mixed $value
     */
    public function addData($data)
    {
        if ( !is_array($data) ) {
            $data = [ $data ];
        }

        foreach ( $data as $k => $v ) {
            self::$request[$k] = $v;
        }
    }

    /**
     * Устанавливает список переменных
     * @param array $data
     */
    public function setData($data)
    {
        self::$request = $data;
    }

    /**
     * Очищает внутренний список
     */
    public function clear()
    {
        self::$request = [];
    }

    //==========================================================================

    /**
     * Проверяет присутствует ли указанная переменная в полученных данных
     * @param string $var
     * @param string $r
     * @return boolean
     */
    public function has($name, $r = 'request', $only_real_request = true)
    {
        $r = mb_strtolower($r);

        switch ( $r ) {
            case 'post': $result = isset($_POST[$name]);
            case 'get': $result = isset($_GET[$name]);
            default: $result = isset($_REQUEST[$name]);
        }

        if ( $only_real_request !== true && $result !== true && !empty(self::$request) ) {
            $result = isset(self::$request[$name]);
        }

        return $result;
    }

    /**
     * Получает в соответствии с заданным типом переменную $value из $_REQUEST или из заданного массива данных если такой задан
     * @param string $name название переменной
     * @param string $type тип bool (boolean) | int (integer) | float | ip | str (string) | html | email | email_regex | array | array_int | array_str | массив допустимых значений
     * @param string $default значение по умолчанию
     * @param string $r Откуда брать значение get | post | request
     * @param array $options массив опций и флагов для функции filter_var
     * @return mixed
     */
    public function get($name, $type = 'str', $default = false, $r = 'request', $options = [])
    {
        $r = mb_strtolower($r);

        switch ( $r ) {
            case 'post':
                $value = isset($_POST[$name]) ? $_POST[$name] : null;
                break;
            case 'get':
                $value = isset($_GET[$name]) ? $_GET[$name] : null;
                break;
            default:
                $value = isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
                break;
        }

        if ( $value === null && !empty(self::$request) ) {
            $value = isset(self::$request[$name]) ? self::$request[$name] : null;
        }

        if ( $value !== null ) {
            return self::cleanVar($value, $type, $default, $options);
        }
        else {
            return $default;
        }
    }

    /**
     * Формирует массив данных из $_REQUEST в соответствии с параметрами
     * @param array $types массив, ключами которого являются названия полей в базе данных,
     * а значения его - массив параметров входной переменной
     * @return array
     */
    public function getArrayFromRequest($types)
    {
        $items = [];

        foreach ( $types as $field => $type_list ) {
            $items[$field] = $this->get($type_list[0], $type_list[1], $type_list[2], !empty($type_list[4]) ? $type_list[4] : 'request', !empty($type_list[5]) ? $type_list[5] : []);

            // если передана функция обработки (ее название), обрабатываем
            // полная поддержка анонимных функций невозможна из-за поддержки php 5.2.x
            if ( isset($type_list[3]) ) {
                // если пришел массив, считаем что передан объект/название класса и метод
                if ( is_array($type_list[3]) ) {
                    if ( class_exists($type_list[3][0]) && method_exists($type_list[3][0], $type_list[3][1]) ) {
                        $items[$field] = call_user_func($type_list[3], $items[$field]);
                    }
                }

                // в остальных случаях считаем, что пришло название функции
                elseif ( function_exists($type_list[3]) ) {
                    $items[$field] = call_user_func($type_list[3], $items[$field]);
                }
            }
        }

        return $items;
    }

    /**
     * Возвращает массив с данными переданными методом json post
     * @return array | bool
     */
    public static function getJsonPostData()
    {
        $data = file_get_contents('php://input');

        if ( !empty($data) ) {
            return json_decode($data);
        }

        return false;
    }

    /**
     * Смотрите описание get
     */
    public static function cleanVar($value, $type = 'str', $default = false, $options = [])
    {
        // массив возможных параметров
        if ( is_array($type) ) {
            if ( in_array($value, $type) ) {
                return self::strClear((string) $value);
            }
            else {
                return $default;
            }
        }

        $options = [
            'default' => $default
        ];

        switch ( $type ) {
            case 'bool':
            case 'boolean':
                return (boolean) filter_var($value, FILTER_VALIDATE_BOOLEAN, $options);
            case 'float':
                return (float) filter_var($value, FILTER_VALIDATE_FLOAT, $options);
            case 'int':
            case 'integer':
                return (int) filter_var($value, FILTER_VALIDATE_INT, $options);
            case 'ip':
                return (string) filter_var($value, FILTER_VALIDATE_IP, $options);
            case 'str':
            case 'string':
                return empty($value) ? (string) $default : self::strClear($value, isset($options['flags']) ? $options['flags'] : null);
            case 'email':
                return (string) filter_var($value, FILTER_VALIDATE_EMAIL, $options);
            case 'email_regex':
                return preg_match("/^([a-z0-9\._-]+)@([a-z0-9\._-]+)\.([a-z]{2,6})$/i", $value) ? $value : $default;
            case 'html':
                return (string) (!empty($value) ? $value : $default);
            case 'array':
                return is_array($value) ? $value : $default;
            case 'array_int':
                if ( is_array($value) ) {
                    $arr = [];

                    foreach ( $value as $k => $i ) {
                        $i = filter_var($i, FILTER_VALIDATE_INT, [ 'default' => false ]);

                        if ( $i !== false ) {
                            $arr[$k] = $i;
                        }
                    }

                    return $arr;
                }
                else {
                    return $default;
                }
            case 'array_str':
                if ( is_array($value) ) {
                    $arr = [];

                    foreach ( $value as $k => $s ) {
                        $arr[$k] = self::strClear($s);
                    }

                    return $arr;
                }
                else {
                    return $default;
                }
        }
    }

    public static function strClear($input, $flags = null)
    {
        if ( is_array($input) ) {
            foreach ( $input as $key => $string ) {
                $value[$key] = self::strClear($string, $flags);
            }

            return $value;
        }

        return trim(filter_var($input, FILTER_SANITIZE_STRING, $flags !== null ? $flags : !FILTER_FLAG_STRIP_LOW));
    }

    //========================================================================//

    /**
     * Проверяет наличие загруженного файла
     * @param string $name
     * @return boolean
     */
    public function hasFile($name)
    {
        return empty($_FILES[$name]['size']) ? false : true;
    }

    /**
     * Возвращает содержимое загруженного файла или файлов
     * @param string $name
     * @param boolean $multiple определяет возвращать содержимое всех файлов или только первого если загружено несколько файлов
     * @param function $fn функция которая будет применена к содержимому каждого файла
     * @return boolean|array|string
     */
    public function getFile($name, $multiple = false, $fn = null)
    {
        if ( $this->hasFile($name) ) {
            if ( is_array($_FILES[$name]['tmp_name']) ) {
                if ( $multiple ) {
                    $contents = [];

                    foreach ( $_FILES[$name]['error'] as $k => $v ) {
                        if ( $v === UPLOAD_ERR_OK && is_uploaded_file($_FILES[$name]['tmp_name'][$k]) ) {
                            $content = file_get_contents($_FILES[$name]['tmp_name'][$k]);

                            if ( !empty($fn) ) {
                                $content = $fn($content);
                            }

                            $contents[] = $content;
                        }
                    }

                    return $contents;
                }

                $src = $_FILES[$name]['tmp_name'][0];
            }
            else {
                $src = $_FILES[$name]['tmp_name'];
            }

            if ( is_uploaded_file($src) ) {
                $content = file_get_contents($src);

                if ( !empty($fn) ) {
                    $content = $fn($content);
                }

                return $content;
            }
        }

        return false;
    }

    /**
     * Перемещает загруженный файл или файлы в указанную папку с указанным названием
     * @param string $name
     * @param string $folder
     * @param string $file_name
     * @param boolean $multiple
     * @return boolean|array|string
     */
    public function moveFile($name, $folder, $file_name = false, $multiple = false)
    {
        $folder = rtrim($folder, '/\\');

        if ( $this->hasFile($name) ) {
            if ( is_array($_FILES[$name]['tmp_name']) ) {
                if ( $multiple ) {
                    $files = [];

                    foreach ( $_FILES[$name]['error'] as $k => $v ) {
                        if ( $v === UPLOAD_ERR_OK && is_uploaded_file($_FILES[$name]['tmp_name'][$k]) ) {
                            $fname = basename($_FILES[$name]['name'][$k]);

                            $dist = realpath($folder . '/' . (!empty($file_name) ? $k . '_' . $file_name : $fname));

                            move_uploaded_file($_FILES[$name]['tmp_name'][$k], $dist);

                            $files[] = $dist;
                        }
                    }

                    return $files;
                }

                $src   = $_FILES[$name]['tmp_name'][0];
                $fname = basename($_FILES[$name]['name'][0]);
            }
            else {
                $src   = $_FILES[$name]['tmp_name'];
                $fname = basename($_FILES[$name]['name']);
            }

            if ( is_uploaded_file($src) ) {
                $dist = realpath($folder . '/' . (!empty($file_name) ? $file_name : $fname));

                move_uploaded_file($src, $dist);

                return $dist;
            }
        }

        return false;
    }

    //========================================================================//

    protected static function loadDeviceType()
    {
        $device_type = (string) cookie::get('device_type');

        if ( !$device_type || !in_array($device_type, self::$device_types, true) ) {
            $detect = new \Mobile_Detect();

            $device_type = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'mobile') : 'desktop');

            cookie::set('device_type', $device_type, 31536000); // на 1 год
        }

        self::$device_type = $device_type;
    }

    public static function getDeviceType()
    {
        if ( self::$device_type === null ) {
            self::loadDeviceType();
        }

        return self::$device_type;
    }

}
