<?php
class autoload
{
    private static $classes = array(
        'cmsCore'            => '../cms.php',
        'cmsAdmin'           => '../cms_admin.php',
        'tplMainClass'       => '../tpl_classes/tplMainClass.php',
        'phpTpl'             => '../tpl_classes/phpTpl.php',
        'smartyTpl'          => '../tpl_classes/smartyTpl.php',
        'fenomTpl'           => '../tpl_classes/fenomTpl.php',
        'cmsActions'         => 'actions.class.php',
        'cmsBlogs'           => 'blog.class.php',
        'cmsConfig'          => 'config.class.php',
        'cmsCron'            => 'cron.class.php',
        'cmsDatabase'        => 'db.class.php',
        'cmsForm'            => 'form.class.php',
        'cmsFormGen'         => 'formgen.class.php',
        'cmsgeo'             => 'geo.class.php',
        'idna_convert'       => 'idna_convert.class.php',
        'Jevix'              => 'jevix.class.php',
        'cmsPage'            => 'page.class.php',
        'cmsPhoto'           => 'photo.class.php',
        'cmsPlugin'          => 'plugin.class.php',
        'cmsUploadPhoto'     => 'upload_photo.class.php',
        'cmsUser'            => 'user.class.php',
        'cmsBilling'         => 'billing.class.php',
        'autokeyword'        => '../../includes/keywords.inc.php',
        'CCelkoNastedSet'    => '../../includes/nestedsets.php',
        'bbcode'             => '../../includes/bbcode/bbcode.lib.php',
        'Lingua_Stem_Ru'     => '../../includes/stemmer/stemmer.php',
        'Spyc'               => '../../includes/spyc/spyc.php',
        'Smarty'             => '../../includes/smarty/libs/Smarty.class.php',
        'lastRSS'            => '../../includes/rss/lastRSS.php',
        'PHPMailer'          => '../../includes/phpmailer/class.phpmailer.php',
        'GeSHi'              => '../../includes/geshi/geshi.php',
        'Spreadsheet_Excel_Reader'  => '../../includes/excel/excel_reader2.php'
    );
    
    private static $dirs = array();
    
    public function autoload($class = false)
    {
        if (empty($class)) {
            return false;
        }
        
        $class = str_replace('\\', '/', $class);
        
        $required = false;
        
        if (isset(self::$classes[$class])) {
            if (file_exists(self::$classes[$class]))
            {
                require_once(self::$classes[$class]);
                $required = true;
            }
            else
            {
                foreach (self::$dirs as $dir) {
                    if (file_exists($dir .'/'. self::$classes[$class])) {
                        require_once($dir .'/'. self::$classes[$class]);
                        $required = true;
                        break;
                    }
                }
            }
        }
        
        if (!$required) {
            foreach (self::$dirs as $dir) {
                if (file_exists($dir .'/'. $class .'.php')) {
                    require_once($dir .'/'. $class .'.php');
                    $required = true;
                    break;
                }

                if (file_exists($dir .'/'. $class .'.class.php')) {
                    require_once($dir .'/'. $class .'.class.php');
                    $required = true;
                    break;
                }
            }
        }
        
        if (!$required && strstr($class, 'cms_model_') && file_exists(__DIR__ .'/../../components/'. str_replace('cms_model_', '', $class) .'/model.php'))
        {
            require_once(__DIR__ .'/../../components/'. str_replace('cms_model_', '', $class) .'/model.php');
            $required = true;
        }
        elseif (!$required && substr($class, 0, 2) == 'p_' && file_exists(__DIR__ .'/../../plugins/'. $class .'/plugin.php'))
        {
            require_once(__DIR__ .'/../../plugins/'. $class .'/plugin.php');
            $required = true;
        }

        return $required ? true : false;
    }
    
    /**
     * Добавляет связь названия класса и его местоположения
     * @param string $class - название класса
     * @param string $path - путь от папки /core/classes например если некий класс blabla лежит в папке /lib/class/blabla.class.php от корня сайта то путь нужно указать такой ../../lib/class/blabla.class.php
     */
    public static function add($class, $path)
    {
        self::$classes[$class] = $path;
    }
    
    /**
     * Добавляет новую директорию для поиска классов
     * @param string $path - абсолютный путь до директории
     */
    public static function addDir($path)
    {
        $path = rtrim($path);
        
        if (!in_array($path, self::$dirs)) {
            self::$dirs[] = $path;
        }
    }
}

autoload::addDir(__DIR__);

spl_autoload_register(array(new autoload(), 'autoload'));