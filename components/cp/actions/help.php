<?php

namespace components\cp\actions;

class help extends \cms\com_action
{

    protected $urls = [
        'menu'       => 'http://www.instantcms.ru/wiki/doku.php/%D0%BC%D0%B5%D0%BD%D1%8E_%D1%81%D0%B0%D0%B9%D1%82%D0%B0',
        'modules'    => 'http://www.instantcms.ru/wiki/doku.php/%D0%BC%D0%BE%D0%B4%D1%83%D0%BB%D0%B8',
        'content'    => 'http://www.instantcms.ru/wiki/doku.php/%D0%BA%D0%BE%D0%BD%D1%82%D0%B5%D0%BD%D1%82',
        'cats'       => 'http://www.instantcms.ru/wiki/doku.php/%D0%BA%D0%BE%D0%BD%D1%82%D0%B5%D0%BD%D1%82',
        'components' => 'http://www.instantcms.ru/wiki/doku.php/%D0%BA%D0%BE%D0%BC%D0%BF%D0%BE%D0%BD%D0%B5%D0%BD%D1%82%D1%8B',
        'users'      => 'http://www.instantcms.ru/wiki/doku.php/%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D1%82%D0%B5%D0%BB%D0%B8',
        'config'     => 'http://www.instantcms.ru/wiki/doku.php/%D0%BD%D0%B0%D1%81%D1%82%D1%80%D0%BE%D0%B9%D0%BA%D0%B0_%D1%81%D0%B0%D0%B9%D1%82%D0%B0'
    ];

    public function run($topic = false)
    {
        if ( empty($topic) ) {
            $topic = $this->request()->get('topic', 'str');
        }

        if ( isset($this->urls[$topic]) ) {
            \cmsCore::redirect($this->urls[$topic]);
        }

        \cmsCore::redirect('http://www.instantcms.ru/wiki');
    }

}
