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

function mod_tags($mod, $cfg)
{
    $inDB = \cms\db::getInstance();

    if ( !@$cfg['targets'] ) {
        return false;
    }

    if ( !isset($cfg['minfreq']) ) {
        $cfg['minfreq'] = 0;
    }

    if ( !isset($cfg['minlen']) ) {
        $cfg['minlen'] = 0;
    }

    if ( !isset($cfg['maxtags']) ) {
        $cfg['maxtags'] = 20;
    }

    if ( !isset($cfg['colors']) ) {
        $cfg['colors'] = '';
    }

    if ( !isset($cfg['shuffle']) ) {
        $cfg['shuffle'] = 0;
    }

    if ( !isset($cfg['start_size']) ) {
        $cfg['start_size'] = 10;
    }

    if ( !isset($cfg['step']) ) {
        $cfg['step'] = 4;
    }

    if ( !isset($cfg['end_size']) ) {
        $cfg['end_size'] = 50;
    }

    $db = clone $inDB;
    $db->select('tag');
    $db->select('COUNT(tag)', 'num');
    $db->filterIn('target', (array) $cfg['targets']);

    if ( $cfg['minlen'] ) {
        $db->filter('CHAR_LENGTH(tag) >= ' . (int) $cfg['minlen']);
    }

    $db->groupBy('tag');

    if ( $cfg['sortby'] == 'tag' ) {
        $db->orderBy('tag');
    }
    else {
        $db->orderBy('num', 'DESC');
    }

    $rows = $db->get('tags', false, false);

    // аналог HAVING num >= minfreq и LIMIT maxtags
    $tags = array();

    foreach ( $rows as $row ) {
        if ( $cfg['minfreq'] && $row['num'] < $cfg['minfreq'] ) {
            continue;
        }

        if ( count($tags) >= (int) $cfg['maxtags'] ) {
            break;
        }

        $tags[] = $row;
    }

    if ( !$tags ) {
        return false;
    }

    // массив возможных значений шрифта
    $sizes    = range($cfg['start_size'], $cfg['end_size'], $cfg['step']);
    $size_prc = ceil((100 / sizeof($sizes)));
    // Общее число тегов
    $summary = 0;

    foreach ( $tags as $key => $tag ) {
        $tags[$key]['fontsize'] = '';
        $summary += $tag['num'];
    }

    // формируем размер шрифта
    foreach ( $tags as $key => $value ) {
        $prc = ceil(($value['num'] / $summary) * 100);
        foreach ( $sizes as $k => $v ) {
            if ( $prc >= ($k * $size_prc) ) {
                $tags[$key]['fontsize'] = $sizes[$k];
            }
        }
    }

    // перемешивать теги
    if ( $cfg['shuffle'] ) {
        shuffle($tags);
    }

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('tags', $tags)->
            assign('cfg', $cfg)->
            display($cfg['tpl']);

    return true;
}
