<?php

/*
 *                           InstantCMS v1.10.7
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2017
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class cmsConfig
{

    use \Singeltone;

    protected static $default_config = [
        'schema'                  => 'http',
        'host'                    => '',
        'sitename'                => '',
        'title_and_sitename'      => 1,
        'title_and_page'          => 1,
        'hometitle'               => '',
        'homecom'                 => '',
        'siteoff'                 => 0,
        'debug'                   => 0,
        'offtext'                 => '',
        'keywords'                => '',
        'metadesc'                => '',
        'lang'                    => 'ru',
        'is_change_lang'          => 0,
        'sitemail'                => '',
        'sitemail_name'           => '',
        'wmark'                   => 'watermark.png',
        'template'                => '_default_',
        'com_without_name_in_url' => 'content',
        'splash'                  => 0,
        'slight'                  => 1,
        'db_host'                 => '',
        'db_base'                 => '',
        'db_user'                 => '',
        'db_pass'                 => '',
        'db_prefix'               => 'cms',
        'db_users_table'          => 'cms_users',
        'db_engine'               => 'MyISAM',
        'show_pw'                 => 1,
        'last_item_pw'            => 1,
        'index_pw'                => 0,
        'fastcfg'                 => 1,
        'mailer'                  => 'mail',
        'smtpsecure'              => '',
        'smtpauth'                => 0,
        'smtpuser'                => '',
        'smtppass'                => '',
        'smtphost'                => 'localhost',
        'smtpport'                => 25,
        'timezone'                => 'Europe/Moscow',
        'timediff'                => 0,
        'user_stats'              => 1,
        'seo_url_count'           => 40,
        'allow_ip'                => '',
        'detect_ip_key'           => 'REMOTE_ADDR',
        'cache_enabled'           => false,
        'cache_method'            => 'files',
        'cache_ttl'               => 3600,
        'cache_path'              => '/cache/data',
        'cache_host'              => 'localhost',
        'cache_port'              => 11211,
    ];
    protected static $config         = array();

    private function __construct()
    {
        mb_internal_encoding("UTF-8");

        self::$config = self::getDefaultConfig();

        date_default_timezone_set(self::$config['timezone']);

        return true;
    }

    public function __get($name)
    {
        return isset(self::$config[$name]) ? self::$config[$name] : null;
    }

    public function __set($name, $value)
    {
        self::$config[$name] = $value;
    }

    public function __isset($name)
    {
        return isset(self::$config[$name]);
    }

    /**
     * Возвращает результат проверки наличия конфигурационного файла, при налиичии
     * которого считается что система усановлена
     *
     * @return bool
     */
    public function isReady()
    {
        return isset(self::$config['is_ready']);
    }

    /**
     * Возвращает оригинальный массив конфигурации системы
     * отдельно используется только в админке и при установке
     *
     * @return array
     */
    public static function getDefaultConfig()
    {
        $f = PATH . '/includes/config.inc.php';

        if ( file_exists($f) ) {
            require($f);

            $_CFG['is_ready'] = true;
        }
        else {
            $_CFG = array();
        }

        $cfg = array_merge(self::$default_config, $_CFG);

        foreach ( $cfg as $key => $value ) {
            $cfg[$key] = stripslashes($value);
        }

        $cfg['cookie_key'] = md5($cfg['sitename']);

        return $cfg;
    }

    /**
     * Возвращает значение опции конфигурации
     * или полный массив значений
     *
     * @param string $value
     *
     * @return mixed
     */
    public static function getConfig($value = '')
    {
        if ( $value ) {
            if ( isset(self::$config[$value]) ) {
                return self::$config[$value];
            }
            else {
                return null;
            }
        }
        else {
            return self::$config;
        }
    }

    /**
     * Сохраняет массив в файл конфигурации
     *
     * @param array $_CFG
     *
     * @return bool
     */
    public static function saveToFile($_CFG, $file = 'config.inc.php')
    {
        global $_LANG;

        $filepath = PATH . '/includes/' . $file;

        if ( file_exists($filepath) ) {
            if ( !@is_writable($filepath) ) {
                die(sprintf($_LANG['FILE_NOT_WRITABLE'], '/includes/' . $file));
            }
        }
        else {
            if ( !@is_writable(dirname($filepath)) ) {
                die(sprintf($_LANG['DIR_NOT_WRITABLE'], '/includes'));
            }
        }

        $cfg_file = fopen($filepath, 'w+');

        fputs($cfg_file, "<?php \n");
        fputs($cfg_file, "if (!defined('VALID_CMS')) { die('ACCESS DENIED'); }" . PHP_EOL);
        fputs($cfg_file, '$_CFG = [];' . PHP_EOL);

        foreach ( $_CFG as $key => $value ) {
            if ( is_int($value) ) {
                $s = '$_CFG' . "['" . $key . "'] = " . $value . ";" . PHP_EOL;
            }
            else {
                $s = '$_CFG' . "['" . $key . "'] = '" . addslashes($value) . "';" . PHP_EOL;
            }

            fwrite($cfg_file, $s);
        }

        fwrite($cfg_file, "?>");
        fclose($cfg_file);

        return true;
    }

}
