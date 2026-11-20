<?php

namespace components\content\backend\actions;

class delete extends \cms\com_action {

    public function actCategory($category_id = 0, $content = false) {
        if (!\cms\csrf_token::checkToken()) {
            \cmsCore::error404();
        }

        $is_with_content = $content == 'content' ? true : false;

        $this->model->deleteCategory($category_id, $is_with_content);

        \cmsCore::addSessionMessage(($is_with_content ? $this->lang->ad_category_removed : $this->lang->ad_category_removed_not_article), 'success');

        $this->core->redirectBack();
    }

    public function actContent($article_id = false) {
        if (!\cms\csrf_token::checkToken()) {
            \cmsCore::error404();
        }

        if (!empty($article_id)) {
            $this->model->deleteArticle($article_id);
            \cmsCore::addSessionMessage($this->lang->ad_article_remove, 'success');
        } else {
            $items = $this->input->get('item', 'array_int');

            if (!empty($items)) {
                $this->model->deleteArticles($items);
                \cmsCore::addSessionMessage($this->lang->ad_articles_remove, 'success');
            }
        }

        \cmsCore::redirectBack();
    }
}