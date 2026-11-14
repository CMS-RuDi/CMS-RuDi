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

$inCore = cmsCore::getInstance(false, true);
$inConf = cmsConfig::getInstance();

// Проверяем, что система установлена
if (!$inConf->isReady()) {
    die();
}

$inUser = cmsUser::getInstance();
if (!$inUser->update()) {
    cmsCore::error404();
}

if (!$inUser->is_admin) {
    include PATH . '/admin/login.php';
    cmsCore::halt();
}

$db = \cms\db::getInstance();

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
            $link = $db->getField('components', $id, 'link');

            if ($link) {
                $do = $link;
            }
        }
    }
} else if ($view == 'plugins') {
    // Старые ссылки вида ?view=plugins&do=config&id=5
    if ($do == 'config' || $do == 'save_config') {
        $id = $r->get('id', 'int');

        if ($id) {
            $plugin = $db->getField('plugins', $id, 'plugin');

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
