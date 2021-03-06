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

define('PATH', __DIR__ . '/../..');

include(PATH . '/core/ajax/ajax_core.php');

if ( !$inUser->id ) {
    cmsCore::halt();
}

$target  = cmsCore::request('target', 'str');
$item_id = cmsCore::request('item_id', 'int');
$opt     = cmsCore::request('opt', array( 'plus', 'minus' ));

if ( !$target || !$item_id || !$opt ) {
    cmsCore::halt();
}

if ( !preg_match('/^([a-zA-Z0-9\_]+)$/iu', $target) ) {
    cmsCore::halt();
}

cmsCore::loadLib('karma');

if ( $opt == 'plus' ) {
    cmsSubmitKarma($target, $item_id, 1);
}

if ( $opt == 'minus' ) {
    cmsSubmitKarma($target, $item_id, -1);
}

$postkarma = cmsKarma($target, $item_id);

$points = cmsKarmaFormat($postkarma['points']);

echo $points;

cmsCore::halt();
