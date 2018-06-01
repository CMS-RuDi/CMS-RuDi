<?php

namespace cms;

class backend extends controller
{

    /**
     * @var \cmsUser
     */
    protected $user;

    /**
     * @var \cmsConfig
     */
    protected $config;

    /**
     * @var array|false
     */
    public static $admin_access = false;

    /**
     * @var array
     */
    protected static $toolmenu = [];

    /**
     * @var string|null
     */
    protected static $ptitle;

    /**
     * @var string|null
     */
    protected static $sub_title;

    public function __construct($request = null)
    {
        parent::__construct($request);

        $this->core   = \cmsAdmin::getInstance();
        $this->user   = \cmsUser::getInstance();
        $this->config = \cmsConfig::getInstance();

        if ( !$this->user->update() ) {
            \cmsCore::error404();
        }

        if ( !\cmsCore::checkAccessByIp($this->config->allow_ip) ) {
            \cmsCore::error404();
        }

        if ( !$this->user->is_admin ) {
            include PATH . '/cp/login';
            \cmsCore::halt();
        }

        self::$admin_access = \cmsUser::getAdminAccess();

        $part = explode('\\', get_called_class());

        if ( $part[count($part) - 1] == 'backend' ) {
            $this->root_url = 'cp/components/' . $this->root_url;

            $this->lang->load('admin/components/' . $this->name);
        }
    }

    public static function accessDenied()
    {
        \cmsCore::redirect('/cp/noaccess');
    }

    public static function setTitle($title)
    {
        self::$ptitle = $title;
    }

    public static function setSubTitle($sub_title)
    {
        self::$sub_title = $sub_title;
    }

    public static function printTitle()
    {
        if ( !empty(self::$ptitle) ) {
            echo '<h1>' . self::$ptitle . '' . (!empty(self::$sub_title) ? ': <span>' . self::$sub_title . '</span>' : '') . '</h1>';
        }
    }

    public static function addToolMenuItems($items)
    {
        if ( is_array($item) ) {
            foreach ( $items as $item ) {
                self::addToolMenuItem($item['title'], $item['link'], $item['icon'], isset($item['target']) ? $item['target'] : false);
            }
        }
    }

    public static function addToolMenuItem($title, $link, $icon, $target = false)
    {
        if ( is_array($title) ) {
            self::$toolmenu[] = $title;
        }
        else {
            self::$toolmenu[] = [
                'title'  => $title,
                'link'   => $link,
                'icon'   => $icon,
                'target' => $target
            ];
        }
    }

    public static function addToolMenuSeparator()
    {
        self::$toolmenu[] = false;
    }

    public static function getToolMenuItems()
    {
        return self::$toolmenu;
    }

    public static function clearToolMenu()
    {
        self::$toolmenu = [];
    }

    public static function printToolMenu()
    {
        if ( !empty(self::$toolmenu) ) {
            $action = \cmsCore::getInstance()->do;

            $component = self::getComponent();

            $toolmenu = \cms\events::call('admin.toolmenu_' . $action . (!empty($component) ? '_' . strtolower($component) : ''), self::$toolmenu);

            if ( !empty($toolmenu) ) {
                \cmsPage::initTemplate('cp', 'toolmenu')->
                        assign('toolmenu', self::$toolmenu)->
                        assign('uri', $_SERVER['REQUEST_URI'])->
                        display();
            }
        }

        self::clearToolMenu();
    }

    public static function getComponent()
    {
        $action = \cmsCore::getInstance()->do;

        if ( $action == 'components' ) {
            $uri = \cmsCore::getInstance()->getUri();

            $segments = explode('/', $uri);

            if ( !empty($segments[2]) ) {
                return $segments[2];
            }
        }

        return false;
    }

    public static function getPanelHtml()
    {
        $p_html = \cms\events::call('admin.replace_panel', [ 'html' => '' ]);

        if ( $p_html['html'] ) {
            return $p_html['html'];
        }

        $tpl = \cmsPage::initTemplate('cp/special', 'panel')->
                assign('banners_enabled', \cms\controller::enabled('banners'))->
                assign('forms_list', \cmsCore::getInstance()->getListItems('cms_forms'));

        if ( \cms\controller::enabled('banners') ) {
            $tpl->assign('banners_list', \cms_model_banners::getBannersListHTML());
        }

        return $tpl->fetch();
    }

    public static function getLangPanel($target, $target_id, $field)
    {
        $langs = \cmsCore::getDirsList('/languages');

        if ( empty($target_id) || count($langs) < 1 ) {
            return '';
        }

        return \cmsPage::initTemplate('cp/special', 'lang_panel')->
                        assign('langs', \cmsCore::getDirsList('/languages'))->
                        assign('target', $target)->
                        assign('target_id', $target_id)->
                        assign('field', $field)->
                        fetch();
    }

    public static function componentHasOldBackend($component)
    {
        return file_exists(PATH . '/admin/components/' . $component . '/backend.php');
    }

    public static function componentHasNewBackend($component)
    {
        return file_exists(PATH . '/components/' . $component . '/backend.php');
    }

    public static function showDebugInfo()
    {
        \cmsPage::initTemplate('cp/special', 'debug')->
                assign('time', \cms\debug::getTime('cms'))->
                assign('memory', round(@memory_get_usage() / 1024 / 1024, 2))->
                assign('debug', \cms\debug::getDebugInfo())->
                assign('debug_tabs', \cms\debug::getDebagTargets())->
                assign('debug_times', \cms\debug::getTotalRunTime())->
                display();
    }

    //========================================================================//

    /**
     * Возвращает объект контроллера компонента
     *
     * @param string $component
     * @param bool $reinit
     * @param \cms\request $request
     *
     * @return \self|false
     */
    public static function getBackend($component, $reinit = false, $request = false)
    {
        if ( !isset(self::$factory['backends'][$component]) || $reinit === true ) {
            $class_name = '\\components\\' . $component . '\\backend';

            if ( \cmsCore::includeFile('components/' . $component . '/backend.php') && class_exists($class_name, false) ) {
                self::$factory['backends'][$component] = new $class_name($request instanceof \cms\request ? $request : \cms\request::getInstance());
            }
        }

        return isset(self::$factory['backends'][$component]) ? self::$factory['backends'][$component] : false;
    }

    //========================================================================//

    public function user()
    {
        return $this->user;
    }

    public function core()
    {
        return $this->core;
    }

}
