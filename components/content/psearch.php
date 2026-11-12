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

if ( !defined('VALID_CMS') ) {
    die('ACCESS DENIED');
}

function search_content($query, $look)
{
    $db          = \cms\db::getInstance();
    $searchModel = cms_model_search::initModel();

    global $_LANG;

    $items = $db->resetFilters()->
            selectOnly('i.*')->
            select('cat.title', 'cat_title')->
            select('cat.id', 'cat_id')->
            select('cat.seolink', 'cat_seolink')->
            select('cat.parent_id', 'cat_parent_id')->
            joinInner('category', 'cat', 'cat.id = i.category_id')->
            filter("MATCH(i.title, i.content) AGAINST ('" . $db->escape($query) . "' IN BOOLEAN MODE)")->
            filterEqual('cat.published', 1)->
            filterEqual('i.is_end', 0)->
            filterEqual('i.published', 1)->
            limit(0, 100)->
            get('content', false, false);

    if ( !$items ) {
        return;
    }

    cmsCore::loadLanguage('components/content');

    foreach ( $items as $item ) {

        $result_array = array();

        $result_array['link']        = "/" . $item['seolink'] . ".html";
        $result_array['place']       = $_LANG['CATALOG_ARTICLES'];
        $result_array['placelink']   = $item['cat_parent_id'] > 0 ? "/" . $item['cat_seolink'] : $link;
        $result_array['description'] = $searchModel->getProposalWithSearchWord($item['content']);
        $result_array['title']       = $item['title'];
        $result_array['pubdate']     = $item['pubdate'];
        $result_array['imageurl']    = (file_exists(PATH . '/images/photos/medium/article' . $item['id'] . '.jpg') ? '/images/photos/medium/article' . $item['id'] . '.jpg' : '');
        $result_array['session_id']  = session_id();

        $searchModel->addResult($result_array);
    }

    return;
}
