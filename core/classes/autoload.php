<?php

/**
 * @author DS Soft <admin@ds-soft.ru>
 * @version 2.0.0
 * @package Classes
 */
class autoload
{

    /**
     * Ассоциативный массив где в качестве ключа используется пространство имен
     * класса а в качестве значения абсолюный путь к классу или относительный, относительно
     * одной из директорий добавленных методом autoload::addDir($dir);
     *
     * @var array
     */
    protected static $namespaces_map = [
        'Mso\\IdnaConvert' => PATH . '/includes/libs/IdnaConvert',
    ];

    /**
     * Ассоциативный массив где в качестве ключа используется название класса
     * а в качестве значения абсолюный путь к классу или относительный, относительно
     * одной из директорий добавленных методом autoload::addDir($dir);
     *
     * @var array
     */
    protected static $classes_map = [
        'cmsCore'                  => PATH . '/core/cms.php',
        'cmsAdmin'                 => PATH . '/core/cms_admin.php',
        'tplMainClass'             => PATH . '/core/tpl_classes/tplMainClass.php',
        'phpTpl'                   => PATH . '/core/tpl_classes/phpTpl.php',
        'smartyTpl'                => PATH . '/core/tpl_classes/smartyTpl.php',
        'fenomTpl'                 => PATH . '/core/tpl_classes/fenomTpl.php',
        'cmsActions'               => PATH . '/core/classes/actions.class.php',
        'cmsBlogs'                 => PATH . '/core/classes/blog.class.php',
        'cmsConfig'                => PATH . '/core/classes/config.class.php',
        'cmsCron'                  => PATH . '/core/classes/cron.class.php',
        'cmsDatabase'              => PATH . '/core/classes/db.class.php',
        'cmsForm'                  => PATH . '/core/classes/form.class.php',
        'cmsFormGen'               => PATH . '/core/classes/formgen.class.php',
        'cmsgeo'                   => PATH . '/core/classes/geo.class.php',
        'idna_convert'             => PATH . '/core/classes/idna_convert.class.php',
        'Jevix'                    => PATH . '/core/classes/jevix.class.php',
        'cmsPage'                  => PATH . '/core/classes/page.class.php',
        'cmsPhoto'                 => PATH . '/core/classes/photo.class.php',
        'cmsPlugin'                => PATH . '/core/classes/plugin.class.php',
        'cmsUploadPhoto'           => PATH . '/core/classes/upload_photo.class.php',
        'cmsUser'                  => PATH . '/core/classes/user.class.php',
        'cmsBilling'               => PATH . '/core/classes/billing.class.php',
        'translations'             => PATH . '/core/classes/translations.class.php',
        'autokeyword'              => PATH . '/includes/keywords.inc.php',
        'CCelkoNastedSet'          => PATH . '/includes/nestedsets.php',
        'bbcode'                   => PATH . '/includes/bbcode/bbcode.lib.php',
        'Lingua_Stem_Ru'           => PATH . '/includes/stemmer/stemmer.php',
        'Spyc'                     => PATH . '/includes/spyc/spyc.php',
        'SphinxClient'             => PATH . '/includes/sphinx/sphinxapi.php',
        'Smarty'                   => PATH . '/includes/smarty/libs/Smarty.class.php',
        'lastRSS'                  => PATH . '/includes/rss/lastRSS.php',
        'PHPMailer'                => PATH . '/includes/phpmailer/class.phpmailer.php',
        'GeSHi'                    => PATH . '/includes/geshi/geshi.php',
        'Spreadsheet_Excel_Reader' => PATH . '/includes/excel/excel_reader2.php'
    ];

    /**
     * Массив директорий в которых и относительно которых будут искаться классы
     *
     * @var array
     */
    protected static $dirs = [];

    /**
     * Ищет и подключает файл с указанным классом
     *
     * @param string $class_name Название класса
     *
     * @return boolean
     */
    public function load($class_name = false)
    {
        if ( empty($class_name) ) {
            return false;
        }

        $required = false;

        $this->loadByNamespace($class_name, $required);

        if ( !$required ) {
            $this->loadByClassesMap($class_name, $required);
        }

        if ( !$required ) {
            $this->loadByDirs($class_name, $required);
        }

        if ( !$required ) {
            $this->loadByRule($class_name, $required);
        }

        return $required ? true : false;
    }

    /**
     * Добавляет связь названия класса и его местоположения
     *
     * @param string $class Название класса
     * @param string $path Абсолютный путь к файлу класса
     */
    public static function add($class, $path)
    {
        self::$classes_map[$class] = $path;
    }

    /**
     * Добавляет новую директорию для поиска классов
     *
     * @param string $path Абсолютный путь до директории
     */
    public static function addDir($path)
    {
        $path = rtrim($path, '/\\ ');

        if ( !in_array($path, self::$dirs) ) {
            self::$dirs[] = $path;
        }
    }

    /**
     * Добавляет связку пространство имен => директория
     *
     * @param string $namespace Название пространства имен
     * @param string $path Абсолютный путь к директории с классами указанного пространства имен
     */
    public static function addNamespaceDir($namespace, $path)
    {
        self::$namespaces_map[trim($namespace, '\\ ')] = rtrim($path, '/\\ ');
    }

    //========================================================================//

    /**
     * Подключает файл класса, если указана папка для его имени пространства
     *
     * @param string $class_name Название класса
     * @param boolean $required Флаг указывающий что файл найден и подключен
     */
    protected function loadByNamespace($class_name, &$required)
    {
        if ( empty(self::$namespaces_map) ) {
            return;
        }

        $parts = explode('\\', $class_name);

        $namespace = '';

        while ( count($parts) > 1 ) {
            $namespace .= (empty($namespace) ? '' : '\\') . array_shift($parts);

            if ( isset(self::$namespaces_map[$namespace]) ) {
                $folder     = self::$namespaces_map[$namespace];
                $class_file = implode('/', $parts) . '.php';

                if ( file_exists($folder . '/' . $class_file) ) {
                    require_once($folder . '/' . $class_file);
                    $required = true;
                    break;
                }
            }
        }
    }

    /**
     * Подключает файл класса, если он указан в массива self::$classes_map
     *
     * @param string $class_name Название класса
     * @param boolean $required Флаг указывающий что файл найден и подключен
     */
    protected function loadByClassesMap($class_name, &$required)
    {
        if ( isset(self::$classes_map[$class_name]) && file_exists(self::$classes_map[$class_name]) ) {
            require_once(self::$classes_map[$class_name]);
            $required = true;
        }
    }

    /**
     * Подключает файл класса ищя его в добавленных для поиска классов директориях
     *
     * @param string $class_name Название класса
     * @param boolean $required Флаг указывающий что файл найден и подключен
     */
    protected function loadByDirs($class_name, &$required)
    {
        $class_file = str_replace('\\', '/', $class_name) . '.php';

        foreach ( self::$dirs as $dir ) {
            if ( file_exists($dir . '/' . $class_file) ) {
                require_once($dir . '/' . $class_file);
                $required = true;
                break;
            }
        }
    }

    /**
     * Подключает файл класса модели и плагина IstantCMS 1.x
     *
     * @param string $class_name Название класса
     * @param boolean $required Флаг указывающий что файл найден и подключен
     */
    protected function loadByRule($class_name, &$required = false)
    {
        $class_file = str_replace('\\', '/', $class_name);

        if ( strstr($class_file, 'cms_model_') && file_exists(__DIR__ . '/../../components/' . str_replace('cms_model_', '', $class_file) . '/model.php') ) {
            require_once(__DIR__ . '/../../components/' . str_replace('cms_model_', '', $class_file) . '/model.php');
            $required = true;
        }
        elseif ( substr($class_file, 0, 2) == 'p_' && file_exists(__DIR__ . '/../../plugins/' . $class_file . '/plugin.php') ) {
            require_once(__DIR__ . '/../../plugins/' . $class_file . '/plugin.php');
            $required = true;
        }
    }

}

autoload::addDir(PATH . '/core/classes');
autoload::addDir(PATH . '/includes/libs');
autoload::addDir(PATH);

spl_autoload_register(array( new autoload(), 'load' ));
