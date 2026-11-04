<?php

Error_Reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Based on InstantCMS v1.10.7 by InstantSoft
 *          (Original code © InstantCMS Team, 2007-2016)
 *          (Modifications © CMS RuDi Team / DagIT, 2026)
 *
 *                      LICENSED BY GNU/GPL v2
 */

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');

session_start();

define('VALID_CMS', 1);
define('VALID_CMS_ADMIN', 1);

define('PATH', $_SERVER['DOCUMENT_ROOT']);

require(PATH . '/core/classes/autoload.php');

// Нужно для cmsDatabase::getInstance() ниже (префикс таблиц)
cmsConfig::getInstance();

//===================== Переадресуем на новую админку ========================//

$r = \cms\request::getInstance();

$view = $r->get('view', 'str', 'main');
$do = $r->get('do', 'str');
$opt = $r->get('opt', 'str');

if ($view == 'components') {
    $do = $r->get('link', 'str');

    // Старые ссылки вида ?view=components&do=config&id=5
    if (empty($do)) {
        $id = $r->get('id', 'int');

        if ($id) {
            $component = cmsDatabase::getInstance()->get_fields('cms_components', 'id=' . (int) $id, '*');

            if ($component) {
                $do = $component['link'];
            }
        }
    }
} else if ($view == 'plugins') {
    // Старые ссылки вида ?view=plugins&do=config&id=5
    if ($do == 'config' || $do == 'save_config') {
        $id = $r->get('id', 'int');

        if ($id) {
            $plugin = cmsDatabase::getInstance()->get_field('cms_plugins', 'id=' . (int) $id, 'plugin');

            if ($plugin) {
                $opt = $plugin;
            }
        }
    }
}

if ($view == 'clearcache') {
    $view = 'cache';
    $do = 'clear';
}

cmsCore::redirect('/cp/' . $view . (!empty($do) ? '/' . $do : '') . (!empty($opt) ? '/' . $opt : '') . '?' . http_build_query($_GET));

//============================================================================//

require(PATH . '/admin/includes/cp.php');
require(PATH . '/includes/tools.inc.php');

$inCore = cmsAdmin::getInstance();

$inPage = cmsPage::getInstance();
$inConf = cmsConfig::getInstance();
$inDB = cmsDatabase::getInstance();
$inUser = cmsUser::getInstance();

if (!$inUser->update()) {
    cmsCore::error404();
}

// проверяем доступ по Ip
if (!cmsCore::checkAccessByIp($inConf->allow_ip)) {
    cmsCore::error404();
}

cmsCore::loadLanguage('admin/lang');

//-------CHECK AUTHENTICATION--------------------------------------//
if (!$inUser->is_admin) {
    include PATH . '/admin/login.php';
    cmsCore::halt();
}
//--------LOAD ACCESS OPTIONS LIST---------------------------------//

$adminAccess = cmsUser::getAdminAccess();

//------------------------------------------------------------------//

$inUser->onlineStats();

$GLOBALS['component'] = '';
$GLOBALS['applet'] = cmsCore::request('view', 'str', 'main');
if (!preg_match('/^[a-z0-9]+$/i', $GLOBALS['applet'])) {
    cmsCore::error404();
}

$GLOBALS['cp_page_title'] = '';
$GLOBALS['cp_page_head'] = array();
$GLOBALS['cp_page_body'] = '';

$GLOBALS['cp_pathway'] = array(
    array(
        'title' => $_LANG['PATH_HOME'],
        'link' => '/admin/',
    )
);

cpProceedBody();

include(PATH . '/admin/template.php');
