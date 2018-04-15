<?php

namespace components\cp\actions;

class cache extends \cms\com_action
{

    public function actDelcache()
    {
        return $this->actDelete($this->request()->get('target', 'str'), $this->request()->get('target_id', 'int'));
    }

    public function actDelete($target, $target_id)
    {
        if ( empty($target) || empty($target_id) ) {
            \cmsCore::error404();
        }

        \cmsCore::deleteCache($target, $target_id);
    }

    public function actClear()
    {
        if ( !\cmsUser::isAdminCan('admin/config', $this->admin_access) ) {
            self::accessDenied();
        }

        \cmsCore::clearCache();

        \cmsCore::addSessionMessage($this->lang->ad_clear_cache_success, 'success');
    }

    public function doAfter($action_name)
    {
        \cmsCore::redirectBack();
    }

}
