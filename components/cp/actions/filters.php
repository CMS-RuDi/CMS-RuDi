<?php

namespace components\cp\actions;

class filters extends \cms\com_action
{

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/plugins', $this->admin_access) || !\cmsUser::isAdminCan('admin/filters', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_filters);
        $this->page()->addPathway($this->lang->ad_filters);
    }

    public function run(...$params)
    {
        if ( empty($params) ) {
            $do = $this->request()->get('do', 'str');

            if ( $do == 'show' || $do == 'hide' ) {
                if ( $do == 'show' ) {
                    dbShow('cms_filters', $this->request()->get('id', 'int', 0));
                }

                if ( $do == 'hide' ) {
                    dbHide('cms_filters', $this->request()->get('id', 'int', 0));
                }

                \cmsCore::halt(1);
            }
        }

        parent::run($params);
    }

    public function actView()
    {
        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '30' ],
            [ 'title' => $this->lang->title, 'field' => 'title', 'width' => '250' ],
            [ 'title' => $this->lang->description, 'field' => 'description', 'width' => '' ],
            [ 'title' => $this->lang->ad_enable, 'field' => 'published', 'width' => '100' ]
        ];

        cpListTable('cms_filters', $fields, []);
    }

}
