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

function cmsInsertTags($tagstr, $target, $item_id)
{
    $db = \cms\db::getInstance();

    $db->filterEqual('target', $target)
            ->filterEqual('item_id', (int) $item_id)
            ->deleteFiltered('tags');

    $tagstr = strip_tags(mb_strtolower(preg_replace('/\s+/u', ' ', trim($tagstr))));

    if ( !$tagstr ) {
        return true;
    }

    // формируем массив слов по запятой
    $tags = explode(',', $tagstr);

    foreach ( $tags as $tag ) {
        $tag = mb_substr(trim($tag), 0, 240);

        if ( mb_strlen($tag) >= 3 ) {
            $db->insert('tags', array(
                'tag'     => $tag,
                'target'  => $target,
                'item_id' => (int) $item_id
            ));
        }
    }

    return true;
}

function cmsClearTags($target, $item_id)
{
    return \cms\db::getInstance()
            ->filterEqual('target', $target)
            ->filterEqual('item_id', (int) $item_id)
            ->deleteFiltered('tags');
}

function cmsTagLine($target, $item_id, $links = true, $selected = '')
{
    $db = \cms\db::getInstance();

    $db->resetFilters();

    $tags = $db->select('i.tag')
            ->filterEqual('i.target', $target)
            ->filterEqual('i.item_id', (int) $item_id)
            ->orderBy('i.tag', 'DESC')
            ->get('tags', false, '');

    $html = '';

    if ( $tags ) {
        $t    = 1;
        $last = count($tags);

        foreach ( $tags as $tag ) {
            if ( $links ) {
                if ( $selected == $tag['tag'] ) {
                    $html .= '<a href="/search/tag/' . urlencode($tag['tag']) . '" style="font-weight:bold;text-decoration:underline">' . $tag['tag'] . '</a>';
                }
                else {
                    $html .= '<a href="/search/tag/' . urlencode($tag['tag']) . '">' . $tag['tag'] . '</a>';
                }
            }
            else {
                $html .= $tag['tag'];
            }
            if ( $t < $last ) {
                $html .= ', ';
                $t++;
            }
        }
    }
    else {
        $html = '';
    }
    return $html;
}

function cmsTagBar($target, $item_id, $selected = '')
{
    global $_LANG;

    if ( $tagline = cmsTagLine($target, $item_id, true, $selected) ) {
        return '<div class="taglinebar"><i class="fa fa-tags fa-lg"></i> <span class="label">' . $_LANG['TAGS'] . ': </span><span class="tags">' . $tagline . '</span></div>';
    }
    else {
        return '';
    }
}

function cmsTagItemLink($target, $item_id)
{
    $db     = \cms\db::getInstance();
    $inCore = cmsCore::getInstance();
    $link   = '';

    switch ( $target ) {
        case 'content':
            $today = date("Y-m-d H:i:s");

            $db->resetFilters();

            $item = $db->select('i.title', 'title')
                    ->select('c.title', 'cat')
                    ->select('i.seolink', 'seolink')
                    ->select('c.seolink', 'cat_seolink')
                    ->joinInner('category', 'c', 'c.id = i.category_id')
                    ->filterEqual('i.id', (int) $item_id)
                    ->filterEqual('i.published', 1)
                    ->filterEqual('i.is_arhive', 0)
                    ->filterLtEqual('i.pubdate', $today)
                    ->filterStart()
                    ->filterEqual('i.is_end', 0)
                    ->filterOr()
                    ->filterStart()
                    ->filterEqual('i.is_end', 1)
                    ->filterGtEqual('i.enddate', $today)
                    ->filterEnd()
                    ->filterEnd()
                    ->getItem('content');

            $db->resetFilters();

            if ( $item ) {
                $link = '<a href="/' . $item['cat_seolink'] . '" class="tag_searchcat">' . $item['cat'] . '</a> &rarr; ';
                $link .= '<a href="/' . $item['seolink'] . '.html" class="tag_searchitem">' . $item['title'] . '</a>';
            }
            break;
        case 'blogpost':
            $db->resetFilters();

            $item = $db->select('i.title', 'title')
                    ->select('i.id', 'item_id')
                    ->select('c.title', 'cat')
                    ->select('c.id', 'cat_id')
                    ->select('c.owner', 'owner')
                    ->select('c.user_id', 'user_id')
                    ->select('i.seolink', 'seolink')
                    ->select('c.seolink', 'bloglink')
                    ->joinLeft('blogs', 'c', 'c.id = i.blog_id')
                    ->filterEqual('i.id', (int) $item_id)
                    ->filterEqual('i.published', 1)
                    ->getItem('blog_posts');

            $db->resetFilters();

            if ( $item ) {
                if ( $item['owner'] == 'club' ) {
                    $db->resetFilters();

                    $item['cat'] = $db->filterEqual('id', (int) $item['user_id'])
                            ->getFieldFiltered('clubs', 'title');

                    $db->resetFilters();
                }

                $link = '<a href="/blogs/' . $item['bloglink'] . '" class="tag_searchcat">' . $item['cat'] . '</a> &rarr; ';
                $link .= '<a href="/blogs/' . $item['bloglink'] . '/' . $item['seolink'] . '.html" class="tag_searchitem">' . $item['title'] . '</a>';
            }
            break;
        case 'photo':
            $db->resetFilters();

            $item = $db->select('i.title', 'title')
                    ->select('i.id', 'item_id')
                    ->select('c.title', 'cat')
                    ->select('c.id', 'cat_id')
                    ->joinLeft('photo_albums', 'c', 'c.id = i.album_id')
                    ->filterEqual('i.id', (int) $item_id)
                    ->filterEqual('i.published', 1)
                    ->getItem('photo_files');

            $db->resetFilters();

            if ( $item ) {
                $link = '<a href="/photos/' . $item['cat_id'] . '" class="tag_searchcat">' . $item['cat'] . '</a> &rarr; ';
                $link .= '<a href="/photos/photo' . $item['item_id'] . '.html" class="tag_searchitem">' . $item['title'] . '</a>';
            }
            break;
        case 'userphoto':
            $db->resetFilters();

            $item = $db->select('i.title', 'title')
                    ->select('i.id', 'item_id')
                    ->select('c.nickname', 'cat')
                    ->select('c.id', 'cat_id')
                    ->select('c.login', 'login')
                    ->joinLeft('users', 'c', 'c.id = i.user_id')
                    ->filterEqual('i.id', (int) $item_id)
                    ->getItem('user_photos');

            $db->resetFilters();

            if ( $item ) {
                $link = '<a href="' . cmsUser::getProfileURL($item['login']) . '" class="tag_searchcat">' . $item['cat'] . '</a> &rarr; ';
                $link .= '<a href="/users/' . $item['cat_id'] . '/photo' . $item['item_id'] . '.html" class="tag_searchitem">' . $item['title'] . '</a>';
            }
            break;
        case 'catalog':
            if ( \cms\controller::installed('catalog') ) {
                $db->resetFilters();

                $item = $db->select('i.title', 'title')
                        ->select('i.id', 'item_id')
                        ->select('c.title', 'cat')
                        ->select('c.id', 'cat_id')
                        ->joinLeft('uc_cats', 'c', 'c.id = i.category_id')
                        ->filterEqual('i.id', (int) $item_id)
                        ->filterEqual('i.published', 1)
                        ->getItem('uc_items');

                $db->resetFilters();

                if ( $item ) {
                    $link = '<a href="/catalog/' . $item['cat_id'] . '" class="tag_searchcat">' . $item['cat'] . '</a> &rarr; ';
                    $link .= '<a href="/catalog/item' . $item['item_id'] . '.html" class="tag_searchitem">' . $item['title'] . '</a>';
                }
            }
            break;
        case 'video':
            $model = cms_model_video::initModel();

            $db->resetFilters();

            $item = $db->select('i.title', 'title')
                    ->select('i.id', 'item_id')
                    ->select('i.seolink', 'movie_seolink')
                    ->select('c.title', 'cat_title')
                    ->select('c.id', 'cat_id')
                    ->select('c.seolink', 'cat_seolink')
                    ->joinInner('video_category', 'c', 'c.id = i.cat_id')
                    ->filterEqual('i.id', (int) $item_id)
                    ->getItem('video_movie');

            $db->resetFilters();

            if ( $item ) {
                $link = '<a href="' . $model->getCatLink($item['cat_seolink'], $item['cat_id']) . '" class="tag_searchcat">' . $item['cat_title'] . '</a> &rarr; ';
                $link .= '<a href="' . $model->getMovieLink($item['cat_seolink'] . '/' . $item['movie_seolink'], $item['item_id']) . '" class="tag_searchitem">' . $item['title'] . '</a>';
            }
            break;

        case 'shop':
            $db->resetFilters();

            $item = $db->select('i.title', 'title')
                    ->select('i.seolink', 'seolink')
                    ->select('c.title', 'cat')
                    ->select('c.seolink', 'cat_seolink')
                    ->joinLeft('shop_cats', 'c', 'c.id = i.category_id')
                    ->filterEqual('i.id', (int) $item_id)
                    ->getItem('shop_items');

            $db->resetFilters();

            if ( $item ) {
                $link = '<a href="/shop/' . $item['cat_seolink'] . '" class="tag_searchcat">' . $item['cat'] . '</a> &rarr; ';
                $link .= '<a href="/shop/' . $item['seolink'] . '.html" class="tag_searchitem">' . $item['title'] . '</a>';
            }
            break;
        case 'maps':
            $db->resetFilters();

            $item = $db->select('i.title', 'title')
                    ->select('i.seolink', 'seolink')
                    ->select('c.title', 'cat')
                    ->select('c.seolink', 'cat_seolink')
                    ->joinLeft('map_cats', 'c', 'c.id = i.category_id')
                    ->filterEqual('i.id', (int) $item_id)
                    ->getItem('map_items');

            $db->resetFilters();

            if ( $item ) {
                $link = '<a href="/maps/' . $item['cat_seolink'] . '" class="tag_searchcat">' . $item['cat'] . '</a> &rarr; ';
                $link .= '<a href="/maps/' . $item['seolink'] . '.html" class="tag_searchitem">' . $item['title'] . '</a>';
            }
            break;
    }
    return $link;
}

function cmsTagsList()
{
    $db     = \cms\db::getInstance();
    $html   = '';

    $db->resetFilters();

    $tags = $db->select('i.tag')
            ->select('COUNT(i.tag)', 'num')
            ->groupBy('i.tag')
            ->orderBy('i.tag', 'ASC')
            ->get('tags', false, '');

    if ( $tags ) {
        foreach ( $tags as $tag ) {
            if ( $tag['tag'] ) {
                $html .= '<a href="/search/tag/' . urlencode($tag['tag']) . '">' . $tag['tag'] . '</a> (' . $tag['num'] . ') ';
            }
        }
    }

    return $html;
}
