<?php

namespace components\cp;

class frontend extends \cms\backend
{

    private static $already_initialized;

    public function __construct($request = null)
    {
        parent::__construct($request);

        if ( !self::$already_initialized ) {
            header('X-Frame-Options: DENY');

            define('VALID_CMS_ADMIN', 1);

            \cmsCore::includeFile('admin/includes/cp.php');
            \cmsCore::includeFile('includes/tools.inc.php');

            $this->lang->load('admin/lang');

            $pathway            = $this->page->getPathway();
            $pathway[0]['link'] = '/' . $this->name;
            $this->page->setPathway($pathway);

            self::$already_initialized = true;
        }
    }

    /**
     * Главная страница CP
     */
    public function actionIndex()
    {
        $content_counts = [
            'content' => false,
            'photos'  => false,
            'video'   => false,
            'audio'   => false,
            'maps'    => false,
            'faq'     => false,
            'board'   => false,
            'catalog' => false,
            'forum'   => false,
        ];

        if ( \cms\controller::enabled('content') ) {
            $model_name                = self::getModelClassName('content');
            $content_counts['content'] = $this->model->getCountNewContent($model_name::CONTENT_TABLE);
//$content_counts['content'] = $this->model->getCountNewContent({\cms\controller::getModelClassName('content')}::CONTENT_TABLE);
        }

        if ( \cms\controller::enabled('photos') ) {
            $model_name               = self::getModelClassName('photos');
            $content_counts['photos'] = $this->model->getCountNewContent($model_name::PHOTO_FILES_TABLE);
//$content_counts['photos'] = $this->model->getCountNewContent({\cms\controller::getModelClassName('photos')}::PHOTO_FILES_TABLE);
        }

        if ( \cms\controller::enabled('video') ) {
            $content_counts['video'] = $this->model->getCountNewContent('video_movie');
        }

        if ( \cms\controller::enabled('audio') ) {
            $content_counts['audio'] = 0;
        }

        if ( \cms\controller::enabled('maps') ) {
            $content_counts['maps'] = $this->model->getCountNewContent('map_items');
        }

        if ( \cms\controller::enabled('faq') ) {
            $content_counts['faq'] = $this->model->getCountNewContent('faq_quests');
        }

        if ( \cms\controller::enabled('board') ) {
            $content_counts['board'] = $this->model->getCountNewContent('board_items');
        }

        if ( \cms\controller::enabled('catalog') ) {
            $content_counts['catalog'] = $this->model->getCountNewContent('uc_items');
        }

        if ( \cms\controller::enabled('forum') ) {
            $content_counts['forum'] = $this->model->getCountNewContent('forum_posts');
        }

        $inActions = \cmsActions::getInstance();
        $inActions->showTargets(true);
        \cmsDatabase::getInstance()->limitPage(1, 30);

        \cmsPage::initTemplate('cp', 'index')->
                assign('content_counts', $content_counts)->
                assign('total_users', $this->model->db->getRowsCount('users', 'is_deleted=0'))->
                assign('today_reg_users', (int) $this->model->db->getRowsCount('users', "DATE_FORMAT(regdate, '%d-%m-%Y') = DATE_FORMAT(NOW(), '%d-%m-%Y') AND is_deleted = 0"))->
                assign('week_reg_users', (int) $this->model->db->getRowsCount('users', "regdate >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))->
                assign('month_reg_users', (int) $this->model->db->getRowsCount('users', "regdate >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"))->
                assign('people', \cmsUser::getOnlineCount())->
                assign('actions', $inActions->getActionsLog())->
                assign('new_quests', \cms\controller::installed('faq') ? $this->model->db->getRowsCount('faq_quests', 'published=0') : 0)->
                assign('new_content', $this->model->db->getRowsCount('content', 'published=0 AND is_arhive = 0'))->
                assign('new_catalog', \cms\controller::installed('catalog') ? $this->model->db->getRowsCount('uc_items', 'on_moderate=1') : 0)->
                assign('rssfeed', \cms\controller::enabled('rssfeed'))->
                assign('com_enabled', [ 'forum' => \cms\controller::enabled('forum'), 'board' => \cms\controller::enabled('board'), 'faq' => \cms\controller::enabled('faq'), 'catalog' => \cms\controller::enabled('catalog'), 'banners' => \cms\controller::enabled('banners'), 'polls' => \cms\controller::enabled('polls') ])->
                display();
    }

//============================================================================//

    public function actionMain()
    {
        \cmsCore::redirect('/' . $this->name);
    }

    public function before($action_name)
    {
        if ( $action_name == 'index' ) {
            $action_name = 'main';
        }

        $this->lang->load('admin/applets/applet_' . $action_name);

        ob_start();
    }

    public function after($action_name)
    {
        $this->page->page_body = \cms\events::call('core.after_cp', ob_get_clean());

        $this->showTemplate();

        \cmsCore::halt();
    }

    protected function showTemplate()
    {
        self::prepareHead();

        \cmsPage::initTemplate('cp', 'template')->
                assign('nowdate', date('d') . ' ' . $this->lang->get('month_' . date('m')))->
                assign('new_messages', $this->user->getNewMsg())->
                assign('profile_url', \cmsUser::getProfileURL($this->user->login))->
                assign('nickname', $this->user->nickname)->
                assign('ip', $this->user->ip)->
                assign('menu', $this->generateMenu())->
                assign('messages', \cmsCore::getSessionMessages())->
                assign('CORE_VERSION', CORE_VERSION)->
                display();
    }

    public static function prepareHead()
    {
        $inPage = \cmsPage::getInstance();
        $lang   = \cms\lang::getInstance();

        $page_title = (string) (!empty($GLOBALS['cp_page_title']) ? $GLOBALS['cp_page_title'] : $inPage->getTitle());

        if ( !empty($page_title) ) {
            $page_title .= ' — ';
        }

        $inPage->setTitle($page_title . $lang->ad_admin_panel . ' v ' . CORE_VERSION);

        $inPage->prependHeadJS('admin/js/common.js');
        $inPage->addHeadJsLang(array( 'AD_NO_SELECT_OBJECTS', 'AD_SWITCH_EDITOR', 'CANCEL', 'CONTINUE', 'CLOSE', 'ATTENTION' ));
        $inPage->addHeadJS('includes/jquery/colorbox/jquery.colorbox.js');
        $inPage->addHeadCSS('includes/jquery/colorbox/colorbox.css');
        $inPage->addHeadJsLang(array( 'CBOX_IMAGE', 'CBOX_FROM', 'CBOX_PREVIOUS', 'CBOX_NEXT', 'CBOX_CLOSE', 'CBOX_XHR_ERROR', 'CBOX_IMG_ERROR', 'CBOX_SLIDESHOWSTOP', 'CBOX_SLIDESHOWSTART' ));
        $inPage->prependHeadJS(!empty($GLOBALS['cp_jquery']) ? $GLOBALS['cp_jquery'] : 'includes/jquery/jquery.js');

        if ( !empty($GLOBALS['cp_page_head']) ) {
            foreach ( $GLOBALS['cp_page_head'] as $key => $value ) {
                $inPage->addHead($value);
                unset($GLOBALS['cp_page_head'][$key]);
            }
        }

        $inPage->addHeadCSS('admin/css/styles.css');
        $inPage->addHeadCSS('admin/js/hmenu/hmenu.css');
        $inPage->addHeadCSS('includes/jquery/tablesorter/style.css');
        $inPage->addHeadCSS('includes/jqueryui/css/smoothness/jquery-ui.min.css');

        $inPage->addHeadJS('admin/js/admin.js');
        $inPage->addHeadJS('includes/jquery/jquery.columnfilters.js');
        $inPage->addHeadJS('includes/jquery/tablesorter/jquery.tablesorter.min.js');
        $inPage->addHeadJS('includes/jquery/jquery.preload.js');
        $inPage->addHeadJS('includes/jqueryui/jquery-ui.min.js');
        $inPage->addHeadJS('includes/jqueryui/init-ui.js');
        $inPage->addHeadJS('includes/jqueryui/i18n/jquery.ui.datepicker-' . \cmsConfig::getConfig('lang') . '.min.js');
        $inPage->addHeadJS('includes/jquery/jquery.form.js');
        $inPage->addHeadJS('admin/js/hltable.js');
        $inPage->addHeadJS('admin/js/jquery.jclock.js');
    }

    public function generateMenu()
    {
        $html = \cmsPage::initTemplate('cp', 'menu')->
                assign('menu_access', \cmsUser::isAdminCan('admin/menu', self::$admin_access))->
                assign('modules_access', \cmsUser::isAdminCan('admin/modules', self::$admin_access))->
                assign('content_access', \cmsUser::isAdminCan('admin/content', self::$admin_access))->
                assign('components_access', \cmsUser::isAdminCan('admin/components', self::$admin_access))->
                assign('plugins_access', \cmsUser::isAdminCan('admin/plugins', self::$admin_access))->
                assign('users_access', \cmsUser::isAdminCan('admin/users', self::$admin_access))->
                assign('config_access', \cmsUser::isAdminCan('admin/config', self::$admin_access))->
                assign('components', $this->model->getMenuComponents())->
                fetch();

        return \cms\events::call('admin.main_menu', $html);
    }

    public function yesno($value)
    {
        if ( empty($value) ) {
            $value = '<span style="color:green;">' . $this->lang->yes . '</span>';
        }
        else {
            $value = '<span style="color:red;">' . $this->lang->no . '</span>';
        }

        return $value;
    }

}
