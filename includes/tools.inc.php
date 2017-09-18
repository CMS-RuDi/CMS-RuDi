<?php

/*
 *                           InstantCMS v1.10.7
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2016
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

function icon($icon, $link, $title, $onClick = '')
{
    if ( $onClick == '' ) {
        $html = '<a class="icon" title="' . $title . '" href="' . $link . '"><img border="0" src="/images/icons/' . $icon . '.png" alt="' . $title . '"></a>';
    }
    else {
        $html = '<a class="icon" title="' . $title . '" href="' . $link . '" onClick="' . $onClick . '"><img border="0" src="/images/icons/' . $icon . '.png" alt="' . $title . '"></a>';
    }

    return $html;
}

function inArray($array, $item)
{
    $found = false;

    foreach ( $array as $key => $value ) {
        if ( $value == $item ) {
            $found = true;
        }
    }

    return $found;
}
