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

function cmsKarma($target, $item_id)
{
    if ( !preg_match('/^([a-zA-Z0-9\_]+)$/ui', $target) ) {
        return;
    }

    $item_id = intval($item_id);

    $db = \cms\db::getInstance();

    $db->resetFilters();

    $item = $db->select('i.total_rating')
            ->select('i.total_votes')
            ->filterEqual('i.item_id', $item_id)
            ->filterEqual('i.target', $target)
            ->getItem('ratings_total');

    $db->resetFilters();

    if ( !$item ) {
        return array( 'points' => 0, 'votes' => 0 );
    }

    return array( 'points' => $item['total_rating'], 'votes' => $item['total_votes'] );
}

function cmsAlreadyKarmed($target, $item_id, $user_id)
{
    $db = \cms\db::getInstance();

    $db->resetFilters();

    $karmed = $db->filterEqual('target', $target)
            ->filterEqual('item_id', (int) $item_id)
            ->filterEqual('user_id', $user_id)
            ->getCount('ratings');

    $db->resetFilters();

    return $karmed;
}

function cmsAlreadyKarmedIP($target, $item_id, $ip)
{
    $db = \cms\db::getInstance();

    $db->resetFilters();

    $karmed = $db->filterEqual('target', $target)
            ->filterEqual('item_id', (int) $item_id)
            ->filterEqual('ip', $ip)
            ->getCount('ratings');

    $db->resetFilters();

    return $karmed;
}

function cmsSubmitKarma($target, $item_id, $points)
{
    $inUser  = cmsUser::getInstance();
    $db      = \cms\db::getInstance();
    $ip      = cmsCore::strClear($_SERVER['REMOTE_ADDR']);
    $target  = cmsCore::strClear($target);
    $points  = (int) $points;
    $item_id = (int) $item_id;

    if ( cmsAlreadyKarmed($target, $item_id, $inUser->id) ) {
        return false;
    }

    //вставляем новый голос
    $db->insert('ratings', array(
        'item_id' => $item_id,
        'points'  => $points,
        'ip'      => $ip,
        'target'  => $target,
        'user_id' => $inUser->id,
        'pubdate' => date('Y-m-d H:i:s')
    ));

    //проверяем была ли сделана агрегация для этой цели ранее
    $db->resetFilters();

    $is_agr = $db->filterEqual('target', $target)
            ->filterEqual('item_id', $item_id)
            ->getCount('ratings_total');

    //если была, то обновляем
    if ( $is_agr ) {
        $db->filterEqual('target', $target)
                ->filterEqual('item_id', $item_id)
                ->increment('ratings_total', 'total_rating', $points);

        $db->filterEqual('target', $target)
                ->filterEqual('item_id', $item_id)
                ->increment('ratings_total', 'total_votes', 1);
    }

    //если не было, то вставляем
    if ( !$is_agr ) {
        $db->insert('ratings_total', array(
            'target'       => $target,
            'item_id'      => $item_id,
            'total_rating' => $points,
            'total_votes'  => 1
        ));
    }

    //получаем информацию о цели
    $db->resetFilters();

    $info = $db->filterEqual('target', $target)->getItem('rating_targets');

    $db->resetFilters();

    // таблица цели хранится с префиксом CMS, builder сам добавит префикс
    $target_table = !empty($info['target_table']) ? $info['target_table'] : '';

    if ( $target_table && $db->prefix && strpos($target_table, $db->prefix) === 0 ) {
        $target_table = substr($target_table, strlen($db->prefix));
    }

    //если нужно, изменяем рейтинг автора цели
    if ( !empty($info['is_user_affect']) && !empty($info['user_weight']) && $target_table ) {
        $db->resetFilters();

        $target_item = $db->getItemById($target_table, $item_id);

        $db->resetFilters();

        if ( $target_item && !empty($target_item['user_id']) ) {
            $db->filterEqual('id', (int) $target_item['user_id'])
                    ->increment('users', 'rating', $points * $info['user_weight']);
        }
    }

    //проверяем наличие метода updateRatingHook(target, item_id, points) в модели
    //компонента, ответственного за цель
    if ( !empty($info['component']) ) {
        if ( class_exists('cms_model_' . $info['component']) ) {
            $model_class = 'cms_model_' . $info['component'];
            $model       = new $model_class();

            // если метод есть, пользуемся им
            if ( method_exists($model, 'updateRatingHook') ) {
                $model->updateRatingHook($target, $item_id, $points);
            }
            else {
                if ( $db->isFieldExists($target_table, 'rating') ) {
                    $db->filterEqual('id', $item_id)->increment($target_table, 'rating', $points);
                }
            }
        }
    }

    return true;
}

function cmsKarmaFormat($points)
{
    if ( $points == 0 ) {
        $html = '<span class="color_gray">0</span>';
    }
    elseif ( $points > 0 ) {
        $html = '<span class="color_green color_transition"><i class="fa fa-thumbs-up fa-lg"></i> +' . $points . '</span>';
    }
    else {
        $html = '<span class="color_red color_transition"><i class="fa fa-thumbs-down fa-lg"></i> ' . $points . '</span>';
    }
    return $html;
}

function cmsKarmaFormatSmall($points)
{
    if ( $points == 0 ) {
        $html = '<span class="color_gray">0</span>';
    }
    elseif ( $points > 0 ) {
        $html = '<span class="color_green">+' . $points . '</span>';
    }
    else {
        $html = '<span class="color_red">' . $points . '</span>';
    }
    return $html;
}

function cmsKarmaForm($target, $target_id, $points, $is_author = false)
{
    $inUser = cmsUser::getInstance();
    $inPage = cmsPage::getInstance();

    global $_LANG;

    $postkarma = cmsKarma($target, $target_id);

    $points = cmsKarmaFormat($postkarma['points']);

    $control = '';

    if ( $inUser->id && !$is_author ) {
        if ( !cmsAlreadyKarmed($target, $target_id, $inUser->id) ) {
            $inPage->addHeadJS('core/js/karma.js');

            $control .= '<div class="ratings_control">';
            $control .= '<a class="color_green color_transition" href="#" onclick="return plusKarma(\'' . $target . '\', \'' . $target_id . '\')" title="' . $_LANG['LIKE'] . '"><i class="fa fa-thumbs-up fa-lg"></i></a> ';
            $control .= '<a class="color_red color_transition" href="#" onclick="return minusKarma(\'' . $target . '\', \'' . $target_id . '\')" title="' . $_LANG['UNLIKE'] . '"><i class="fa fa-thumbs-down fa-lg"></i></a>';
            $control .= '</div>';
        }
    }

    return '<div class="karma_form">' .
            '<div id="karmapoints">' . $points . '</div>' .
            '<div id="karmavotes">' . $_LANG['RATING_VOTES_COUNT'] . ': ' . $postkarma['votes'] . '</div>' .
            '<div id="karmactrl">' . $control . '</div></div>';
}

function cmsKarmaButtons($target, $target_id, $points = 0, $is_author = false)
{
    $inUser = cmsUser::getInstance();
    $inPage = cmsPage::getInstance();
    $html   = '';

    global $_LANG;

    if ( $inUser->id && !$is_author ) {
        if ( !cmsAlreadyKarmed($target, $target_id, $inUser->id) ) {
            $inPage->addHeadJS('core/js/karma.js');

            return '<div class="karma_buttons"><div id="karmactrl">' .
                    '<div class="ratings_control"><a class="color_green color_transition" href="#" onclick="return plusKarma(\'' . $target . '\', \'' . $target_id . '\')" title="' . $_LANG['LIKE'] . '"><i class="fa fa-thumbs-up fa-lg"></i></a> <a class="color_red color_transition" href="#" onclick="return minusKarma(\'' . $target . '\', \'' . $target_id . '\')" title="' . $_LANG['UNLIKE'] . '"><i class="fa fa-thumbs-down fa-lg"></i></a></div>' .
                    '</div></div>';
        }
    }

    return '';
}

function cmsKarmaButtonsText($target, $target_id, $points = 0, $is_author = false)
{
    $inUser = cmsUser::getInstance();
    $inPage = cmsPage::getInstance();
    $html   = '';

    if ( $inUser->id && !$is_author ) {
        if ( !cmsAlreadyKarmed($target, $target_id, $inUser->id) ) {
            global $_LANG;

            $inPage->addHeadJS('core/js/karma.js');

            $control = ' <span><a class="color_green color_transition" href="#" onclick="return plusKarma(\'' . $target . '\', ' . $target_id . ');"><i class="fa fa-thumbs-up fa-lg"></i> ' . $_LANG['LIKE'] . '</a> ';
            $control .= '<a class="color_red color_transition" href="#" onclick="return minusKarma(\'' . $target . '\', ' . $target_id . ');"><i class="fa fa-thumbs-down fa-lg"></i> ' . $_LANG['UNLIKE'] . '</a></span>';

            $html .= '<span class="karma_buttons">';
            $html .= '<span id="karmactrl">' . $control . '</span>';
            $html .= '</span>';
        }
    }

    return $html;
}
