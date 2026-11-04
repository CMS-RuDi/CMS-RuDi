<?php

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Written by CMS RuDi Team, 2026
 *           produced by DagIT, (https://dagit.su)
 *
 *                    LICENSED BY GNU/GPL v2
 */

namespace components\cp\actions;

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