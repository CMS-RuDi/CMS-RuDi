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
        return self::getInstance()->load('components/' . $name);
    }

    /**
     * Загружает языковой файл указанного модуля
     * @param string $name
     */
    public static function loadModuleLang($name)
    {
        return self::getInstance()->load('modules/' . $name);
    }

    /**
     * Загружает языковой файл указанного модуля
     * @param string $name
     */
    public static function loadPluginLang($name)
    {
        return self::getInstance()->load('plugins/' . $name);
    }

    //========================================================================//

    public function load($file)
    {
        $this->loadFromPath(PATH . '/languages/%lang%/' . $file . '.php');
        return $this;
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
            $data = \cms\helper\files::getContent($lang_file_url);

            if ( !empty($data) ) {
                $lang = json_decode($data, true);

                if ( is_array($lang) ) {
                    $this->setLangs($lang);

                    return true;
                }
            }
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

    public function vsprintf($name, ...$params)
    {
        $string = $this->get($name);

        if ( !empty($string) ) {
            if ( count($params) == 1 && is_array($params[0]) ) {
                $params = $params[0];
            }

            return sprintf($string, ...$params);
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

    /**
     * Добавляет указанные языковые переменные
     * @global array $_LANG
     * @param array $lang
     * @return boolean
     */
    public function setLangs($lang)
    {
        if ( is_array($lang) ) {
            global $_LANG;

            foreach ( $lang as $k => $v ) {
                $_LANG[mb_strtoupper($k)] = $v;

                $this->set($k, $v);
            }

            return true;
        }

        return false;
    }

    /**
     * Возвращает содержимое указанного файла из папки letters
     * @param string $file название файла письма
     * @return boolean|string
     */
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

    /**
     * Транслитирует строку и обрезает в соответствии с настройками для использования в качестве seolink
     * @param string $string строка для транслитерации
     * @param string $translit заменить буквы национального алфавита указанного языка на транслит или нет
     * @param string $lang язык для транслитерации, если не указан берется из настроек системы
     * @return string
     */
    public static function slug($string, $translit = true, $lang = false)
    {
        $config_lang = \cmsConfig::getConfig('lang');

        $lang = $lang ? $lang : $config_lang;

        $class_name = '\\languages\\' . $lang . '\\' . $lang;

        if ( class_exists($class_name) && method_exists($class_name, 'slug') ) {
            $string = $class_name::slug($string, $translit);
        }
        else if ( $lang != $config_lang ) {
            $string = self::slug($string, $translit, $config_lang);
        }
        else if ( $lang != 'ru' ) {
            $string = self::slug($string, $translit, 'ru');
        }

        if ( !\cmsConfig::getConfig('seo_url_count') ) {
            return $string;
        }
        else {
            return mb_substr($string, 0, \cmsConfig::getConfig('seo_url_count'));
        }
    }

    /**
     * Возвращает транслитированную строку
     * @param string $string строка для транслитерации
     * @param string $lang язык для транслитерации, если не указан берется из настроек системы
     * @return string
     */
    public static function translit($string, $lang = false)
    {
        $config_lang = \cmsConfig::getConfig('lang');

        $lang = $lang ? $lang : $config_lang;

        $class_name = '\\languages\\' . $lang . '\\' . $lang;

        if ( class_exists($class_name) && method_exists($class_name, 'translit') ) {
            return $class_name::translit($string);
        }
        else if ( $lang != $config_lang ) {
            return self::translit($string, $config_lang);
        }
        else if ( $lang != 'ru' ) {
            return self::translit($string, 'ru');
        }
    }

    public function setLocale()
    {
        $class_name = '\\languages\\' . $this->lang . '\\' . $this->lang;

        if ( class_exists($class_name) && method_exists($class_name, 'setLocale') ) {
            return $class_name::setLocale();
        }
        else if ( $this->lang != ru ) {
            return \languages\ru\ru::setLocale();
        }
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
