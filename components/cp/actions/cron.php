<?php

namespace components\cp\actions;

class cron extends \cms\com_action
{

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/config', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_cron_mission);
        $this->page()->addPathway($this->lang->ad_site_setting, $this->genActionUrl('config'));
        $this->page()->addPathway($this->lang->ad_cron_mission);
    }

    public function actView()
    {
        self::addToolMenuItem($this->lang->ad_create_cron_mission, $this->genActionUrl('cron', 'add'), 'new.gif');

        $this->page->initTemplate('cp/applets', 'cron_tasks')->
                assign('edit_uri', $this->genActionUrl('cron', 'edit'))->
                assign('hide_uri', $this->genActionUrl('cron', 'hide'))->
                assign('show_uri', $this->genActionUrl('cron', 'show'))->
                assign('execute_uri', $this->genActionUrl('cron', 'execute'))->
                assign('delete_uri', $this->genActionUrl('cron', 'delete'))->
                assign('items', \cmsCron::getJobs(false))->
                display();
    }

    public function actAdd()
    {
        $this->page()->addPathway($this->lang->ad_create_cron_mission);
        \cms\backend::setTitle($this->lang->ad_create_cron_mission);

        return $this->actEdit(false, 'add');
    }

    public function actEdit($item_id = false, $do = 'edit')
    {
        self::addToolMenuItem($this->lang->save, 'javascript:document.addform.submit();', 'save.gif');
        self::addToolMenuItem($this->lang->cancel, 'javascript:history.go(-1);', 'cancel.gif');

        if ( $do == 'edit' ) {
            $item = cmsCron::getJobById($item_id);

            if ( empty($item) ) {
                \cmsCore::error404();
            }

            $this->page()->addPathway($item['job_name']);
            \cms\backend::setTitle($this->lang->ad_edit_mission);
        }

        $this->page->initTemplate('cp/applets', 'cron_task_edit')->
                assign('submit_uri', $this->genActionUrl('cron', 'submit'))->
                assign('item', !empty($item) ? $item : false)->
                display();
    }

    public function actSubmit()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $job_name     = $this->request()->get('job_name', 'str');
        $comment      = $this->request()->get('comment', 'str');
        $job_interval = $this->request()->get('job_interval', 'int');
        $enabled      = $this->request()->get('enabled', 'int');
        $component    = $this->request()->get('component', 'str');
        $model_method = $this->request()->get('model_method', 'str');
        $custom_file  = $this->request()->get('custom_file', 'str');
        $custom_file  = (mb_stripos($custom_file, 'image/') || mb_stripos($custom_file, 'upload/') || mb_stripos($custom_file, 'cache/')) ? '' : $custom_file;
        $custom_file  = preg_replace('/\.+\//', '', $custom_file);
        $class_name   = $this->request()->get('class_name', 'str');
        $class_method = $this->request()->get('class_method', 'str');

        $item_id = $this->request()->get('item_id', 'int');

        if ( empty($item_id) ) {
            \cmsCron::registerJob($job_name, [
                'interval'     => $job_interval,
                'component'    => $component,
                'model_method' => $model_method,
                'comment'      => $comment,
                'custom_file'  => $custom_file,
                'enabled'      => $enabled,
                'class_name'   => $class_name,
                'class_method' => $class_method
            ]);
        }
        else {
            \cmsCron::updateJob($item_id, [
                'job_name'     => $job_name,
                'job_interval' => $job_interval,
                'component'    => $component,
                'model_method' => $model_method,
                'comment'      => $comment,
                'custom_file'  => $custom_file,
                'is_enabled'   => $enabled,
                'class_name'   => $class_name,
                'class_method' => $class_method
            ]);
        }

        $this->redirectToAction('cron');
    }

    public function actShow($item_id)
    {
        if ( \cms\request::getInstance()->isAjax() ) {
            if ( !empty($item_id) ) {
                \cmsCron::jobEnabled($item_id, true);
            }

            \cmsCore::halt(1);
        }
        else {
            $this->redirectToAction('cron');
        }
    }

    public function actHide($item_id)
    {
        if ( \cms\request::getInstance()->isAjax() ) {
            if ( !empty($item_id) ) {
                \cmsCron::jobEnabled($item_id, false);
            }

            \cmsCore::halt(1);
        }
        else {
            $this->redirectToAction('cron');
        }
    }

    public function actDelete($item_id)
    {
        if ( !empty($item_id) ) {
            \cmsCron::removeJobById($id);
        }

        $this->redirectToAction('cron');
    }

    public function actExecute($item_id)
    {
        if ( !empty($item_id) ) {
            $job_result = \cmsCron::executeJobById($item_id);
        }

        if ( $job_result ) {
            \cmsCore::addSessionMessage($this->lang->ad_mission_success, 'success');
        }
        else {
            \cmsCore::addSessionMessage($this->lang->ad_mission_error, 'error');
        }

        $this->redirectToAction('cron');
    }

}
