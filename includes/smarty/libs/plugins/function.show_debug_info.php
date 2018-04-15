<?php

/*
 *                           CMS RuDi v1.0.0
 *                        http://www.cmsrudi.ru/
 *
 *                   written by DS Soft Team, 2017-2018
 *                  produced by DS Soft, (www.ds-soft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

function smarty_function_show_debug_info($params, $template)
{
    \cmsCore::showDebugInfo();
}
