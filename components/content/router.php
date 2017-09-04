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

function routes_content()
{
    return array(
        array(
            '_uri' => '/^content\/top.html$/i',
            'do'   => 'best'
        ),
        array(
            '_uri' => '/^content\/add.html$/i',
            'do'   => 'addarticle'
        ),
        array(
            '_uri' => '/^content\/edit([0-9]+).html$/i',
            'do'   => 'editarticle',
            1      => 'id'
        ),
        array(
            '_uri' => '/^content\/delete([0-9]+).html$/i',
            'do'   => 'deletearticle',
            1      => 'id'
        ),
        array(
            '_uri' => '/^content\/publish([0-9]+).html$/i',
            'do'   => 'publisharticle',
            1      => 'id'
        ),
        array(
            '_uri' => '/^content\/my.html$/i',
            'do'   => 'my'
        ),
        array(
            '_uri' => '/^content\/my([0-9]+).html$/i',
            'do'   => 'my',
            1      => 'page'
        ),
        array(
            '_uri' => '/^content\/(.+)\/page\-([0-9]+).html$/i',
            'do'   => 'read',
            1      => 'seolink',
            2      => 'page'
        ),
        array(
            '_uri' => '/^content\/(.+).html$/i',
            'do'   => 'read',
            1      => 'seolink'
        ),
        array(
            '_uri' => '/^content\/(.+)\/page\-([0-9]+)$/i',
            'do'   => 'view',
            1      => 'seolink',
            2      => 'page'
        ),
        array(
            '_uri' => '/^content\/(.*)$/i',
            'do'   => 'view',
            1      => 'seolink'
        )
    );
}
