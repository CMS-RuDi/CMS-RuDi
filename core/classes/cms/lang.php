<?php

namespace cms;

class lang
{

    use \Singeltone;

    private $lang           = 'ru';
    protected static $_LANG = [];

    protected function __construct()
    {
        $this->lang = \cmsConfig::getConfig('lang');
        $this->load('lang');
    }

    //========================================================================//

    /**
     * Загружает языковой файл указанного компонента
     *
     * @param string $name
     *
     * @return $this
     */
    public static function loadComponentLang($name)
    {
        return self::getInstance()->load('components/' . $name);
    }

    /**
     * Загружает языковой файл указанного модуля
     *
     * @param string $name
     *
     * @return $this
     */
    public static function loadModuleLang($name)
    {
        return self::getInstance()->load('modules/' . $name);
    }

    /**
     * Загружает языковой файл указанного модуля
     *
     * @param string $name
     *
     * @return $this
     */
    public static function loadPluginLang($name)
    {
        return self::getInstance()->load('plugins/' . $name);
    }

    /**
     * Зайгружает языковой файл указанного шаблона
     *
     * @param string $name
     *
     * @return $this
     */
    public static function loadTemplateLang($name)
    {
        return self::getInstance()->load('templates/' . $name);
    }

    //========================================================================//

    /**
     * Зайгружает указанный языковой файл
     *
     * @param string $file
     *
     * @return $this
     */
    public function load($file)
    {
        $this->loadFromPath(PATH . '/languages/%lang%/' . $file . '.php');

        return $this;
    }

    /**
     * Загружает языковой файл по указанному пути
     *
     * @param string $file_path
     */
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

        return $this;
    }

    /**
     * Загружает удаленный языковой файл в формате json по указанному url
     *
     * @param type $lang_file_url
     *
     * @return boolean true если удалось загрузить файл и false в случае ошибки
     */
    public function remoteLoad($lang_file_url)
    {
        if ( !empty($lang_file_url) ) {
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

    /**
     * Возвращает указанную языковую строку, в которой произведена процедура
     * поиска и замены $params где для поиска используется %ключ% а для замены значение
     *
     * @param string $name
     * @param array $params
     *
     * @return false|string
     */
    public function replace($name, $params = [])
    {
        if ( isset(self::$_LANG[$name]) ) {
            $string = self::$_LANG[$name];

            if ( !empty($params) && is_array($params) ) {
                $find    = $replace = [];

                foreach ( $params as $k => $v ) {
                    $find[]    = '%' . $k . '%';
                    $replace[] = $v;
                }

                $string = str_replace($find, $replace, $string);
            }

            return $string;
        }

        return false;
    }

    /**
     * Возвращает отформатированную строку
     * подробнее http://php.net/manual/ru/function.sprintf.php
     *
     * @param string $name
     * @param mixed ...$params
     *
     * @return false|string
     */
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

    /**
     * Возвращает идентификатор текущего языка
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Возвращает локализованную строку
     *
     * @param string $name
     *
     * @return string
     */
    public function get(...$params)
    {
        $name = mb_strtolower(implode('', $params));

        return isset(self::$_LANG[$name]) ? self::$_LANG[$name] : '';
    }

    /**
     * Аналогична get, но в отличии от нее если строка не найдена возвращает $name
     *
     * @see self::get()
     */
    public function e(...$params)
    {
        $key = mb_strtolower(implode('', $params));

        return isset(self::$_LANG[$key]) ? self::$_LANG[$key] : implode('', $params);
    }

    /**
     * Возвращает локализованную строку у которой первая буква переведена в верхний
     * регистр
     *
     * @param string $name
     *
     * @return string
     */
    public function ucf(...$params)
    {
        return ucfirst($this->e(...$params));
    }

    /**
     * Возвращает локализованную строку у которой первая буква переведена в нижний
     * регистр
     *
     * @param string $name
     *
     * @return string
     */
    public function lcf(...$params)
    {
        return lcfirst($this->e(...$params));
    }

    /**
     * Возвращает локализованную строку в верхнем регистре
     *
     * @param string $name
     *
     * @return string
     */
    public function uc(...$params)
    {
        return mb_strtoupper($this->e(...$params));
    }

    /**
     * Возвращает локализованную строку в нижнем регистре
     *
     * @param string $name
     *
     * @return string
     */
    public function lc(...$params)
    {
        return mb_strtolower($this->e(...$params));
    }

    /**
     * Устанавливает/изменяет локализованную строку
     *
     * @param string $name
     * @param string $value
     */
    public function set($name, $value)
    {
        self::$_LANG[mb_strtolower($name)] = $value;
    }

    /**
     * Добавляет указанные языковые переменные
     *
     * @global array $_LANG
     * @param array $lang
     *
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
     *
     * @param string $file название файла письма
     *
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
     *
     * @param string $string строка для транслитерации
     * @param string $translit заменить буквы национального алфавита указанного языка на транслит или нет
     * @param string $lang язык для транслитерации, если не указан берется из настроек системы
     *
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
     *
     * @param string $string строка для транслитерации
     * @param string $lang язык для транслитерации, если не указан берется из настроек системы
     *
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
