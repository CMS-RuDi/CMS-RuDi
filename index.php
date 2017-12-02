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

header('Content-Type: text/html; charset=utf-8');
header('X-Powered-By: InstantCMS');
define('PATH', __DIR__);
define('VALID_CMS', 1);

require(PATH . '/core/classes/autoload.php');

$inConf = cmsConfig::getInstance();

// Проверяем, что система установлена
if ( !$inConf->isReady() ) {
    header('location:/install/');
    die();
}

// дебаг отключен - скрываем все сообщения об ошибках
if ( !$inConf->debug ) {
    error_reporting(0);
}
else {
    error_reporting(E_ALL);
    set_error_handler(array( '\\cms\\debug', 'errorHandler' ));
}

session_start();

$inCore = cmsCore::getInstance();

// Проверяем что директории установки и миграции удалены
if ( is_dir(PATH . '/install') || is_dir(PATH . '/migrate') ) {
    cmsPage::includeTemplateFile('special/installation.php');
    cmsCore::halt();
}

\cms\plugin::callEvent('get_index', '');

$inPage = cmsPage::getInstance();
$inUser = cmsUser::getInstance();

// автоматически авторизуем пользователя, если найден кукис
$inUser->autoLogin();

// проверяем что пользователь не удален и не забанен и загружаем его данные
if ( !$inUser->update() && !$_SERVER['REQUEST_URI'] !== '/logout' ) {
    cmsCore::halt();
}

//Если сайт выключен и пользователь не администратор,
//то показываем шаблон сообщения о том что сайт отключен
if ( $inConf->siteoff &&
        !$inUser->is_admin &&
        $_SERVER['REQUEST_URI'] != '/login' &&
        $_SERVER['REQUEST_URI'] != '/logout'
 ) {
    cmsPage::includeTemplateFile('special/siteoff.php');
    cmsCore::halt();
}

// Мониторинг пользователей
$inUser->onlineStats();

//Проверяем доступ пользователя
//При положительном результате
//Строим тело страницы (запускаем текущий компонент)
if ( $inCore->checkMenuAccess() ) {
    $inCore->proceedBody();
}

//Проверяем нужно ли показать входную страницу (splash)
if ( cmsPage::isSplash() ) {
    //Показываем входную страницу
    cmsPage::showSplash();
}
else {
    //показываем шаблон сайта
    $inPage->showTemplate();
}