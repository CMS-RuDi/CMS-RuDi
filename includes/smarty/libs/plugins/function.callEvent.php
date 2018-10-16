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

function smarty_function_callEvent($params, &$smarty)
{
    if ( !empty($params['event']) ) {
        if ( !empty($params['mode']) && $params['mode'] == 'filter' ) {
            return \cms\events::filter($params['event'], (empty($params['item']) ? '' : $params['item']), (empty($params['data']) ? false : $params['data']));
        }

        return \cms\events::call($params['event'], (empty($params['item']) ? array() : $params['item']), (empty($params['mode']) ? 'normal' : $params['mode']));
    }
}
