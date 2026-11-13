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

class noaccess extends \cms\com_action {

    public function run() {
        $this->page()->setTitle($this->lang->access_denied);
        $this->page()->addPathway($this->lang->access_denied);

        \cmsPage::initTemplate('cp/applets', 'noaccess')->display();
    }
}