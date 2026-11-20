<?php

namespace components\content\backend\actions;

class config extends \cms\com_action {

    public function actView() {
        \cmsCore::includeFile('includes/jwtabs.php');
        $this->page->addHead(jwHeader());

        $this->page->addPathway($this->lang->ad_settings);

        self::addToolMenuItems([
            ['title' => $this->lang->save, 'link' => 'javascript:document.optform.submit();', 'icon' => 'save.gif'],
            ['title' => $this->lang->cancel, 'link' => $this->genActionUrl(), 'icon' => 'cancel.gif']
        ]);

        $tpl = $this->page->initTemplate('components/content/backend', 'config')
                ->assign('submit_uri', $this->genActionUrl('config', 'save'))
                ->assign('base_uri', $this->genActionUrl())
                ->assign('options', $this->options);

        echo jwTabs($tpl->fetch());
    }

    public function actSave() {
        if (!\cms\csrf_token::checkPost()) {
            \cmsCore::error404();
        }

        $options = [
            'readdesc' => $this->input->get('readdesc', 'int', 0),
            'is_url_cyrillic' => $this->input->get('is_url_cyrillic', 'int', 0),
            'rating' => $this->input->get('rating', 'int', 0),
            'perpage' => $this->input->get('perpage', 'int', 0),
            'pt_show' => $this->input->get('pt_show', 'int', 0),
            'pt_disp' => $this->input->get('pt_disp', 'int', 0),
            'pt_hide' => $this->input->get('pt_hide', 'int', 0),
            'autokeys' => $this->input->get('autokeys', 'int', 0),
            'hide_root' => $this->input->get('hide_root', 'int', 0),
            'img_small_w' => $this->input->get('img_small_w', 'int', 100),
            'img_big_w' => $this->input->get('img_big_w', 'int', 200),
            'img_sqr' => $this->input->get('img_sqr', 'int', 1),
            'img_users' => $this->input->get('img_users', 'int', 1),
            'watermark' => $this->input->get('watermark', 'int', 0),
            'watermark_only_big' => $this->input->get('watermark_only_big', 'int', 0)
        ];

        $this->saveOptions($this->name, $options);
        $this->core->addSessionMessage($this->lang->ad_config_save_success, 'success');
        $this->redirectToAction('config');
    }
}