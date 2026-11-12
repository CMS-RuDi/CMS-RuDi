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

class cms_model_clubs {
    private $club_members_ids;
    public $club_total_members = 1;
    public $last_message = '';
    private $clubs = array();

    public function __construct() {
        $this->db = \cms\db::getInstance();
        $this->config = cmsCore::getInstance()->loadComponentConfig('clubs');
        cmsCore::loadLanguage('components/clubs');
        cmsCore::loadLib('karma');
    }

    /**
     * Настройки по умолчанию для компонента
     * @return array
     */
    public static function getDefaultConfig() {
        return array(
            'enabled_blogs' => 1,
            'enabled_photos' => 1,
            'thumb1' => 48,
            'thumb2' => 200,
            'thumbsqr' => 1,
            'cancreate' => 1,
            'perpage' => 10,
            'club_perpage' => 4,
            'member_perpage' => 10,
            'club_album_perpage' => 3,
            'club_posts_perpage' => 5,
            'posts_perpage' => 10,
            'photo_perpage' => 18,
            'wall_perpage' => 10,
            'photo_watermark' => 1,
            'photo_thumb_small' => 96,
            'photo_thumbsqr' => 1,
            'photo_thumb_medium' => 450,
            'photo_maxcols' => 6,
            'create_min_karma' => 0,
            'create_min_rating' => 0,
            'is_saveorig' => 0,
            'notify_in' => 1,
            'notify_out' => 1,
            'every_karma' => 100,
            'meta_keys' => '',
            'meta_desc' => '',
            'seo_user_access' => 0
        );
    }

    /**
     * этот метод вызывается компонентом comments при создании нового комментария
     * метод обновляет количество комментариев для поста и для блога в целом
     */
    public function updateCommentsCount($target, $target_id) {
        if ($target != 'club_post') {
            return false;
        }

        return cmsBlogs::updateCommentsCount($target, $target_id);
    }

    public function getCommentTarget($target, $target_id) {
        $result = array();

        switch ($target) {
            case 'club_photo': $photo = $this->db->getRow('photo_files', 'id = ' . (int) $target_id, 'id, title');
                if (!$photo) {
                    return false;
                }
                $result['link'] = '/clubs/photo' . $photo['id'] . '.html';
                $result['title'] = $photo['title'];
                break;
            case 'club_post': $post = \cms\db::cloneOf($this->db)->select('i.title')
                        ->select('i.seolink')
                        ->select('b.user_id', 'bloglink')
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

    public function getVisibility($target, $target_id) {
        $is_hidden = 0;

        switch ($target) {
            case 'club_photo':
                $album_id = $this->db->getField('photo_files', $target_id, 'album_id');
                $club_id = $this->db->getField('photo_albums', $album_id, 'user_id');
                $clubtype = $this->db->getField('clubs', $club_id, 'clubtype');
                if ($clubtype == 'private') {
                    $is_hidden = 1;
                }
                break;
            case 'club_post':
                $post = $this->db->getRow('blog_posts', 'id = ' . (int) $target_id, 'blog_id, allow_who, published');
                if ($post['allow_who'] != 'all' || !$post['published']) {
                    $is_hidden = 1;
                }
                $club_id = $this->db->getField('blogs', $post['blog_id'], 'user_id');
                $clubtype = $this->db->getField('clubs', $club_id, 'clubtype');
                if ($clubtype == 'private') {
                    $is_hidden = 1;
                }
                break;
        }

        return $is_hidden;
    }

    public function updateRatingHook($target, $item_id, $points) {
        if (!$item_id || abs($points) != 1) {
            return false;
        }

        switch ($target) {
            case 'club_photo':
                $this->db->filterEqual('id', (int) $item_id)->increment('photo_files', 'rating', $points);
                break;
            case 'club_post':
                $blog_id = $this->db->getField('blog_posts', $item_id, 'blog_id');

                $this->db->filterEqual('id', (int) $item_id)->increment('blog_posts', 'rating', $points);

                if ($blog_id) {
                    $this->db->filterEqual('id', (int) $blog_id)->increment('blogs', 'rating', $points);
                }
                break;
        }

        return true;
    }

    /**
     * Методы стены
     */
    public function forWallIsMyProfile($club_id) {
        $club = $this->getClub($club_id);

        if (!$club) {
            return false;
        }

        return $club['admin_id'] == cmsUser::getInstance()->id;
    }

    public function forWallIsAdmin($club_id) {
        $club = $this->getClub($club_id);

        if (!$club) {
            return false;
        }

        $inUser = cmsUser::getInstance();

        $this->initClubMembers($club['id']);

        $is_admin = $inUser->is_admin || ($inUser->id == $club['admin_id']);
        $is_moder = $this->checkUserRightsInClub('moderator');

        return ($is_admin || $is_moder);
    }

    public function addWall($item) {
        $club = $this->getClub($item['user_id']);

        if (!$club) {
            return false;
        }

        // добавляем запись
        $wall_id = $this->db->insert('user_wall', \cms\events::call('clubs.add_wall_item', $item));

        if ($club['clubtype'] == 'private') {
            return $wall_id;
        }

        $message = strip_tags($item['content']);
        $message = mb_strlen($message) > 100 ? mb_substr($message, 0, 100) : $message;

        //регистрируем событие
        cmsActions::log('add_wall_club', array(
            'object' => $club['title'],
            'object_url' => '/clubs/' . $club['id'],
            'object_id' => $wall_id,
            'target' => '',
            'target_url' => '',
            'target_id' => 0,
            'description' => $message
        ));

        return $wall_id;
    }

    public function deleteWallRecord($record_id) {
        $this->db->delete('user_wall', (int) $record_id);
        cmsCore::deleteUploadImages($record_id, 'wall');

        cmsActions::removeObjectLog('add_wall_club', $record_id);

        return true;
    }

    /**
     * Возвращает изображение клуба
     * @return str
     */
    public static function getClubImage($imageurl = '') {
        return $imageurl ?
                (file_exists(PATH . '/images/clubs/small/' . $imageurl) ? $imageurl : 'nopic.jpg') :
                'nopic.jpg';
    }

    /**
     * Условия выборки
     */
    public function whereAdminIs($user_id) {
        $this->db->filterEqual('admin_id', (int) $user_id);
        return;
    }

    /**
     * Список клубов по критериям
     * @return array
     */
    public function getClubs($only_published = true) {
        if ($only_published) {
            $this->db->filterEqual('published', 1);
        }

        // сохраняем поля клуба, даже если к выборке добавлены свои поля
        $this->db->select('i.*');

        $clubs = $this->db->get('clubs', function ($club) {
            $club['imageurl'] = self::getClubImage($club['imageurl']);
            $club['fpubdate'] = cmsCore::dateFormat($club['pubdate'], true, true);

            return $club;
        });

        return \cms\events::call('clubs.get_items', $clubs);
    }

    /**
     * Количество клубов по критериям
     * @return int
     */
    public function getClubsCount($only_published = true) {
        $db = clone $this->db;

        if ($only_published) {
            $db->filterEqual('published', 1);
        }

        return $db->getCount('clubs');
    }

    /**
     * Возвращает клуб и его администратора
     * @return array
     */
    public function getClub($club_id) {
        if (isset($this->clubs[$club_id])) {
            return $this->clubs[$club_id];
        }

        $club = \cms\db::cloneOf($this->db)->select('i.*')
                ->select('i.enabled_blogs', 'orig_enabled_blogs')
                ->select('i.enabled_photos', 'orig_enabled_photos')
                ->select('u.nickname', 'nickname')
                ->select('u.login', 'login')
                ->select('u.status')
                ->select('u.logdate')
                ->select('p.karma')
                ->select('p.gender', 'gender')
                ->select('p.imageurl', 'admin_avatar')
                ->select('u.is_deleted')
                ->joinLeft('users', 'u', 'u.id = i.admin_id')
                ->joinLeft('user_profiles', 'p', 'p.user_id = u.id')
                ->filterEqual('i.id', (int) $club_id)
                ->getItem('clubs');

        if (!$club) {
            return false;
        }
        $club['f_imageurl'] = self::getClubImage($club['imageurl']);
        $club['fpubdate'] = cmsCore::dateFormat($club['pubdate'], true, true);
        $club['flogdate'] = cmsUser::getOnlineStatus($club['admin_id'], $club['logdate']);
        $club['admin_avatar'] = cmsUser::getUserAvatarUrl($club['admin_id'], 'small', $club['admin_avatar'], $club['is_deleted']);
        $club['enabled_blogs'] = ($club['enabled_blogs'] == -1) ? $this->config['enabled_blogs'] : $club['enabled_blogs'];
        $club['enabled_photos'] = ($club['enabled_photos'] == -1) ? $this->config['enabled_photos'] : $club['enabled_photos'];

        return $this->clubs[$club_id] = \cms\events::call('clubs.get_club', $club);
    }

    /**
     * Получает массив членов клуба
     * @return bool
     */
    public function getClubMembers($club_id, $role = '') {
        $db = clone $this->db;
        $db->limit = $this->db->limit ? $this->db->limit : 1000;

        $db->select('i.user_id');
        $db->select('i.role');
        $db->select('u.nickname');
        $db->select('u.login');
        $db->select('u.status');
        $db->select('u.logdate');
        $db->select('p.karma');
        $db->select('p.gender');
        $db->select('p.imageurl', 'admin_avatar');
        $db->select('u.is_deleted');
        $db->joinLeft('users', 'u', 'u.id = i.user_id');
        $db->joinLeft('user_profiles', 'p', 'p.user_id = u.id');
        $db->filterEqual('i.club_id', $club_id);

        if ($role) {
            $db->filterEqual('i.role', $role);
        }

        $db->orderBy('i.role DESC, u.logdate DESC');

        $club_members = $db->get('user_clubs', function ($u) {
            $u['admin_avatar'] = cmsUser::getUserAvatarUrl($u['user_id'], 'small', $u['admin_avatar'], $u['is_deleted']);
            $u['is_online'] = cmsUser::isOnline($u['user_id']);
            $u['logdate'] = cmsCore::dateFormat($u['logdate'], true, true);

            return $u;
        });

        $this->db->resetConditions();

        return $club_members;
    }

    /**
     * Получает id членов клуба и запоминает их по ролям и общее количество участников
     * @return bool
     */
    public function initClubMembers($club_id) {
        // Если уже получали, возвращаемся
        if (isset($this->club_members_ids)) {
            return true;
        }

        $club_members_ids = array();
        $club_members_ids['all'] = array();

        $rows = $this->db->getRows('user_clubs', 'club_id = ' . (int) $club_id, 'user_id, role');

        if ($rows) {
            foreach ($rows as $u) {
                $club_members_ids[$u['role']][] = $u['user_id'];
                $club_members_ids['all'][] = $u['user_id'];
            }
        }

        // запоминаем массив id участников
        $this->club_members_ids = $club_members_ids;
        // запоминаем общее количество участников
        $this->club_total_members = isset($club_members_ids['all']) ? count($club_members_ids['all']) : 0;
        // запоминаем общее количество модераторов
        $this->club_total_moderators = isset($club_members_ids['moderator']) ? count($club_members_ids['moderator']) : 0;

        return true;
    }

    /**
     * Возвращает массив id участников клуба
     * @return array
     */
    public function getClubMembersIds($role = '') {
        if (!$role) {
            return $this->club_members_ids['all'];
        }

        if (!isset($this->club_members_ids[$role])) {
            return false;
        }

        return $this->club_members_ids[$role];
    }

    /**
     * Проверяет права пользователя в группе
     * @return bool
     */
    public function checkUserRightsInClub($role = '', $user_id = 0) {
        if (!$user_id) {
            $user_id = cmsUser::getInstance()->id;
        }

        if (!$user_id) {
            return false;
        }

        if (!$role) {
            return in_array($user_id, $this->club_members_ids['all']);
        }

        if (!isset($this->club_members_ids[$role])) {
            return false;
        }

        return in_array($user_id, $this->club_members_ids[$role]);
    }

    /**
     * Устанавливает рейтинг клубу как сумму рейтингов его участников * 5
     * @return bool
     */
    public function setClubRating($club_id) {
        $members_rating = \cms\db::cloneOf($this->db)->select('SUM(u.rating)', 'rating')
                ->joinLeft('users', 'u', 'u.id = i.user_id')
                ->filterEqual('i.club_id', (int) $club_id)
                ->getItem('user_clubs');

        $rating = !empty($members_rating['rating']) ? $members_rating['rating'] * 5 : 0;

        return $this->db->update('clubs', (int) $club_id, array('rating' => $rating));
    }

    /**
     * Кеширует количество участников клуба
     * @return bool
     */
    public function setClubMembersCount($club_id) {
        $members_count = \cms\db::cloneOf($this->db)->filterEqual('club_id', (int) $club_id)
                        ->getCount('user_clubs') + 1; // учитываем администратора

        return $this->db->update('clubs', (int) $club_id, array('members_count' => $members_count));
    }

    /**
     * Создает клуб
     * @return int
     */
    public function addClub($item) {
        global $_LANG;

        $item = \cms\events::call('clubs.create_club', $item);

        $club_id = $this->db->insert('clubs', $item);
        if (!$club_id) {
            return false;
        }

        // Создаем блог клуба
        $inBlog = cmsBlogs::getInstance();

        $inBlog->addBlog(\cms\events::call('clubs.add_blog', ['user_id' => $club_id,
                    'title' => $_LANG['CLUB_BLOG'] . ' - ' . $item['title'],
                    'allow_who' => $item['clubtype'] == 'private' ? 'friends' : 'all',
                    'ownertype' => 'multi',
                    'owner' => 'club']));

        // Создаем корневой фотоальбом
        $this->db->addRootNsCategory('photo_albums', 'club' . $club_id, array('user_id' => $club_id,
            'title' => $_LANG['CLUB_PHOTOALBUMS'] . ' - ' . $item['title'],
            'orderform' => 0));

        // Устанавливаем рейтинг
        $this->setClubRating($club_id);

        return $club_id;
    }

    /**
     * Возвращает id блога клуба
     * @return int
     */
    public function getClubBlogId($club_id) {
        return $this->db->
                        filterEqual('owner', 'club')->
                        filterEqual('user_id', $club_id)->
                        getFieldFiltered('blogs', 'id');
    }

    /**
     * Добавляет участника в клуб
     * @return bool
     */
    public function addUserToClub($club_id, $user_id, $role = 'member') {
        return $this->db->insert('user_clubs', array(
                    'user_id' => (int) $user_id,
                    'club_id' => (int) $club_id,
                    'role' => $role
                        ), false, 'json', true);
    }

    /**
     * Удаляет участника из клуба
     * @return bool
     */
    public function removeUserFromClub($club_id, $user_id) {
        return $this->db->filterEqual('user_id', (int) $user_id)
                        ->filterEqual('club_id', (int) $club_id)
                        ->deleteFiltered('user_clubs');
    }

    /**
     * Сохраняет участников клуба
     * @return bool
     */
    public function clubSaveUsers($club_id, $list) {
        if (!is_array($list)) {
            return false;
        }

        $inUser = cmsUser::getInstance();
        global $_LANG;

        // Все участники
        $members = $this->getClubMembersIds();

        // Название клуба
        $club_title = $this->db->getField('clubs', $club_id, 'title');

        // Добавляем новых
        foreach ($list as $user_id) {
            if (in_array($user_id, $members)) {
                continue;
            }

            $this->addUserToClub($club_id, $user_id);

            //Уведомляем
            if ($this->config['notify_in'] && ($user_id != $inUser->id)) {
                cmsUser::sendMessage(USER_UPDATER, $user_id, sprintf($_LANG['USER_ADD_YOU'], '<a href="' . cmsUser::getProfileURL($inUser->login) . '">' . $inUser->nickname . '</a>', '<a href="' . HOST . '/clubs/' . $club_id . '">' . $club_title . '</a>'));
            }
        }

        // удаляем пользователя
        foreach ($members as $member_id) {
            if (in_array($member_id, $list)) {
                continue;
            }

            $this->removeUserFromClub($club_id, $member_id);
            // Уведомляем пользователя
            if ($this->config['notify_out'] && ($member_id != $inUser->id)) {
                cmsUser::sendMessage(USER_UPDATER, $member_id, sprintf($_LANG['USER_DELETE_YOU'], '<a href="' . cmsUser::getProfileURL($inUser->login) . '">' . $inUser->nickname . '</a>', '<a href="' . HOST . '/clubs/' . $club_id . '">' . $club_title . '</a>'));
            }
        }
    }

    /**
     * Устанавливает роли участников
     * @return bool
     */
    public function clubSetRole($club_id, $list, $role = '') {
        if (!is_array($list)) {
            return false;
        }

        // Сбрасываем сначала всем роли
        $this->db->filterEqual('club_id', (int) $club_id)->updateFiltered('user_clubs', array('role' => 'member'));

        if (!$list) {
            return true;
        }

        $this->db->filterEqual('club_id', (int) $club_id)
                ->filterIn('user_id', $list)
                ->updateFiltered('user_clubs', array('role' => $role));

        return true;
    }

    /**
     * Загружает изображение клуба
     * @return str
     */
    public function uploadClubImage($old_file = '') {
        $inUploadPhoto = cmsUploadPhoto::getInstance();
        // Выставляем конфигурационные параметры
        $inUploadPhoto->upload_dir = PATH . '/images/';
        $inUploadPhoto->dir_medium = 'clubs/';
        $inUploadPhoto->dir_small = 'clubs/small/';
        $inUploadPhoto->small_size_w = $this->config['thumb1'];
        $inUploadPhoto->medium_size_w = $this->config['thumb2'];
        $inUploadPhoto->thumbsqr = $this->config['thumbsqr'];
        $inUploadPhoto->is_watermark = false;
        $inUploadPhoto->input_name = 'picture';

        $file = $inUploadPhoto->uploadPhoto($old_file);

        return $file;
    }

    /**
     * Возвращает объект класса для загрузки изображений
     * @return obj
     */
    public function initUploadClass() {
        $inUploadPhoto = cmsUploadPhoto::getInstance();
        // Выставляем конфигурационные параметры
        $inUploadPhoto->upload_dir = PATH . '/images/photos/';
        $inUploadPhoto->small_size_w = $this->config['photo_thumb_small'];
        $inUploadPhoto->medium_size_w = $this->config['photo_thumb_medium'];
        $inUploadPhoto->thumbsqr = $this->config['photo_thumbsqr'];
        $inUploadPhoto->is_watermark = $this->config['photo_watermark'];
        $inUploadPhoto->is_saveorig = $this->config['is_saveorig'];

        return $inUploadPhoto;
    }

    /**
     * Сохраняет настройки клуба
     * @return bool
     */
    public function updateClub($club_id, $item) {
        global $_LANG;

        $blog_allow_who = $item['clubtype'] == 'private' ? 'friends' : 'all';
        $blog_title = $_LANG['CLUB_BLOG'] . ' - ' . $item['title'];

        $this->db->filterEqual('user_id', (int) $club_id)
                ->filterEqual('owner', 'club')
                ->updateFiltered('blogs', array(
                    'allow_who' => $blog_allow_who,
                    'title' => $blog_title
        ));

        $item = \cms\events::call('clubs.update_club', $item);

        return $this->db->update('clubs', (int) $club_id, $item);
    }

    /**
     * Управляет меткой vip клуба
     * @return bool
     */
    public function setVip($club_id, $is_vip, $join_cost) {
        if (!$is_vip) {
            $is_vip = 0;
        }

        if (!$join_cost) {
            $join_cost = 0;
        }

        return $this->db->update('clubs', (int) $club_id, array(
                    'is_vip' => $is_vip,
                    'join_cost' => $join_cost
        ));
    }

    /**
     * Удаляет клуб
     * @return bool
     */
    public function deleteClub($club_id) {
        \cms\events::call('clubs.delete_club', $club_id);

        $club = $this->getClub($club_id);

        if (!$club) {
            return false;
        }

        $inBlog = $this->initBlog();

        $inPhoto = $this->initPhoto();

        //Удаляем логотип клуба
        if ($club['imageurl'] != 'nopic.jpg') {
            @unlink(PATH . '/images/clubs/' . $club['imageurl']);
            @unlink(PATH . '/images/clubs/small/' . $club['imageurl']);
        }

        //Удаляем клуб и привязки пользователей
        $this->db->delete('clubs', (int) $club_id);
        $this->db->filterEqual('club_id', (int) $club_id)->deleteFiltered('user_clubs');

        //Удаляем блог клуба
        \cms\events::call('clubs.delete_blog', $this->getClubBlogId($club_id));
        $inBlog->deleteBlog($this->getClubBlogId($club_id));

        //Удаляем фотоальбомы клуба
        $inPhoto->deleteAlbum($this->db->getNsRootCatId('photo_albums', 'club' . $club_id), 'club' . $club_id, $this->initUploadClass());
        $this->db->filterEqual('NSDiffer', 'club' . $club_id)->deleteFiltered('photo_albums');

        cmsActions::removeObjectLog('add_club', $club_id);

        return true;
    }

    public function hasUserClub() {
        $inUser = cmsUser::getInstance();

        $is_has_club = $inUser->id ? $this->db->getRow('clubs', 'admin_id = ' . (int) $inUser->id, 'id, create_karma', 'id DESC') : true;

        return $is_has_club;
    }

    public function canCreate() {
        global $_LANG;

        $inUser = cmsUser::getInstance();

        // неавторизованные не могут создавать клубы
        if (!$inUser->id) {
            return false;
        }

        // администраторы могут создавать клубы
        if ($inUser->is_admin) {
            return true;
        }

        $is_has_club = $this->hasUserClub();

        // клуб можно создавать каждые $cfg['every_karma']
        if ($is_has_club) {
            if (!$this->config['every_karma']) {

                $this->last_message = $_LANG['USER_HAS_ONE_CLUB'];
                return false;
            }

            if (($is_has_club['create_karma'] + $this->config['every_karma']) > $inUser->karma) {

                $message = $_LANG['USER_HAS_CLUB'];
                $message = str_replace('%create_karma%', $is_has_club['create_karma'], $message);
                $message = str_replace('%every_karma%', $this->config['every_karma'], $message);
                $message = str_replace('%karma%', $inUser->karma, $message);
                $message = str_replace('%new_karma%', ($is_has_club['create_karma'] + $this->config['every_karma']), $message);

                $this->last_message = $message;
                return false;
            }
        }

        if ($this->config['cancreate'] && $inUser->karma >= $this->config['create_min_karma'] && $inUser->rating >= $this->config['create_min_rating']) {
            return true;
        } else {
            if ($inUser->karma < $this->config['create_min_karma']) {
                $message = $_LANG['NEED_KARMA_TEXT_ACCESS'];
                $message = str_replace('%karma%', $inUser->karma, $message);
                $message = str_replace('%min_karma%', $this->config['create_min_karma'], $message);
                $this->last_message = $message;
            }
            if ($inUser->rating < $this->config['create_min_rating']) {
                $message = $_LANG['NEED_RATING_TEXT_ACCESS'];
                $message = str_replace('%rating%', $inUser->rating, $message);
                $message = str_replace('%min_rating%', $this->config['create_min_rating'], $message);
                $this->last_message = $message;
            }
        }

        return false;
    }

    /**
     * Отрабатывает плагины на событие $plugin_title
     * @return array $plugins_list
     */
    public function getPluginsOutput($item, $plugin_title = 'GET_SINGLE_CLUB') {
        $plugins = cmsCore::callAllEvent($plugin_title, $item);
        if (empty($plugins)) {
            return array();
        }

        foreach ($plugins as $plugin_data) {
            $plugins_list[] = array(
                'name' => $plugin_data['info']['plugin'],
                'html' => $plugin_data['result']
            );
        }

        return $plugins_list;
    }

    /**
     * Производит инициализацию класса блогов
     * @return obj
     */
    public function initBlog() {
        $inBlog = cmsBlogs::getInstance();
        $inBlog->owner = 'club';
        $inBlog->setTargets(array('tags' => 'blogpost',
            'rating' => 'club_post',
            'comments' => 'club_post',
            'actions_post' => 'add_post_club',
            'actions_blog' => 'add_blog'));

        return $inBlog;
    }

    /**
     * Производит инициализацию класса фото
     * @return obj
     */
    public function initPhoto() {
        $inPhoto = cmsPhoto::getInstance();

        $inPhoto->setTargets(array('tags' => 'photo',
            'rating' => 'club_photo',
            'comments_photo' => 'club_photo',
            'comments_album' => 'club_album',
            'actions_photo' => 'add_photo_club'));

        return $inPhoto;
    }

    /**
     * Возвращает ссылку на пост блога клуба /clubs/ID/blog/seolink.html
     * @return str
     */
    public static function getPostURL($club_id, $seolink) {
        return '/clubs/' . $club_id . '_' . $seolink . '.html';
    }

    /**
     * Возвращает ссылку на блога клуба /clubs/ID/blog
     * @return str
     */
    public static function getBlogURL($club_id, $page = 1, $cat_id = 0) {
        $cat_section = ($cat_id > 0 ? '/cat-' . $cat_id : '');
        $page_section = ($page > 1 ? '/page-' . $page : '');

        return '/clubs/' . $club_id . '_blog' . $cat_section . $page_section;
    }

    public function getBlog($id_or_link) {
        return \cms\events::call('clubs.get_blog', $this->initBlog()->getBlog($id_or_link));
    }

    public function getPost($id_or_link) {
        return \cms\events::call('clubs.get_blog_post', $this->initBlog()->getPost($id_or_link));
    }

    public function getPosts($show_all = false, $is_short = false) {
        return \cms\events::call('clubs.get_blog_posts', $this->initBlog()->getPosts($show_all, $this, $is_short));
    }
}