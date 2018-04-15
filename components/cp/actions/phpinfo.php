<?php

namespace components\cp\actions;

class phpinfo extends \cms\com_action
{

    public function run()
    {
        if ( !\cmsUser::isAdminCan('admin/config', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_php_info);
        $this->page()->addPathway($this->lang->ad_site_setting, $this->genActionUrl('config'));
        $this->page()->addPathway($this->lang->ad_php_info);

        echo '<iframe width="100%" height="1000" srcdoc="';
        echo htmlspecialchars($this->phpInfo());
        echo '"></iframe>';
    }

    protected function phpInfo()
    {
        ob_start();

        phpinfo();

        return ob_get_clean();
    }

}
