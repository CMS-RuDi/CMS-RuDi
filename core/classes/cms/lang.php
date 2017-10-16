<?php

namespace cms;

class lang
{

    use \Singeltone;

    protected $lang         = 'ru';
    protected static $_LANG = [];

    protected function __construct()
    {
        $this->lang = \cmsConfig::getConfig('lang');
        $this->load('lang');
    }

    //========================================================================//

    /**
     * Загружает языковой файл указанного компонента
     * @param string $name
     */
    public static function loadComponentLang($name)
    {
        self::getInstance()->load('components/' . $name);
    }

    /**
     * Загружает языковой файл указанного модуля
     * @param string $name
     */
    public static function loadModuleLang($name)
    {
        self::getInstance()->load('modules/' . $name);
    }

    /**
     * Загружает языковой файл указанного модуля
     * @param string $name
     */
    public static function loadPluginLang($name)
    {
        self::getInstance()->load('plugins/' . $name);
    }

    //========================================================================//

    public function load($file)
    {
        $this->loadFromPath(PATH . '/languages/%lang%/' . $file . '.php');
    }

    public function loadFromPath($file_path)
    {
        $lang  = $_LANG = [];

        if ( file_exists(str_replace('%lang%', $this->lang, $file_path)) ) {
            include_once(str_replace('%lang%', $this->lang, $file_path));
        }
        else if ( $this->lang != 'ru' && file_exists(str_replace('%lang%', 'ru', $file_path)) ) {
            include_once(str_replace('%lang%', 'ru', $file_path));
        }

        $this->setLangs(!empty($lang) ? $lang : $_LANG);
    }

    public function remoteLoad($lang_file_url)
    {
        if ( !empty($this->lang_uri) && !empty($lang_file_url) ) {
            $inCurl = \cms\curl::init()->request('get', $lang_file_url)->execute();

            $lang = $inCurl->json();

            if ( is_array($lang) ) {
                $this->setLangs($lang);
            }

            return true;
        }

        return false;
    }

    public function replace($name, $params = [])
    {
        if ( isset(self::$_LANG[$name]) ) {
            $string = self::$_LANG[$name];

            if ( !empty($params) && is_array($params) ) {
                $find    = $replace = [];

                foreach ( $vars as $k => $v ) {
                    $find[]    = '%' . $k . '%';
                    $replace[] = $v;
                }

                $string = str_replace($find, $replace, $string);
                $string = vsprintf($lang_var, $params);
            }

            return $string;
        }

        return false;
    }

    public function vsprintf($name, $params)
    {
        $string = $this->get($name);

        if ( !empty($string) ) {
            if ( !is_array($params) ) {
                $params = [ $params ];
            }

            return vsprintf($string, $params);
        }

        return false;
    }

    public function get($name)
    {
        $name = mb_strtolower($name);

        return isset(self::$_LANG[$name]) ? self::$_LANG[$name] : '';
    }

    public function set($name, $value)
    {
        self::$_LANG[mb_strtolower($name)] = $value;
    }

    public function setLangs($lang)
    {
        if ( is_array($lang) ) {
            global $_LANG;

            foreach ( $lang as $k => $v ) {
                $_LANG[$k] = $v;

                $this->set($k, $v);
            }

            return true;
        }

        return false;
    }

    public function getLetter($file)
    {
        $letter_file = PATH . '/languages/' . $this->lang . '/letters/' . $file . '.txt';

        if ( !file_exists($letter_file) ) {
            $letter_file = PATH . '/languages/ru/letters/' . $file . '.txt';
        }

        if ( !file_exists($letter_file) ) {
            return false;
        }

        return file_get_contents($letter_file);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function __isset($name)
    {
        return isset(self::$_LANG[$name]);
    }

}
