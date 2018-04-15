<?php

namespace components\arhive;

class backend extends \cms\backend
{

    /**
     * @var \components\content\model
     */
    public $model;

    public function actionIndex()
    {
        $this->page->addPathway($this->lang->ad_articles_archive);

        self::addToolMenuItem($this->lang->ad_settings, $this->genActionUrl('config'), 'config.gif');
        self::addToolMenuItem($this->lang->ad_delete_selected, "javascript:checkSel('" . self::getBackend('content')->genActionUrl('delete', false, [ 'multiple' => 1 ]) . "');", 'delete.gif');

        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '30' ],
            [ 'title' => $this->lang->ad_create, 'field' => 'pubdate', 'width' => '80', 'filter' => 15, 'fdate' => '%d/%m/%Y' ],
            [ 'title' => $this->lang->title, 'field' => 'title', 'width' => '', 'filter' => 15, 'link' => self::getBackend('content')->genActionUrl('edit', '%id%') ],
            [ 'title' => $this->lang->ad_partition, 'field' => 'category_id', 'width' => '100', 'filter' => 1, 'filterlist' => cpGetList(\cmsDatabase::getInstance()->prefix . \components\content\model::CATEGORY_TABLE), 'prc' => 'cpCatById' ],
        ];

        $actions = [
            [ 'title' => $this->lang->ad_view_online, 'icon' => 'search.gif', 'link' => '/%seolink%.html' ],
            [ 'title' => $this->lang->ad_to_articles_catalog, 'icon' => 'arhive_off.gif', 'link' => $this->genActionUrl('arhive_off', '%id%') ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_delete_materials, 'link' => self::getBackend('content')->genActionUrl('delete', '%id%') ]
        ];

        cpListTable(\cmsDatabase::getInstance()->prefix . \components\content\model::CONTENT_TABLE, $fields, $actions, 'is_arhive=1');
    }

    public function actionArhiveOff($item_id)
    {
        $this->model('content')->moveFromArhive($item_id);

        $this->redirectToAction();
    }

    public function actionConfig()
    {
        $this->page->addPathway($this->lang->ad_settings);

        self::addToolMenuItem($this->lang->ad_list_of_articles, $this->genActionUrl(), 'folders.gif');

        $this->page->initTemplate('components/arhive/backend', 'config')->
                assign('submit_uri', $this->genActionUrl('save_config'))->
                assign('base_uri', $this->genActionUrl())->
                assign('options', $this->options)->
                display();
    }

    public function actionSaveConfig()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $options = [
            'source' => $this->request->get('source', 'str', '')
        ];

        $this->saveOptions($this->name, $options);

        \cmsCore::addSessionMessage($this->lang->ad_config_save_success, 'success');

        $this->redirectToAction('config');
    }

}
