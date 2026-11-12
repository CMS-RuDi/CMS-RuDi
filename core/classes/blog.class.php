<?php

/*
 *                           InstantCMS v1.10.7
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2017
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class cmsBlogs {
    private static $instance;
    public $owner;
    private $targets = array(
        'tags' => 'blogpost',
        'rating' => 'blogpost',
        'comments' => 'blog',
        'actions_post' => 'add_post',
        'actions_blog' => 'add_blog'
    );

    private function __construct() {
        $this->inDB = \cms\db::getInstance();
        cmsCore::loadLanguage('components/blogs');
        cmsCore::loadLib('tags');
        cmsCore::loadLib('karma');
    }

    private function __clone() {

    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Устанавливает таргеты
     * @param array $targets
     * @return bool
     */
    public function setTargets($targets = array()) {
        $this->targets = $targets;
        return true;
    }

    public function getTarget($target = '') {
        return isset($this->targets[$target]) ? $this->targets[$target] : '';
    }

    /**
     * Возвращает категории блога
     * @param int $blog_id
     * @return array $cats
     */
    public function getBlogCats($blog_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $cats = $db->select('i.*')
                ->select('IFNULL(COUNT(p.id), 0)', 'post_count')
                ->joinLeft('blog_posts', 'p', 'p.cat_id = i.id AND p.published = 1')
                ->filterEqual('i.blog_id', (int) $blog_id)
                ->groupBy('i.id')
                ->get('blog_cats', false, '');

        $db->resetFilters();

        if (!$cats) {
            return false;
        }

        return \cms\events::call('blog.get_categories', $cats);
    }

    /**
     * Возвращает категорию блога
     * @param int $cat_id
     * @return array $cat
     */
    public function getBlogCategory($cat_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $cat = $db->getItemById('blog_cats', (int) $cat_id);

        $db->resetFilters();

        return $cat ? \cms\events::call('blog.get_category', $cat) : false;
    }

    /**
     * Добавляет категорию блога
     * @param array $item
     * @return int
     */
    public function addBlogCategory($item) {
        return $this->inDB->insert('blog_cats', \cms\events::call('blog.add_category', $item));
    }

    /**
     * Обновляет данные категории блога
     * @param int $cat_id
     * @param array $item
     * @return bool
     */
    public function updateBlogCategory($cat_id, $item) {
        $item = \cms\events::call('blog.update_category', $item);

        unset($item['id']);

        return $this->inDB->update('blog_cats', (int) $cat_id, $item);
    }

    /**
     * Удаляет категорию
     * @param int $cat_id
     * @return bool
     */
    public function deleteBlogCategory($cat_id) {
        \cms\events::call('blog.delete_category', $cat_id);

        $this->inDB->resetFilters();

        $this->inDB->filterEqual('cat_id', (int) $cat_id)->updateFiltered('blog_posts', array(
            'cat_id' => 0
        ));

        return $this->inDB->delete('blog_cats', (int) $cat_id);
    }

    /**
     * Возвращает массив авторов блога
     * @param int $blog_id
     * @return array $authors
     */
    public function getBlogAuthors($blog_id) {
        $authors = array();

        $db = $this->inDB;

        $db->resetFilters();

        $rs = $db->selectOnly('i.user_id')
                ->filterEqual('i.blog_id', (int) $blog_id)
                ->get('blog_authors', false, '');

        $db->resetFilters();

        if ($rs) {
            foreach ($rs as $u) {
                $authors[] = $u['user_id'];
            }
        }

        return \cms\events::call('blog.get_authors', $authors);
    }

    /**
     * Возвращает массив навигационными постами
     * @param int $post_id
     * @return array $navigation
     */
    public function getPostNavigation($post_id, $blog_id, $ownerModel, $blog_link) {
        $navigation = array();

        $db = $this->inDB;

        $db->resetFilters();
        $db->select('i.seolink')->select('i.title')->filterLt('i.id', $post_id);
        if (isset($blog_id)) {
            $db->filterEqual('i.blog_id', $blog_id);
        }
        $navigation['next'] = $db->orderBy('i.id', 'DESC')->getItem('blog_posts');

        $db->resetFilters();
        $db->select('i.seolink')->select('i.title')->filterGt('i.id', $post_id);
        if (isset($blog_id)) {
            $db->filterEqual('i.blog_id', $blog_id);
        }
        $navigation['prev'] = $db->orderBy('i.id', 'ASC')->getItem('blog_posts');

        $db->resetFilters();

        if ($navigation['next']) {
            $navigation['next']['url'] = $ownerModel->getPostURL($blog_link, $navigation['next']['seolink']);
        }

        if ($navigation['prev']) {
            $navigation['prev']['url'] = $ownerModel->getPostURL($blog_link, $navigation['prev']['seolink']);
        }

        return $navigation;
    }

    /**
     * Возвращает блог по ссылке или по id
     * @param int or string $id_or_link
     * @return array $blog
     */
    public function getBlog($id_or_link = 0) {
        if (is_numeric($id_or_link)) {
            $where = 'i.id = ' . (int) $id_or_link;
        } else {
            $where = "i.seolink = '" . $this->inDB->escape($id_or_link) . "'";
        }

        return $this->getBlogItem($where);
    }

    /**
     * Возвращает блог по user_id
     * @param int $user_id
     * @return array $blog
     */
    public function getBlogByUserId($user_id) {
        return $this->getBlogItem('i.user_id = ' . (int) $user_id);
    }

    /**
     * Возвращает блог по запросу
     * @param str $where
     * @return array $blog
     */
    private function getBlogItem($where) {
        $db = $this->inDB;

        if (isset($this->owner)) {
            $where .= " AND i.owner = '" . $db->escape($this->owner) . "'";
        }

        $db->resetFilters();

        $blog = $db->filter($where)->getItem('blogs');

        $db->resetFilters();

        if (!$blog) {
            return false;
        }

        $blog['pubdate'] = cmsCore::dateFormat($blog['pubdate']);

        return \cms\events::call('blog.get_blog', $blog);
    }

    /**
     * Получает количество блогов по условиям
     * @return int
     */
    public function getBlogsCount() {
        if (isset($this->owner)) {
            $this->inDB->filterEqual('i.owner', $this->owner);
        }

        return $this->inDB->getCount('blogs');
    }

    /**
     * Возвращает блоги
     * @param obj $ownerModel
     * @return array $blogs
     */
    public function getBlogs($ownerModel) {
        $db = $this->inDB;

        if (isset($this->owner)) {
            $db->filterEqual('i.owner', $this->owner);
        }

        $db->select('i.*')
                ->select('COUNT(p.id)', 'records')
                ->select('u.nickname')
                ->select('u.login')
                ->joinLeft('blog_posts', 'p', 'p.blog_id = i.id')
                ->joinLeft('users', 'u', 'u.id = i.user_id')
                ->groupBy('i.id');

        $blogs = $db->get('blogs', false, '');

        if (!$blogs) {
            return false;
        }

        foreach ($blogs as $key => $blog) {
            $blogs[$key]['pubdate'] = cmsCore::dateFormat($blog['pubdate']);
            $blogs[$key]['url'] = $ownerModel->getBlogURL($blog['seolink']);
        }

        return \cms\events::call('blog.get_blogs', $blogs);
    }

    /**
     * Генерирует ссылку на пост в блоге
     * @param array $post
     * @return str $seolink
     */
    public function generatePostSeoLink($post) {
        $seolink = cmsCore::strToURL($post['title']);

        $db = $this->inDB;

        $db->resetFilters();
        $db->filterEqual('i.seolink', $seolink);
        if (@$post['id']) {
            $db->filterNotEqual('i.id', (int) $post['id']);
        }

        $is_exists = $db->getCount('blog_posts');

        $db->resetFilters();

        if ($is_exists) {
            $seolink .= '-' . (@$post['id'] ? $post['id'] : date("d-i"));
        }

        return $seolink;
    }

    /**
     * Генерирует ссылку на блог
     * @param array $blog
     * @return str $seolink
     */
    public function generateBlogSeoLink($blog) {
        $seolink = cmsCore::strToURL($blog['title']);

        $db = $this->inDB;

        $db->resetFilters();
        $db->filterEqual('i.seolink', $seolink);
        if (@$blog['id']) {
            $db->filterNotEqual('i.id', (int) $blog['id']);
        }

        $is_exists = $db->getCount('blogs');

        $db->resetFilters();

        if ($is_exists) {
            $seolink .= '-' . (@$blog['id'] ? $blog['id'] : date("d-i"));
        }

        return $seolink;
    }

    /**
     * Возвращает количество постов, ожидающих модерации
     * @param int $blog_id
     * @return int
     */
    public function getModerationCount($blog_id = 0) {
        $db = $this->inDB;

        $db->resetFilters();

        if ($blog_id) {
            $db->filterEqual('i.blog_id', (int) $blog_id);
        }

        $db->filterEqual('i.published', 0);

        $count = $db->getCount('blog_posts');

        $db->resetFilters();

        return $count;
    }

    /**
     * Добавляет блог
     * @param array $item
     * @return int $blog_id
     */
    public function addBlog($item) {
        $db = $this->inDB;

        $item['id'] = $db->insert('blogs', \cms\events::call('blog.add', $item));

        if ($item['id']) {
            $item['seolink'] = $this->generateBlogSeoLink($item);

            $db->update('blogs', (int) $item['id'], array(
                'seolink' => $item['seolink']
            ));
        }

        return $item['id'] ? $item['id'] : false;
    }

    /**
     * Обновляет данные блога
     * @param int $id
     * @param array $item
     * @param bool $update_seo_link
     * @return str $seolink
     */
    public function updateBlog($id, $item, $update_seo_link = false) {
        $item['id'] = $id;

        if ($update_seo_link) {
            $item['seolink'] = $this->generateBlogSeoLink($item);
        }

        $item = \cms\events::call('blog.update', $item);

        unset($item['id']);

        $this->inDB->update('blogs', (int) $id, $item);

        return @$item['seolink'] ? $item['seolink'] : false;
    }

    /**
     * Удаляет блог
     * @param int $blog_id
     * @return bool
     */
    public function deleteBlog($blog_id) {
        \cms\events::call('blog.delete', $blog_id);

        $db = $this->inDB;

        // удаляем сам блог
        $db->delete('blogs', (int) $blog_id);
        cmsActions::removeObjectLog($this->getTarget('actions_blog'), $blog_id);

        // удаляем посты в нем
        $db->resetFilters();

        $posts = $db->selectOnly('i.id')
                ->filterEqual('i.blog_id', (int) $blog_id)
                ->get('blog_posts', false, '');

        if (!$posts) {
            return true;
        }

        foreach ($posts as $post) {
            $this->deletePost($post['id']);
        }

        return true;
    }

    public function deleteBlogs($id_list) {
        if (!$id_list || !is_array($id_list)) {
            return false;
        }

        foreach ($id_list as $key => $id) {
            $this->deleteBlog($id);
        }

        return true;
    }

    /**
     * Обновляет авторов блога
     * @param int $id
     * @param array $authors
     * @return bool
     */
    public function updateBlogAuthors($id, $authors) {
        $db = $this->inDB;

        //Удаляем прежний набор авторов
        $db->filterEqual('blog_id', (int) $id)->deleteFiltered('blog_authors');

        $authors = \cms\events::call('blog.update_authors', $authors);

        if ($authors) {
            foreach ($authors as $key => $author_id) {
                $db->insert('blog_authors', array(
                    'user_id'     => (int) $author_id,
                    'blog_id'     => (int) $id,
                    'description' => '',
                    'startdate'   => date('Y-m-d H:i:s')
                ));
            }
        }

        return true;
    }

    /**
     * Условия выборки
     */
    public function whereOwnerIs($owner) {
        $this->inDB->filterEqual('b.owner', $owner);
        return;
    }

    public function whereNotPublished() {
        $this->inDB->filterEqual('i.published', 0);
        return;
    }

    public function whereOnlyPublic() {
        $this->inDB->filterEqual('b.allow_who', 'all');
        $this->inDB->filterEqual('i.allow_who', 'all');
        return;
    }

    public function whereBlogIs($blog_id) {
        $this->inDB->filterEqual('i.blog_id', (int) $blog_id);
        return;
    }

    public function whereCatIs($cat_id) {
        $this->inDB->filterEqual('i.cat_id', (int) $cat_id);
        return;
    }

    public function whereBlogUserIs($user_id) {
        $this->inDB->filterEqual('b.user_id', (int) $user_id);
        return;
    }

    public function whereOwnerTypeIs($ownertype) {
        $this->inDB->filterEqual('i.ownertype', $ownertype);
        return;
    }

    public function ratingGreaterThan($rating) {
        $this->inDB->filterGt('i.rating', $rating);
        return;
    }

    /**
     * Получает посты по условиям
     * @param bool $show_all
     * @param obj $ownerModel
     * @return array $posts
     */
    public function getPosts($show_all = false, $ownerModel = null, $is_short = false) {
        if ($ownerModel === null) {
            return false;
        }

        $db = $this->inDB;

        if (!$show_all) {
            $db->filterEqual('i.published', 1);
        }

        if (isset($this->owner)) {
            $db->filterEqual('b.owner', $this->owner);
        }

        $db->select('i.*')
                ->select('u.nickname', 'author')
                ->select('u.login', 'login')
                ->select('u.is_deleted', 'is_deleted')
                ->select('b.allow_who', 'blog_allow_who')
                ->select('b.seolink', 'bloglink')
                ->select('b.title', 'blog_title')
                ->select('b.owner', 'owner')
                ->joinInner('blogs', 'b', 'b.id = i.blog_id')
                ->joinLeft('users', 'u', 'u.id = i.user_id');

        $posts = $db->get('blog_posts', false, '');

        if (!$posts) {
            return false;
        }

        foreach ($posts as $key => $post) {
            $posts[$key]['fpubdate'] = cmsCore::dateFormat($post['pubdate']);
            $posts[$key]['url'] = $ownerModel->getPostURL($post['bloglink'], $post['seolink']);
            $posts[$key]['blog_url'] = $ownerModel->getBlogURL($post['bloglink']);

            //Разбиваем текст поста на 2 части по тегу [cut=...] и оставляем только первую из них
            if (mb_strstr($post['content_html'], '[cut')) {
                $posts[$key]['content_html'] = $this->getPostShort($post['content_html'], $posts[$key]['url']);
            }

            if (!$is_short) {
                $posts[$key]['tagline'] = cmsTagLine($this->getTarget('tags'), $post['id']);
            }

            // если захотим получать аватар владельца
            if (isset($post['imageurl'])) {
                $posts[$key]['author_avatar'] = cmsUser::getUserAvatarUrl($post['user_id'], 'small', $post['imageurl'], $post['is_deleted']);
            }
        }

        return \cms\events::call('blog.get_posts', $posts);
    }

    /**
     * Получает количество постов по условиям
     * @param bool $show_all
     * @return int
     */
    public function getPostsCount($show_all = false) {
        $db = $this->inDB;

        $join = $db->join;

        if (!$show_all) {
            $db->filterEqual('i.published', 1);
        }

        if (isset($this->owner)) {
            $db->filterEqual('b.owner', $this->owner);
        }

        $db->joinInner('blogs', 'b', 'b.id = i.blog_id');

        $count = $db->getCount('blog_posts');

        // внутренний join был нужен только для подсчета
        $db->join = $join;

        return $count;
    }

    public function getUserBlogId($user_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $db->filterEqual('user_id', $user_id);

        if (isset($this->owner)) {
            $db->filterEqual('owner', $this->owner);
        }

        $blog_id = $db->getFieldFiltered('blogs', 'id');

        $db->resetFilters();

        return $blog_id;
    }

    /**
     * проверяет, может ли пользователь писать в блог
     * @param array $blog
     * @param int $user_id
     * @return int
     */
    public function isUserBlogWriter($blog, $user_id) {
        \cms\events::call('blog.is_writer', $blog);

        // в персональные блоги может писать только автор
        if ($blog['ownertype'] == 'single') {
            return false;
        }

        if ($blog['ownertype'] == 'multi' && $blog['forall']) {
            return true;
        }

        $db = $this->inDB;

        $db->resetFilters();

        $author_id = $db->filterEqual('blog_id', $blog['id'])
                ->filterEqual('user_id', $user_id)
                ->getFieldFiltered('blog_authors', 'id');

        $db->resetFilters();

        return $author_id;
    }

    /**
     * Возвращает блог по ссылке или по id
     * @param int or string $id_or_link
     * @return array $blog
     */
    public function getPost($id_or_link = 0) {
        $db = $this->inDB;

        $db->resetFilters();

        if (is_numeric($id_or_link)) {
            $db->filterEqual('i.id', (int) $id_or_link);
        } else {
            $db->filterEqual('i.seolink', $id_or_link);
        }

        $post = $db->select('i.*')
                ->select('u.nickname', 'author_nickname')
                ->select('u.login', 'author_login')
                ->select('up.imageurl', 'author_image')
                ->select('u.is_deleted', 'author_deleted')
                ->joinLeft('users', 'u', 'u.id = i.user_id')
                ->joinLeft('user_profiles', 'up', 'up.user_id = u.id')
                ->getItem('blog_posts');

        $db->resetFilters();

        if (!$post) {
            return false;
        }

        global $_LANG;

        $post['feditdate'] = cmsCore::dateFormat($post['edit_date']);
        $post['fpubdate'] = cmsCore::dateDiffNow($post['pubdate']) . ' ' . $_LANG['BACK'] . ' (' . cmsCore::dateFormat($post['pubdate']) . ')';
        //Убираем тег [cut]
        $post['content_html'] = preg_replace('/\[(cut=)\s*(.*?)\]/ui', '', $post['content_html']);
        $post['author_avatar'] = cmsUser::getUserAvatarUrl($post['user_id'], 'small', $post['author_image'], $post['author_deleted']);

        return \cms\events::call('blog.get_post', $post);
    }

    /**
     * Добавляет пост в блоге
     * @param array $item
     * @return array $post_id, $seolink
     */
    public function addPost($item) {
        $db = $this->inDB;

        $item = \cms\events::call('blog.add_post', $item);

        //парсим bb-код перед записью в базу
        // Парсим по отдельности части текста, если есть тег [cut
        if (mb_strstr($item['content'], '[cut')) {
            $msg_to = $this->getPostShort($item['content']);
            $msg_to = cmsCore::parseSmiles($msg_to, true);
            $msg_after = $this->getPostShort($item['content'], false, true);
            $msg_after = cmsCore::parseSmiles($msg_after, true);
            $cut = $this->getPostCut($item['content']);
            $item['content_html'] = $msg_to . $cut . $msg_after;
        } else {
            $item['content_html'] = cmsCore::parseSmiles($item['content'], true);
        }

        // seolink обязателен (NOT NULL без DEFAULT), поэтому генерируем его
        // до INSERT, а не отдельным UPDATE после вставки.
        $item['seolink'] = $this->generatePostSeoLink($item);

        $post_id = $db->insert('blog_posts', $item);

        if (!$post_id) {
            return false;
        }

        if ( isset($item['tags']) ) {
            cmsInsertTags($item['tags'], $this->getTarget('tags'), $post_id);
        }

        $item['id'] = $post_id;

        if ($item['published']) {
            cmsUser::checkAwards($item['user_id']);
        }

        cmsCore::setIdUploadImage('blog_post', $post_id);

        return array('id' => $post_id, 'seolink' => $item['seolink']);
    }

    /**
     * Обновляет пост в блоге
     * @param int $post_id
     * @param array $item
     * @param bool $update_seo_link
     * @return bool
     */
    public function updatePost($post_id, $item, $update_seo_link = false) {
        $item['id'] = $post_id;

        $item = \cms\events::call('blog.update_post', $item);

        if ($update_seo_link) {
            $item['seolink'] = $this->generatePostSeoLink($item);
        }

        if (mb_strstr($item['content'], '[cut')) {
            $msg_to = $this->getPostShort($item['content']);
            $msg_to = cmsCore::parseSmiles($msg_to, true);
            $msg_after = $this->getPostShort($item['content'], false, true);
            $msg_after = cmsCore::parseSmiles($msg_after, true);
            $cut = $this->getPostCut($item['content']);
            $item['content_html'] = $msg_to . $cut . $msg_after;
        } else {
            $item['content_html'] = cmsCore::parseSmiles($item['content'], true);
        }

        unset($item['id']);

        $this->inDB->update('blog_posts', (int) $post_id, $item);

        if ( isset($item['tags']) ) {
            cmsInsertTags($item['tags'], $this->getTarget('tags'), $post_id);
        }

        return isset($item['seolink']) ? $item['seolink'] : true;
    }

    /**
     * Удаляет пост в блоге
     * @param int $post_id
     * @return bool
     */
    public function deletePost($post_id) {
        \cms\events::call('blog.delete_post', $post_id);

        $post = $this->getPost($post_id);

        if (!$post) {
            return false;
        }

        $db = $this->inDB;

        // пересчитываем рейтинг блога
        $db->filterEqual('id', (int) $post['blog_id'])
                ->increment('blogs', 'rating', -1 * (int) $post['rating']);

        $db->delete('blog_posts', (int) $post_id);

        cmsCore::deleteRatings($this->getTarget('rating'), $post_id);
        cmsCore::deleteComments($this->getTarget('comments'), $post_id);

        cmsClearTags($this->getTarget('tags'), $post_id);

        cmsCore::deleteUploadImages($post_id, 'blog_post');
        cmsActions::removeObjectLog($this->getTarget('actions_post'), $post_id);

        return true;
    }

    /**
     * Публикует пост в блоге
     * @param int $post_id
     * @param int $flag
     * @return bool
     */
    public function publishPost($post_id, $flag = 1) {
        return $this->inDB->update('blog_posts', (int) $post_id, array(
            'published' => $flag,
            'pubdate'   => date('Y-m-d H:i:s')
        ));
    }

    public static function updateCommentsCount($target, $target_id) {
        $db = \cms\db::getInstance();

        // получаем данные о записи
        $db->resetFilters();

        $post = $db->getItemById('blog_posts', (int) $target_id);

        $db->resetFilters();

        if (!$post) {
            return false;
        }

        // обновляем количество комментов для поста
        $pc_count = cmsCore::getCommentsCount($target, $post['id']);
        $db->update('blog_posts', (int) $post['id'], array(
            'comments_count' => $pc_count
        ));

        // обновляем общее количество комментов к постам в блоге
        $db->resetFilters();

        $com = $db->select('IFNULL(SUM(i.comments_count), 0)', 'blog_comments_count')
                ->filterEqual('i.blog_id', (int) $post['blog_id'])
                ->groupBy('i.blog_id')
                ->getItem('blog_posts');

        $db->resetFilters();

        if ($com) {
            $db->update('blogs', (int) $post['blog_id'], array(
                'comments_count' => $com['blog_comments_count']
            ));
        }

        return true;
    }

    public function getPostShort($post_content, $post_url = false, $is_after = false) {
        $regex = '/\[(cut=)\s*(.*?)\]/ui';
        $matches = array();

        preg_match_all($regex, $post_content, $matches, PREG_SET_ORDER);

        if (is_array($matches)) {
            $elm = $matches[0];
            $elm[0] = str_replace('[', '', $elm[0]);
            $elm[0] = str_replace(']', '', $elm[0]);

            mb_parse_str($elm[0], $args);

            $cut_title = $args['cut'];

            $pages = preg_split($regex, $post_content);

            if ($pages) {
                $post_content = $is_after ? $pages[1] : $pages[0];
            }

            if ($post_url && !$is_after) {
                $post_content .= '<div class="blog_cut_link">
                                    <a href="' . $post_url . '">' . $cut_title . '</a>
                              </div>';
            }
        }

        return $post_content;
    }

    public function getPostCut($post_content) {
        $regex = '/\[(cut=)\s*(.*?)\]/ui';
        $matches = array();

        preg_match_all($regex, $post_content, $matches, PREG_SET_ORDER);

        if (is_array($matches)) {
            $elm = $matches[0];
            $elm[0] = str_replace('[', '', $elm[0]);
            $elm[0] = str_replace(']', '', $elm[0]);

            mb_parse_str($elm[0], $args);

            $cut .= '[cut=' . $args['cut'] . '...]';
        }

        return $cut;
    }
}
