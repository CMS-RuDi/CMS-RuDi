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

define('PATH', __DIR__ . '/../..');

include(PATH . '/core/ajax/ajax_core.php');

cmsCore::loadLanguage('components/registration');

$opt  = cmsCore::request('opt', 'str', '');
$data = cmsCore::request('data', 'str', '');

if ( !$data ) {
    cmsCore::halt();
}

if ( mb_strlen($data) < 2 ||
        mb_strlen($data) > 15 ||
        is_numeric($data) ||
        !preg_match("/^([a-z0-9])+$/ui", $data) ) {

    cmsCore::halt('<span style="color:red">' . $_LANG['ERR_LOGIN'] . '</span>');
}

if ( $opt == 'checklogin' ) {
    $is_busy = \cms\db::cloneOf($inDB)->filterEqual('login', mb_strtolower($data))
                    ->filterEqual('is_deleted', 0)
                    ->getCount('users');

    if ( !$is_busy ) {
        echo '<span style="color:green">' . $_LANG['YOU_LOGIN_COMPLETE'] . '</span>';
    }
    else {
        echo '<span style="color:red">' . $_LANG['LOGIN'] . ' "' . $data . '" ' . $_LANG['IS_BUSY'] . '</span>';
    }
}

cmsCore::halt();
