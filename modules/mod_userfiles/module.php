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

function fetchFiles($rows)
{
    $files = array();

    foreach ( $rows as $file ) {
        $file['size'] = round(($file['filesize'] / 1024) / 1024, 2);
        $files[]      = $file;
    }

    return $files;
}

function mod_userfiles($mod, $cfg)
{
    $inDB = \cms\db::getInstance();

    if ( !isset($cfg['sw_stats']) ) {
        $cfg['sw_stats'] = 1;
    }

    if ( !isset($cfg['sw_latest']) ) {
        $cfg['sw_latest'] = 1;
    }

    if ( !isset($cfg['sw_popular']) ) {
        $cfg['sw_popular'] = 1;
    }

    if ( !isset($cfg['num_latest']) ) {
        $cfg['num_latest'] = 5;
    }

    if ( !isset($cfg['num_popular']) ) {
        $cfg['num_popular'] = 5;
    }

    $latest  = array();
    $popular = array();
    $stats   = array();

    //============================ Новые файлы ===============================//

    if ( $cfg['sw_latest'] && $cfg['num_latest'] ) {
        $db = clone $inDB;
        $db->select('i.*');
        $db->select('u.nickname', 'user_nickname');
        $db->select('u.login', 'user_login');
        $db->joinInner('users', 'u', 'u.id = i.user_id');
        $db->filterEqual('i.allow_who', 'all');
        $db->orderBy('i.pubdate', 'DESC');
        $db->limit(0, (int) $cfg['num_latest']);

        $latest = fetchFiles($db->get('user_files', false, false));
    }

    //==========================Популярные файлы =============================//

    if ( $cfg['sw_popular'] && $cfg['num_popular'] ) {
        $db = clone $inDB;
        $db->select('i.*');
        $db->select('u.nickname', 'user_nickname');
        $db->select('u.login', 'user_login');
        $db->joinInner('users', 'u', 'u.id = i.user_id');
        $db->filterEqual('i.allow_who', 'all');
        $db->orderBy('i.hits', 'DESC');
        $db->limit(0, (int) $cfg['num_popular']);

        $popular = fetchFiles($db->get('user_files', false, false));
    }

    //============================= Статистика ===============================//

    if ( $cfg['sw_stats'] ) {
        $stats['total_files'] = \cms\db::cloneOf($inDB)->filterEqual('allow_who', 'all')->getCount('user_files');

        $stats['total_size'] = 0;

        $size = $inDB->getRow('user_files', "allow_who = 'all'", 'SUM(filesize) as bytes');

        if ( $size ) {
            $stats['total_size'] = round(($size['bytes'] / 1024) / 1024, 2);
        }
    }

    if ( !$popular && !$latest ) {
        return false;
    }

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('latest', $latest)->
            assign('popular', $popular)->
            assign('stats', $stats)->
            assign('cfg', $cfg)->
            display($cfg['tpl']);

    return true;
}
