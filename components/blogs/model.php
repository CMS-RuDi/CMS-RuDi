<?php

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Based on InstantCMS v1.10.7 by InstantSoft
 *          (Original code © InstantCMS Team, 2007-2016)
 *          (Modifications © CMS RuDi Team / DagIT, 2026)
 *
 *                      LICENSED BY GNU/GPL v2
 */

class cms_model_blogs {

    public function __construct() {
        $this->db = \cms\db::getInstance();
        $this->config = cmsCore::getInstance()->loadComponentConfig('blogs');
    }

    public static function getDefaultConfig() {

        return array(
            'perpage' => 10,
            'perpage_blog' => 15,
            'update_date' => 0,
            'update_seo_link' => 0,
            'min_karma_private' => 0,
            'min_karma_public' => 5,
            'min_karma' => 1,
            'list_min_rating' => 0,
            'watermark' => 1,
            'img_on' => 1,
            'meta_keys' => '',
            'meta_desc' => '',
            'seo_user_access' => 0,
            'update_seo_link_blog' => 0
        );
    }

    /**
     * этот метод вызывается компонентом comments при создании нового комментария
     * метод обновляет количество комментариев для поста и для блога в целом
     */
    public function updateCommentsCount($target, $target_id) {
        if ($target != 'blog') {
            return false;
        }

        return cmsBlogs::updateCommentsCount($target, $target_id);
    }

    /**
     * этот метод вызывается компонентом comments при создании нового комментария
     * метод должен вернуть массив содержащий ссылку и заголовок поста, к которому
     * добавляется комментарий
     */
    public function getCommentTarget($target, $target_id) {
        $result = array();

        switch ($target) {
            case 'blog': $post = \cms\db::cloneOf($this->db)->select('i.title')
                        ->select('i.seolink')
                        ->select('b.seolink', 'bloglink')
                        ->joinLeft('blogs', 'b', 'b.id = i.blog_id')
                        ->filterEqual('i.id', (int) $target_id)
                        ->getItem('blog_posts');

                if (!$post) {
                    return false;
                }

                $result['link'] = $this->getPostURL($post['bloglink'], $post['seolink']);
                $result['title'] = $post['title'];
                break;
        }

        return ($result ? $result : false);
    }

    /**
     * этот метод вызывается компонентом comments при создании нового комментария
     * метод должен вернуть 0 или 1
     */
    public function getVisibility($target, $target_id) {
        $is_hidden = 0;

        switch ($target) {
            case 'blog':
                // получаем массив поста
                $post = $this->db->getRow('blog_posts', 'id = ' . (int) $target_id, 'blog_id, allow_who, published');
                if ($post['allow_who'] != 'all' || !$post['published']) {
                    $is_hidden = 1;
                }
                // получаем массив блога
                $blog = $this->db->getRow('blogs', 'id = ' . (int) $post['blog_id'], 'allow_who');
                if ($blog['allow_who'] != 'all') {
                    $is_hidden = 1;
                }
                break;
        }

        return $is_hidden;
    }

    /**
     * этот метод является хуком и вызывается при изменении рейтинга объекта blogpost
     * см. таблицу cms_rating_targets
     */
    public function updateRatingHook($target, $item_id, $points) {
        if ($target != 'blogpost' || !$item_id || abs($points) != 1) {
            return false;
        }

        $blog_id = $this->db->getField('blog_posts', $item_id, 'blog_id');

        $this->db->filterEqual('id', (int) $item_id)->increment('blog_posts', 'rating', $points);

        if ($blog_id) {
            $this->db->filterEqual('id', (int) $blog_id)->increment('blogs', 'rating', $points);
        }

        return true;
    }

    public function initBlog() {
        $inBlog = cmsBlogs::getInstance();
        $inBlog->owner = 'user';
        return $inBlog;
    }

    public static function getPostURL($bloglink, $seolink) {
        return '/blogs/' . $bloglink . '/' . $seolink . '.html';
    }

    public static function getBlogURL($bloglink, $page = 1, $cat_id = 0) {
        $cat_section = ($cat_id > 0 ? '/cat-' . $cat_id : '');
        $page_section = ($page > 1 ? '/page-' . $page : '');

        return '/blogs/' . $bloglink . $cat_section . $page_section;
    }

    public function getBlog($id_or_link) {
        return \cms\events::call('blogs.get_blog', $this->initBlog()->getBlog($id_or_link));
    }

    public function deleteBlog($ids) {
        $ids = is_array($ids) ? $ids : [$ids];

        foreach ($ids as $id) {
            \cms\events::call('blogs.delete', $id);
        }

        $this->initBlog()->deleteBlogd($ids);
    }

    public function getPost($id_or_link) {
        return \cms\events::call('blogs.get_post', $this->initBlog()->getPost($id_or_link));
    }

    public function getPosts($show_all = false, $is_short = false) {
        return \cms\events::call('blogs.get_posts', $this->initBlog()->getPosts($show_all, $this, $is_short));
    }
}