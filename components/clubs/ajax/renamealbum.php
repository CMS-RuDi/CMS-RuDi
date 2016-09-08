<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.6                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2015                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/
define('PATH', __DIR__ .'/../../..');

include(PATH .'/core/ajax/ajax_core.php');

if (!$inUser->id) {
    cmsCore::halt();
}

$model = new cms_model_clubs();

$title = cmsCore::request('title', 'str', '');

if (!$title) {
    cmsCore::jsonOutput(array(
        'error' => true,
        'text' => $_LANG['ALBUM_REQ_TITLE']
    ));
}

// Получаем альбом
$album = $inDB->getNsCategory('cms_photo_albums', cmsCore::request('album_id', 'int', 0), null);
if (!$album) {
    cmsCore::halt();
}

// получаем клуб
$club = $model->getClub($album['user_id']);
if (!$club) {
    cmsCore::halt();
}

if (!$club['enabled_photos']) {
    cmsCore::halt();
}

// Инициализируем участников клуба
$model->initClubMembers($club['id']);

// права доступа
$is_admin = $inUser->is_admin || ($inUser->id == $club['admin_id']);
$is_moder = $model->checkUserRightsInClub('moderator');

if ($is_admin || $is_moder) {
    $inDB->update('cms_photo_albums', array('title' => $title), $album['id']);

    cmsCore::jsonOutput(array(
        'error' => false,
        'text' => htmlspecialchars(stripslashes($title))
    ));
}

cmsCore::halt();