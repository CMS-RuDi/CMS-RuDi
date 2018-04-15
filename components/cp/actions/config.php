<?php

namespace components\cp\actions;

class config extends \cms\com_action
{

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/config', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_site_setting);
        $this->page()->addPathway($this->lang->ad_site_setting);
    }

    public function actView()
    {
        cpCheckWritable('/includes/config.inc.php');

        $config   = \cmsConfig::getConfig();
        $tpl_info = $this->page->getCurrentTplInfo();

        $result = $this->model->db->query("SELECT (sum(data_length)+sum(index_length))/1024/1024 as size FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = '" . $config['db_base'] . "'", false, true);

        if ( $result === false ) {
            $db_size = $this->lang->ad_db_size_error;
        }
        else {
            $s       = $this->model->db->fetchAssoc($result);
            $db_size = round($s['size'], 2) . ' ' . $this->lang->size_mb;
        }

        $tpl = $this->page->initTemplate('cp/applets', 'config')->
                assign('submit_uri', $this->genActionUri('config', 'save'))->
                assign('config', $config)->
                assign('langs', \cmsCore::getDirsList('/languages'))->
                assign('templates', \cmsCore::getDirsList('/templates'))->
                assign('components_list', \cmsCore::getListItems('cms_components', $config['homecom'], 'title', 'ASC', "internal = 0 AND link != 'cp'", 'link'))->
                assign('tpl_info', $this->lang->vsprintf('ad_template_info', $tpl_info['author'], $tpl_info['renderer'], $tpl_info['ext']))->
                assign('timezone_list', \cmsCore::getTimeZonesOptions($config['timezone']))->
                assign('db_size', $db_size);

        if ( file_exists(PATH . '/templates/' . TEMPLATE . '/positions.jpg') ) {
            $tpl->assign('position_view', true);
        }

        $tpl->display();
    }

    public function actSave()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $cfg = \cmsConfig::getConfig();

        $config = [
            'sitename'           => stripslashes($this->request()->get('sitename', 'str', '')),
            'title_and_sitename' => $this->request()->get('title_and_sitename', 'int', 0),
            'title_and_page'     => $this->request()->get('title_and_page', 'int', 0),
            'hometitle'          => stripslashes($this->request()->get('hometitle', 'str', '')),
            'homecom'            => $this->request()->get('homecom', 'str', ''),
            'siteoff'            => $this->request()->get('siteoff', 'int', 0),
            'debug'              => $this->request()->get('debug', 'int', 0),
            'offtext'            => htmlspecialchars($this->request()->get('offtext', 'str', ''), ENT_QUOTES),
            'keywords'           => $this->request()->get('keywords', 'str', ''),
            'metadesc'           => $this->request()->get('metadesc', 'str', ''),
            'seourl'             => $this->request()->get('seourl', 'int', 0),
            'lang'               => $this->request()->get('lang', 'str', 'ru'),
            'is_change_lang'     => $this->request()->get('is_change_lang', 'int', 0),
            'sitemail'           => $this->request()->get('sitemail', 'str', ''),
            'sitemail_name'      => $this->request()->get('sitemail_name', 'str', ''),
            'wmark'              => $this->request()->get('wmark', 'str', ''),
            'template'           => $this->request()->get('template', 'str', ''),
            'splash'             => $this->request()->get('splash', 'int', 0),
            'slight'             => $this->request()->get('slight', 'int', 0),
            'db_host'            => $cfg['db_host'],
            'db_base'            => $cfg['db_host'],
            'db_user'            => $cfg['db_user'],
            'db_pass'            => $cfg['db_pass'],
            'db_prefix'          => $cfg['db_prefix'],
            'show_pw'            => $this->request()->get('show_pw', 'int', 0),
            'last_item_pw'       => $this->request()->get('last_item_pw', 'int', 0),
            'index_pw'           => $this->request()->get('index_pw', 'int', 0),
            'fastcfg'            => $this->request()->get('fastcfg', 'int', 0),
            'mailer'             => $this->request()->get('mailer', 'str', ''),
            'smtpsecure'         => $this->request()->get('smtpsecure', 'str', ''),
            'smtpauth'           => $this->request()->get('smtpauth', 'int', 0),
            'smtpuser'           => $this->request()->get('smtpuser', 'str', $cfg['smtpuser']),
            'smtppass'           => $this->request()->get('smtppass', 'str', $cfg['smtppass']),
            'smtphost'           => $this->request()->get('smtphost', 'str', ''),
            'smtpport'           => $this->request()->get('smtpport', 'int', '25'),
            'timezone'           => $this->request()->get('timezone', 'str', ''),
            'user_stats'         => $this->request()->get('user_stats', 'int', 0),
            'seo_url_count'      => $this->request()->get('seo_url_count', 'int', 0),
            'allow_ip'           => $this->request()->get('allow_ip', 'str', '')
        ];

        if ( \cmsConfig::saveToFile($config) ) {
            \cmsCore::addSessionMessage($this->lang->ad_config_save_success, 'success');
        }
        else {
            \cmsCore::addSessionMessage($this->lang->ad_config_site_error, 'error');
        }

        \cmsCore::clearCache();

        $this->redirectToAction('config');
    }

}
