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

if ( !$inUser->is_admin ) {
    cmsCore::halt();
}

$user_id = cmsCore::request('user_id', 'int');

if ( !$user_id ) {
    cmsCore::halt();
}

$last_ip = $inDB->get_field('cms_users', "id = '" . $user_id . "'", 'last_ip');

echo $last_ip;

cmsCore::halt();
