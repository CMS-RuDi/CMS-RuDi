<?php

/*
 *                           InstantCMS v1.10.6
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2015
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

function mod_random_image($mod, $cfg)
{
    $inDB = \cms\db::getInstance();

    $db = clone $inDB;

    $db->select('i.*');
    $db->select('a.title', 'album_title');
    $db->joinLeft('photo_albums', 'a', 'a.id = i.album_id');

    if ( $cfg['album_id'] != 0 ) {
        if ( $cfg['subs'] ) {
            $rootcat = $inDB->getRow('photo_albums', 'id = ' . (int) $cfg['album_id'], 'NSLeft, NSRight');

            if ( $rootcat ) {
                $db->filter('a.NSLeft >= ' . (int) $rootcat['NSLeft'] . ' AND a.NSRight <= ' . (int) $rootcat['NSRight']);
            }
        }
        else {
            $db->filterEqual('i.album_id', (int) $cfg['album_id']);
        }
    }

    $db->filterEqual('i.published', 1);
    $db->orderBy('RAND()');
    $db->limit(0, 1);

    $item = $db->getItem('photo_files');

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('item', $item)->
            assign('is_img', (bool) $item)->
            assign('cfg', $cfg)->
            display($cfg['tpl']);

    return true;
}
