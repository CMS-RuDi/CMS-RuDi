<?php

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Based on InstantCMS v1.10.7 by InstantSoft
 *          (Original code © InstantCMS Team, 2007-2016)
 *          (Modifications © CMS RuDi Team / DagIT, 2026)
 *
 *                      LICENSED BY GNU/GPL v2
 */

namespace components\cp\backend\actions;

class help extends \cms\com_action {
    protected $urls = [
        'menu' => 'https://cmsrudi.ru',
        'modules' => 'https://cmsrudi.ru',
        'content' => 'https://cmsrudi.ru',
        'cats' => 'https://cmsrudi.ru',
        'components' => 'https://cmsrudi.ru',
        'users' => 'https://cmsrudi.ru',
        'config' => 'https://cmsrudi.ru'
    ];

    public function run($topic = false) {
        if (empty($topic)) {
            $topic = $this->request()->get('topic', 'str');
        }

        if (isset($this->urls[$topic])) {
            \cmsCore::redirect($this->urls[$topic]);
        }

        \cmsCore::redirect('https://cmsrudi.ru');
    }
}