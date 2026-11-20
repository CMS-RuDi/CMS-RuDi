<?php

namespace components\content\backend\actions;

class save extends \cms\com_action {

    public function actCategory($category_id = 0) {
        if (!\cms\csrf_token::checkPost()) {
            \cmsCore::error404();
        }

        if (!empty($category_id)) {
            // получаем старую категорию
            $old = $this->model->db->filterEqual('id', (int) $category_id)->getItem(\components\content\model::CATEGORY_TABLE);

            if (!$old) {
                \cmsCore::error404();
            }
        }

        $category['title'] = $this->input->get('title', 'str', $this->lang->ad_category_untitled);

        $category['url'] = $this->input->get('url', 'str');
        if ($category['url']) {
            $category['url'] = \cms\lang::slug($category['url'], !$this->options['is_url_cyrillic']);
        }

        $category['parent_id'] = $this->input->get('parent_id', 'int');
        $category['description'] = $this->input->get('description', 'html');
        $category['published'] = $this->input->get('published', 'int', 0);
        $category['showdate'] = $this->input->get('showdate', 'int', 0);
        $category['showcomm'] = $this->input->get('showcomm', 'int', 0);
        $category['orderby'] = $this->input->get('orderby', 'str');
        $category['orderto'] = $this->input->get('orderto', 'str');
        $category['modgrp_id'] = $this->input->get('modgrp_id', 'int', 0);
        $category['maxcols'] = $this->input->get('maxcols', 'int', 0);
        $category['showtags'] = $this->input->get('showtags', 'int', 0);
        $category['showrss'] = $this->input->get('showrss', 'int', 0);
        $category['showdesc'] = $this->input->get('showdesc', 'int', 0);
        $category['is_public'] = $this->input->get('is_public', 'int', 0);
        $category['tpl'] = $this->input->get('tpl', 'str', 'com_content_view.tpl');
        $category['pagetitle'] = $this->input->get('pagetitle', 'str', '');
        $category['meta_desc'] = $this->input->get('meta_desc', 'str');
        $category['meta_keys'] = $this->input->get('meta_keys', 'str');

        $category['cost'] = $this->input->get('cost', 'str', 0);
        if (!is_numeric($category['cost'])) {
            $category['cost'] = '';
        }

        $album = [
            'id' => $this->input->get('album_id', 'int', 0),
            'header' => $this->input->get('album_header', 'str', ''),
            'orderby' => $this->input->get('album_orderby', 'str', ''),
            'orderto' => $this->input->get('album_orderto', 'str', ''),
            'maxcols' => $this->input->get('album_maxcols', 'int', 0),
            'max' => $this->input->get('album_max', 'int', 0)
        ];

        if ($album['id']) {
            $category['photoalbum'] = serialize($album);
        } else {
            $category['photoalbum'] = '';
        }

        if (empty($category_id)) {
            $ns = $this->core->nestedSetsInit(\components\content\model::CATEGORY_TABLE);

            $category_id = $ns->AddNode($category['parent_id']);
            $category['id'] = $category_id;
            $category['seolink'] = \cmsCore::generateCatSeoLink($category, \components\content\model::CATEGORY_TABLE, $this->options['is_url_cyrillic']);

            $this->model->db->update(\components\content\model::CATEGORY_TABLE, (int) $category_id, $category);

            $inmenu = $this->input->get('createmenu', 'str', '');

            if ($inmenu) {
                $this->createMenuItem($inmenu, $category_id, $category['title'], 'category');
            }

            \cmsCore::addSessionMessage($this->lang->ad_category_add, 'success');
        } else {
            // если сменили категорию
            if ($old['parent_id'] != $category['parent_id']) {
                // перемещаем ее в дереве
                $this->core->nestedSetsInit(\components\content\model::CATEGORY_TABLE)->MoveNode($category_id, $category['parent_id']);

                // обновляем сеолинки категорий
                $this->model->db->updateNsCategorySeoLink(\components\content\model::CATEGORY_TABLE, $category_id, $this->options['is_url_cyrillic']);

                // Обновляем ссылки меню на категории
                $this->model->updateCatMenu();

                // обновляем сеолинки всех вложенных статей
                $this->model->updateArticlesSeoLink($category_id);

                \cmsCore::addSessionMessage($this->lang->ad_category_new_url, 'info');
            }

            $this->model->db->update(\components\content\model::CATEGORY_TABLE, (int) $category_id, $category);

            // если пришел запрос на обновление ссылок
            // и категория не менялась - если менялась, мы выше все обновили
            if ($this->input->has('update_seolink') && ($old['parent_id'] == $category['parent_id'])) {
                // обновляем сеолинки категорий
                $this->model->db->updateNsCategorySeoLink(\components\content\model::CATEGORY_TABLE, $category_id, $this->options['is_url_cyrillic']);

                // Обновляем ссылки меню на категории
                $this->model->updateCatMenu();

                // обновляем сеолинки всех вложенных статей
                $this->model->updateArticlesSeoLink($category_id);

                \cmsCore::addSessionMessage($this->lang->ad_section_and_articles_new_url, 'info');
            }

            \cmsCore::addSessionMessage($this->lang->ad_category_saved, 'success');
        }

        if (!$this->input->get('is_access', 'int', 0)) {
            $showfor = $this->input->get('showfor', 'array_int', []);
            \cmsCore::setAccess($category_id, $showfor, 'category');
        } else {
            \cmsCore::clearAccess($category_id, 'category');
        }

        $this->redirectToAction('view', $category_id);
    }

    public function actContent($article_id = 0) {
        if (!\cms\csrf_token::checkPost()) {
            \cmsCore::error404();
        }

        $olddate = $this->input->get('olddate', 'str', ''); // Нигде не используется, проверить нужно ли, если нет удалить

        $article['category_id'] = $this->input->get('category_id', 'int', 1);
        $article['title'] = $this->input->get('title', 'str');
        $article['url'] = $this->input->get('url', 'str');
        $article['showtitle'] = $this->input->get('showtitle', 'int', 0);
        $article['description'] = $this->input->get('description', 'html', '');
        $article['content'] = $this->input->get('content', 'html', '');

        $article['published'] = $this->input->get('published', 'int', 0);
        $article['showdate'] = $this->input->get('showdate', 'int', 0);
        $article['showlatest'] = $this->input->get('showlatest', 'int', 0);
        $article['showpath'] = $this->input->get('showpath', 'int', 0);
        $article['comments'] = $this->input->get('comments', 'int', 0);
        $article['canrate'] = $this->input->get('canrate', 'int', 0);

        $enddate_raw = trim($this->input->get('enddate', 'str'));

        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $enddate_raw, $enddate)) {
            $article['enddate'] = $enddate[3] . '-' . $enddate[2] . '-' . $enddate[1];
        } else {
            $article['enddate'] = null;
        }

        $article['is_end'] = $this->input->get('is_end', 'int', 0);
        $article['pagetitle'] = $this->input->get('pagetitle', 'str', '');
        $article['tags'] = $this->input->get('tags', 'str');

        $pubdate_raw = trim($this->input->get('pubdate', 'str'));

        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $pubdate_raw, $date)) {
            $article['pubdate'] = $date[3] . '-' . $date[2] . '-' . $date[1] . ' ' . date('H:i');
        } else {
            $article['pubdate'] = date('Y-m-d H:i:s');
        }

        $article['user_id'] = $this->input->get('user_id', 'int', $this->user->id);
        $article['tpl'] = $this->input->get('tpl', 'str', 'com_content_read.tpl');

        $autokeys = $this->input->get('autokeys', 'int');

        switch ($autokeys) {
            case 1: $article['meta_keys'] = $this->core->getKeywords($article['content']);
                $article['meta_desc'] = $article['title'];
                break;

            case 2: $article['meta_desc'] = strip_tags($article['description']);
                $article['meta_keys'] = $article['tags'];
                break;

            case 3: $article['meta_desc'] = $this->input->get('meta_desc', 'str');
                $article['meta_keys'] = $this->input->get('meta_keys', 'str');
                break;
        }

        if (empty($article_id)) {
            $article_id = $this->model->addArticle($article);

            $inmenu = $this->input->get('createmenu', 'str', '');

            if ($inmenu) {
                $this->createMenuItem($inmenu, $article_id, $article['title']);
            }

            \cmsCore::addSessionMessage($this->lang->ad_article_save, 'success');

            if (!isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist']) == 0) {
                $redirect_uri = $this->genActionUrl('view', $article['category_id']);
            } else {
                $redirect_uri = $this->genActionUrl('edit');
            }
        } else {
            $this->model->updateArticle($article_id, $article);

            \cmsCore::addSessionMessage($this->lang->ad_article_add, 'success');

            $redirect_uri = $this->genActionUrl('view', $article['category_id']);
        }

        if (!$this->input->get('is_public', 'int', 0)) {
            $showfor = $this->input->get('showfor', 'array_int', []);
            \cmsCore::setAccess($article_id, $showfor, 'material');
        } else {
            \cmsCore::clearAccess($article_id, 'material');
        }

        $file = 'article' . $article_id . '.jpg';

        if ($this->input->get('delete_image', 'int', 0)) {
            @unlink(PATH . '/images/photos/small/' . $file);
            @unlink(PATH . '/images/photos/medium/' . $file);
        } else {
            $inUploadPhoto = \cmsUploadPhoto::getInstance();

            // Выставляем конфигурационные параметры
            $inUploadPhoto->upload_dir = PATH . '/images/photos/';
            $inUploadPhoto->small_size_w = $this->options['img_small_w'];
            $inUploadPhoto->medium_size_w = $this->options['img_big_w'];
            $inUploadPhoto->thumbsqr = $this->options['img_sqr'];
            $inUploadPhoto->is_watermark = $this->model->config['watermark'];
            $inUploadPhoto->input_name = 'picture';
            $inUploadPhoto->filename = $file;

            // Процесс загрузки фото
            $inUploadPhoto->uploadPhoto();
        }

        \cmsCore::redirect($redirect_uri);
    }
}