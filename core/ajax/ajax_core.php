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

//
// ВНИМАНИЕ! Данный файл используйте в своих отдельных php файлах
//			 которые отдают что-либо через ajax
//           подключая его так:
//			 define('PATH', $_SERVER['DOCUMENT_ROOT']);
//			 include(PATH.'/core/ajax/ajax_core.php');
//           ниже произведена инициализация InstantCMS с базовыми классами
//

if ( !defined('PATH') ) {
    die('ACCESS DENIED');
}

Error_Reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if ( @$_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' ) {
    die();
}

header('Content-Type: text/html; charset=utf-8');

require(PATH . '/core/classes/autoload.php');

session_start();

define("VALID_CMS", 1);

$inCore = cmsCore::getInstance();

$inDB   = cmsDatabase::getInstance();
$inConf = cmsConfig::getInstance();
$inUser = cmsUser::getInstance();
$inPage = cmsPage::getInstance();

if ( !$inUser->update() ) {
    cmsCore::halt();
}

if ( $inConf->siteoff && !$inUser->is_admin ) {
    cmsCore::halt();
}

global $_LANG;

$inPage->setRequestIsAjax();
