<?php

/*
 *                           CMS RuDi v1.0.0
 *                        http://www.instantcms.ru/
 *
 *                   written by CMS RuDi Team, 2017-2018
 *                   produced by DS-Soft, (ds-soft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

function smarty_block_callEvent($params, $content, &$smarty, &$repeat)
{
    if ( $repeat === false && !empty($params['event']) ) {
        if ( !empty($params['mode']) && $params['mode'] == 'filter' ) {
            return \cms\events::filter($params['event'], $content, (empty($params['data']) ? false : $params['data']));
        }

        return \cms\events::call($params['event'], $content, (empty($params['mode']) ? 'normal' : $params['mode']));
    }
}
