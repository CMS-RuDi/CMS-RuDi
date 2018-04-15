<?php

namespace components\cp\actions;

class noaccess extends \cms\com_action
{

    public function run()
    {
        $this->page()->setTitle($this->lang->access_denied);
        $this->page()->addPathway($this->lang->access_denied);

        \cmsPage::initTemplate('cp/applets', 'noaccess')->display();
    }

}
