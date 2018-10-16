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

function smarty_modifier_callEvent($string, $event, $mode = 'normal', $data = false)
{
    if ( $mode == 'filter' ) {
        return \cms\events::filter($event, $string, $data);
    }

    return \cms\events::call($event, $string, $mode);
}
