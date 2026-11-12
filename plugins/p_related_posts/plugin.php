<?php

/*
 *                           InstantCMS v1.10.6
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2015
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class p_related_posts extends cmsPlugin {

    public function __construct() {
        $this->info['plugin'] = 'p_related_posts';
        $this->info['title'] = 'Похожие записи в блогах';
        $this->info['description'] = 'Добавляет в конец каждого поста список похожих записей';
        $this->info['author'] = 'Pasha && InstantCMS Team';
        $this->info['version'] = '1.0';
        $this->info['published'] = '1';

        $this->events[] = 'GET_POST';

        $this->config['search_mode'] = 2;              // Режим поиска по титлам и контенту
        $this->config['tags_mode'] = 1;              // Режим поиска по тегам
        $this->config['add_mode'] = 1;              // Дополнение предыдущими-следующими
        $this->config['limit'] = 4;              // Количество похожих блогов
        $this->config['truncate'] = 200;            // Кол-во символов после которого текст будет обрезан
        $this->config['blank_photo'] = 'no_image.png'; // Путь к изображению-заглушке
        $this->config['cash_time'] = 24;             // Время жизни кеша в часах

        parent::__construct();
    }

    public function execute($event = '', $item = array()) {
        switch ($event) {
            case 'GET_POST': return $this->getRelatedPosts($item);
        }

        return $item;
    }

    private function getRelatedPosts($item) {
        if (!($this->inCore->do == 'post' && $this->inCore->component == 'blogs')) {
            return $item;
        }

        $current_post_id = (int) $item['id'];
        $current_blog_id = (int) $item['blog_id'];
        $current_title = $item['title'];
        $search_mode = (int) $this->config['search_mode'];
        $tags_mode = (int) $this->config['tags_mode'];
        $add_mode = (int) $this->config['add_mode'];
        $need_found = (int) $this->config['limit'];
        $truncate = (int) $this->config['truncate'];
        $blank_photo = strip_tags($this->config['blank_photo']);
        $cash_time = (int) $this->config['cash_time'];
        $found_posts = array();
        $exception_ids = array();

        // защита от дурака
        if ($need_found < 1 || $truncate < 20) {
            return $item;
        }

        // Опционально может быть использовано кеширование
        if (cmsCore::isCached('rel_posts', $current_post_id, $cash_time, 'HOUR')) {
            $cache = cmsCore::getCache('rel_posts', $current_post_id);

            if (isset($item['plugins_output_after'])) {
                $item['plugins_output_after'] .= $cache;
            } else {
                $item['plugins_output_after'] = $cache;
            }

            return $item;
        }

        // Исключаем текущий, предыдущий и следующий пост
        $prev = \cms\db::cloneOf($this->inDB)->selectOnly('id')
                ->filterLt('id', $current_post_id)
                ->filterEqual('blog_id', $current_blog_id)
                ->orderBy('id', 'DESC')
                ->limit(0, 1)
                ->getItem('blog_posts');

        $next = \cms\db::cloneOf($this->inDB)->selectOnly('id')
                ->filterGt('id', $current_post_id)
                ->filterEqual('blog_id', $current_blog_id)
                ->orderBy('id', 'ASC')
                ->limit(0, 1)
                ->getItem('blog_posts');

        if ($prev) {
            $exception_ids[] = (int) $prev['id'];
        }

        if ($next) {
            $exception_ids[] = (int) $next['id'];
        }

        // Первый набор исключений готов
        $exception_ids[] = $current_post_id;

        if (in_array($search_mode, array(1, 2))) {// Если разрешен поиск по титлу или контенту
            $target = $search_mode == 1 ? 'i.title' : 'i.content_html';

            $escaped_title = $this->inDB->escape($current_title);

            $db = clone $this->inDB;
            $db->select('i.id');
            $db->select('i.title');
            $db->select('i.seolink', 'post_link');
            $db->select('i.content_html', 'content');
            $db->select('MIN(b.seolink)', 'blog_link');
            $db->select('MIN(img.fileurl)');
            $db->select('MATCH(' . $target . ") AGAINST('" . $escaped_title . "')", 'rel');
            $db->joinInner('blogs', 'b', 'b.id = i.blog_id');
            $db->joinLeft('upload_images', 'img', "img.target_id = i.id AND img.target = 'blog_post' AND img.component = 'blogs'");
            $db->filter("MATCH(" . $target . ") AGAINST('" . $escaped_title . "' IN BOOLEAN MODE)");
            $db->filterEqual('i.allow_who', 'all');
            $db->filterEqual('b.allow_who', 'all');
            $db->filterEqual('b.owner', 'user');
            $db->filterNotIn('i.id', $exception_ids);
            $db->groupBy('i.id');
            $db->orderBy('rel', 'DESC');
            $db->limit(0, $need_found);

            foreach ($db->get('blog_posts', false, false) as $r) {
                $found_posts[] = $r;
            }
        }

        $found = count($found_posts);

        if ($found < $need_found && $tags_mode) {// Если недобор и разрешен поиск по тегам
            $left_find = $need_found - $found;

            $exception2_ids = $exception_ids;

            foreach ($found_posts as $post) {// Обновляю список найденных постов
                $exception2_ids[] = (int) $post['id'];
            }

            // Вытягиваю идентификаторы постов имеющих такие же теги как и у текущего поста.
            $current_tags = $this->inDB->getRows('tags', "target = 'blogpost' AND item_id = " . $current_post_id, 'tag');
            $tag_list = array();

            foreach ($current_tags as $current_tag) {
                $tag_list[] = $current_tag['tag'];
            }

            if ($tag_list) {
                $db = clone $this->inDB;
                $db->select('item_id');
                $db->select('COUNT(item_id)', 'match_common_tags');
                $db->filterNotIn('item_id', $exception2_ids);
                $db->filterEqual('target', 'blogpost');
                $db->filterIn('tag', $tag_list);
                $db->groupBy('item_id');
                $db->orderBy('match_common_tags', 'DESC');
                $db->limit(0, $left_find);

                $target_ids = array();

                foreach ($db->get('tags', false, false) as $r) {
                    $target_ids[] = (int) $r['item_id'];
                }

                // Определять факт успеха операции в этом месте недостаточно, тут нужно гарантировать возврат числа
                if ($target_ids) {
                    // Вытягиваю посты
                    $db = clone $this->inDB;
                    $db->select('i.id');
                    $db->select('i.title');
                    $db->select('i.seolink', 'post_link');
                    $db->select('i.content_html', 'content');
                    $db->select('b.seolink', 'blog_link');
                    $db->select('img.fileurl');
                    $db->joinInner('blogs', 'b', 'b.id = i.blog_id');
                    $db->joinLeft('upload_images', 'img', "img.target_id = i.id AND img.target = 'blog_post' AND img.component = 'blogs'");
                    $db->filterEqual('i.allow_who', 'all');
                    $db->filterEqual('b.allow_who', 'all');
                    $db->filterEqual('b.owner', 'user');
                    $db->filterIn('i.id', $target_ids);
                    $db->groupBy('i.id');
                    $db->limit(0, $left_find);

                    foreach ($db->get('blog_posts', false, false) as $r) {
                        $found_posts[] = $r;
                    }
                }
            }
        }

        $found = count($found_posts);

        if ($found < $need_found && $add_mode == 1) { // Если опять недобор и разрешено дополнять сеседями
            $left_find = $need_found - $found;

            $exception3_ids = $exception_ids;

            foreach ($found_posts as $post) { // Обновляю список найденных постов
                $exception3_ids[] = (int) $post['id'];
            }

            // Смотрю как там оно распределено и сколько вообще осталось контента удовлетворяющего моим требованиям
            $base_query = function ($direction, $limit) use ($current_post_id, $exception3_ids) {
                $db = clone $this->inDB;
                $db->select('i.id');
                $db->select('i.title');
                $db->select('i.seolink', 'post_link');
                $db->select('i.content_html', 'content');
                $db->select('b.seolink', 'blog_link');
                $db->select('img.fileurl');
                $db->joinInner('blogs', 'b', 'b.id = i.blog_id');
                $db->joinLeft('upload_images', 'img', "img.target_id = i.id AND img.target = 'blog_post' AND img.component = 'blogs'");
                $db->filterEqual('i.allow_who', 'all');
                $db->filterEqual('b.allow_who', 'all');
                $db->filterEqual('b.owner', 'user');
                $db->filterNotIn('i.id', $exception3_ids);

                if ($direction == 'left') {
                    $db->filterLt('i.id', $current_post_id)->orderBy('i.id', 'DESC');
                } else {
                    $db->filterGt('i.id', $current_post_id)->orderBy('i.id', 'ASC');
                }

                $db->groupBy('i.id');
                $db->limit(0, (int) $limit);

                return $db->get('blog_posts', false, false);
            };

            $count_query = function ($direction) use ($current_post_id, $exception3_ids) {
                $db = clone $this->inDB;
                $db->joinInner('blogs', 'b', 'b.id = i.blog_id');
                $db->filterEqual('i.allow_who', 'all');
                $db->filterEqual('b.allow_who', 'all');
                $db->filterEqual('b.owner', 'user');
                $db->filterNotIn('i.id', $exception3_ids);

                if ($direction == 'left') {
                    $db->filterLt('i.id', $current_post_id);
                } else {
                    $db->filterGt('i.id', $current_post_id);
                }

                return $db->getCount('blog_posts');
            };

            $have_left = $count_query('left');
            $have_right = $count_query('right');

            // Все дальнейшие действия актуальны только если в базе остался подходящий под все условия контент
            if ($have_left + $have_right > 0) {
                // Разделяю на левую и правую половину
                $need_from_both = $left_find / 2;
                $need_from_left = floor($need_from_both);
                $need_from_right = ceil($need_from_both);

                // Коррекция если текущий оказался крайним
                if ($have_left < $need_from_left) {
                    $need_from_right = $need_from_right + $need_from_left - $have_left;
                }
                if ($have_right < $need_from_right) {
                    $need_from_left = $need_from_left + $need_from_right - $have_right;
                }

                if ($need_from_left == 0) {// Если слева оказался ноль
                    foreach ($base_query('right', $left_find) as $post) {
                        $found_posts[] = $post;
                    }
                } else {
                    foreach ($base_query('left', $need_from_left) as $post) {
                        $found_posts[] = $post;
                    }

                    foreach ($base_query('right', $need_from_right) as $post) {
                        $found_posts[] = $post;
                    }
                }
            }
        }

        // Если ничео нет, то возврат
        if (count($found_posts) == 0) {
            return $item;
        }

        $model = new cms_model_blogs();

        foreach ($found_posts as $key => $post) {
            if (!$found_posts[$key]['fileurl']) {
                $found_posts[$key]['fileurl'] = '/images/photos/small/' . $blank_photo;
            }
            $found_posts[$key]['url'] = $model->getPostURL($post['blog_link'], $post['post_link']);
            $found_posts[$key]['blog_url'] = $model->getBlogURL($post['bloglink']);
            $found_posts[$key]['content'] = mb_strimwidth(preg_replace('/\[cut=.*\]/ui', '', strip_tags($post['content'])), 0, $truncate, '...');
        }

        ob_start();

        cmsPage::initTemplate('plugins', 'p_related_posts.tpl')->
                assign('posts', $found_posts)->
                display('p_related_posts.tpl');

        $html = ob_get_clean();

        // Добавляем в спец ячейку вывод
        if (isset($item['plugins_output_after'])) {
            $item['plugins_output_after'] .= $html;
        } else {
            $item['plugins_output_after'] = $html;
        }

        // кешируем если нужно
        if ($cash_time > 0)
            cmsCore::saveCache('rel_posts', $current_post_id, $html);

        return $item;
    }
}
