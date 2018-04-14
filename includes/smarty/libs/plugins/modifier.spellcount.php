<?php

/* * *************************************************************************** */

//                                                                            //
//                           InstantCMS v1.10.6                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2015                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/* * *************************************************************************** */
function smarty_modifier_spellcount($num, $one, $two, $many, $is_full = 1)
{
    if ( $num % 10 == 1 && $num % 100 != 11 ) {
        $str = $one;
    }
    elseif ( $num % 10 >= 2 && $num % 10 <= 4 && ($num % 100 < 10 || $num % 100 >= 20) ) {
        $str = $two;
    }
    else {
        $str = $many;
    }

    return ($is_full ? $num . ' ' : '') . $str;
}
