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

class cms_model_users {
    /**
     * @var \cms\db
     */
    public $inDB;

    /**
     * Признак того, что join'ы и базовые фильтры списка пользователей
     * уже добавлены в builder (getUsersCount и getUsers работают с ним же).
     * @var bool
     */
    private $users_query_applied = false;

    function __construct() {
        $this->inDB = \cms\db::getInstance();
        $this->inCore = cmsCore::getInstance();
        $this->config = $this->inCore->loadComponentConfig('users');
        cmsCore::loadLanguage('components/users');
    }

    /**
     * Одиночная запись по полю без утечки условий builder'а.
     * cms\db::getItemByField() восстанавливает where/select после вызова.
     *
     * @param string $table
     * @param string $field
     * @param mixed $value
     * @return array|false
     */
    private function getItemByFieldIsolated($table, $field, $value) {
        $this->inDB->resetFilters();
        $item = $this->inDB->getItemByField($table, $field, $value);
        $this->inDB->resetFilters();

        return $item;
    }

    /**
     * Одиночная запись по id без утечки условий builder'а.
     *
     * @param string $table
     * @param mixed $id
     * @return array|false
     */
    private function getItemByIdIsolated($table, $id) {
        $this->inDB->resetFilters();
        $item = $this->inDB->getItemById($table, $id);
        $this->inDB->resetFilters();

        return $item;
    }

    /**
     * Добавляет join'ы и базовые условия выборки пользователей.
     *
     * @param bool $is_online
     */
    private function applyUsersQuery($is_online) {
        // условия уже добавлены и builder не сбрасывали извне
        if ($this->users_query_applied && $this->inDB->join) {
            return;
        }

        $this->inDB->joinInner('user_profiles', 'p', 'p.user_id = i.id');

        if ($is_online) {
            $this->inDB->joinInner('online', 'o', 'o.user_id = i.id');
        }

        $this->inDB->filterEqual('i.is_locked', 0);
        $this->inDB->filterEqual('i.is_deleted', 0);

        $this->users_query_applied = true;
    }

    public static function getDefaultConfig() {
        $cfg = array(
            'sw_comm' => 1,
            'sw_search' => 1,
            'sw_forum' => 1,
            'sw_photo' => 1,
            'sw_wall' => 1,
            'sw_blogs' => 1,
            'sw_clubs' => 1,
            'sw_feed' => 1,
            'sw_awards' => 1,
            'sw_board' => 1,
            'sw_msg' => 1,
            'sw_guest' => 1,
            'sw_gifts' => 1,
            'karmatime' => 1,
            'karmaint' => 'DAY',
            'photosize' => 0,
            'watermark' => 1,
            'smallw' => 64,
            'medw' => 200,
            'medh' => 500,
            'sw_files' => 1,
            'filessize' => 100,
            'users_perpage' => 10,
            'wall_perpage' => 10,
            'filestype' => 'jpeg,gif,png,jpg,bmp,zip,rar,tar',
            'privforms' => array(),
            'deltime' => 6
        );

        return $cfg;
    }

    public function getCommentTarget($target, $target_id) {
        $result = array();

        switch ($target) {
            case 'userphoto': $photo = $this->getItemByFieldIsolated('user_photos', 'id', $target_id);
                if (!$photo) {
                    return false;
                }

                $result['link'] = '/users/' . $photo['user_id'] . '/photo' . $target_id . '.html';
                $result['title'] = $photo['title'];

                break;
        }

        return ($result ? $result : false);
    }

    /**
     * Этот метод вызывается компонентом comments при создании нового комментария
     * @param string $target
     * @param integer $target_id
     * @return 0|1
     */
    public function getVisibility($target, $target_id) {
        $is_hidden = 0;

        switch ($target) {
            case 'userphoto': $photo = $this->getItemByFieldIsolated('user_photos', 'id', $target_id);
                if ($photo['allow_who'] != 'all') {
                    $is_hidden = 1;
                }

                $album = $this->getPhotoAlbum('private', $photo['album_id']);

                if ($album['allow_who'] != 'all') {
                    $is_hidden = 1;
                }

                break;
        }

        return $is_hidden;
    }

    /**
     * Методы стены
     */
    public function forWallIsMyProfile($user_id) {
        return $user_id == cmsUser::getInstance()->id;
    }

    public function forWallIsAdmin($user_id) {
        return cmsUser::getInstance()->is_admin;
    }

    public function addWall($item) {
        // проверяем есть ли пользователь, которому добавляем на стену
        $usr = cmsUser::getShortUserData($item['user_id']);

        if (!$usr) {
            return false;
        }

        // добавляем запись
        $wall_id = $this->inDB->insert('user_wall', \cms\events::call('users.add_wall', $item));

        $message = strip_tags($item['content']);
        $message = mb_strlen($message) > 100 ? mb_substr($message, 0, 100) : $message;

        if ($item['user_id'] == $item['author_id']) {
            cmsActions::log('add_wall_my', array(
                'object' => '',
                'object_url' => '',
                'object_id' => $wall_id,
                'target' => '',
                'target_url' => '',
                'target_id' => 0,
                'description' => $message
            ));
        } else {
            cmsActions::log('add_wall', array(
                'object' => $usr['nickname'],
                'object_url' => cmsUser::getProfileURL($usr['login']),
                'object_id' => $wall_id,
                'target' => '',
                'target_url' => '',
                'target_id' => 0,
                'description' => $message
            ));
        }

        $profile = $this->getItemByFieldIsolated('user_profiles', 'user_id', $item['user_id']);
        $usr['email_newmsg'] = $profile ? $profile['email_newmsg'] : false;

        if ($usr['email_newmsg'] && $item['user_id'] != $item['author_id']) {
            global $_LANG;

            $letter = cmsCore::getLanguageTextFile('newwallpost');
            $letter = str_replace('{sitename}', cmsConfig::getConfig('sitename'), $letter);
            $letter = str_replace('{profilelink}', HOST . cmsUser::getProfileURL($usr['login']), $letter);
            $letter = str_replace('{date}', date('d/m/Y H:i:s'), $letter);
            $letter = str_replace('{from}', $item['nickname'], $letter);

            cmsCore::getInstance()->mailText($usr['email'], $_LANG['NEW_POST_ON_WALL'] . '! - ' . cmsConfig::getConfig('sitename'), $letter);
        }

        return $wall_id;
    }

    public function deleteWallRecord($record_id) {
        $this->inDB->resetFilters();

        $this->inDB->delete('user_wall', (int) $record_id);
        cmsCore::deleteUploadImages($record_id, 'wall');

        cmsActions::removeObjectLog('add_wall_my', $record_id);
        cmsActions::removeObjectLog('add_wall', $record_id);

        return true;
    }

    public function whereUserGroupIs($group_id) {
        $this->inDB->filterEqual('i.group_id', $group_id);
    }

    public function whereNameIs($name) {
        $this->inDB->filter("LOWER(i.nickname) LIKE '%" . $this->inDB->escape($name) . "%'");
    }

    public function whereCityIs($city) {
        $this->inDB->filter("LOWER(p.city) LIKE '%" . $this->inDB->escape($city) . "%'");
    }

    public function whereHobbyIs($hobby) {
        $hobby = $this->inDB->escape($hobby);
        $this->inDB->filter("LOWER(p.description) LIKE '%" . $hobby . "%' OR LOWER(p.formsdata) LIKE '%" . $hobby . "%'");
    }

    public function whereGenderIs($gender) {
        $this->inDB->filterEqual('p.gender', $gender);
    }

    public function whereAgeTo($year) {
        $this->inDB->filter('DATEDIFF(NOW(), i.birthdate) <= ' . ((int) $year * 365));
    }

    public function whereAgeFrom($year) {
        $this->inDB->filter('DATEDIFF(NOW(), i.birthdate) >= ' . ((int) $year * 365));
    }

    public function getUsers($is_online = false) {
        $db = $this->inDB;

        $this->applyUsersQuery($is_online);

        $db->selectOnly('i.id')->
                select('i.login')->
                select('i.nickname')->
                select('i.icq')->
                select('i.logdate', 'flogdate')->
                select('i.rating')->
                select('i.is_deleted')->
                select('i.birthdate')->
                select('i.status', 'microstatus')->
                select('p.city')->
                select('p.karma')->
                select('p.imageurl')->
                select('p.gender', 'gender');

        $users = $db->get('users', false, false);

        $this->users_query_applied = false;

        if (!$users) {
            return false;
        }

        $result = array();

        foreach ($users as $user) {
            $user['avatar'] = cmsUser::getUserAvatarUrl($user['id'], 'small', $user['imageurl'], $user['is_deleted']);
            $user['user_link'] = cmsUser::getProfileLink($user['login'], $user['nickname']);
            $user['flogdate'] = cmsCore::dateFormat($user['flogdate']);
            $user['is_online'] = $is_online ? true : cmsUser::isOnline($user['id']);

            $result[] = $user;
        }

        return $result;
    }

    public function getUsersCount($is_online = false) {
        $this->applyUsersQuery($is_online);

        return $this->inDB->getCount('users');
    }

    public function getUser($login) {
        $db = $this->inDB;

        $db->resetFilters();

        $db->selectOnly('i.*')->
                select('i.status', 'status_text')->
                select('i.rating', 'user_rating')->
                select('p.id', 'pid')->
                select('p.city')->
                select('p.description')->
                select('p.showmail')->
                select('p.showbirth')->
                select('p.showicq')->
                select('p.showphone')->
                select('p.karma')->
                select('p.imageurl')->
                select('p.allow_who')->
                select('p.gender', 'gender')->
                select('p.formsdata')->
                select('p.signature')->
                select('p.email_newmsg')->
                select('p.cm_subscribe')->
                select('g.title', 'grp')->
                select('g.alias', 'group_alias')->
                select('b.user_id', 'banned')->
                select("IFNULL(ui.login, '')", 'inv_login')->
                select("IFNULL(ui.nickname, '')", 'inv_nickname')->
                joinInner('user_profiles', 'p', 'p.user_id = i.id')->
                joinInner('user_groups', 'g', 'g.id = i.group_id')->
                joinLeft('banlist', 'b', 'b.user_id = i.id AND b.status = 1')->
                joinLeft('users', 'ui', 'ui.id = i.invited_by');

        if (is_numeric($login)) {
            $db->filterEqual('i.id', $login);
        } else {
            $db->filterEqual('i.login', $login);
        }

        $db->filterEqual('i.is_locked', 0);
        $db->orderBy('i.id', 'DESC');

        $user = $db->getItem('users');

        if (!$user) {
            return false;
        }

        global $_LANG;

        $user['avatar'] = cmsUser::getUserAvatarUrl($user['id'], 'big', $user['imageurl'], $user['is_deleted']);
        $user['status_date'] = cmsCore::dateDiffNow($user['status_date']);
        $user['flogdate'] = cmsUser::getOnlineStatus($user['id'], $user['logdate']);
        $user['fregdate'] = cmsCore::dateFormat($user['regdate']);
        $user['fbirthdate'] = cmsCore::dateFormat($user['birthdate']);
        $user['cityurl'] = urlencode($user['city']);
        $user['profile_link'] = HOST . cmsUser::getProfileURL($user['login']);
        $user['fdescription'] = cmsPage::getMetaSearchLink('/users/hobby/', $user['description']);
        $user['formsdata'] = cmsCore::yamlToArray($user['formsdata']);

        if ($user['gender']) {
            switch ($user['gender']) {
                case 'm': $user['fgender'] = $_LANG['MALES'];
                    break;
                case 'f': $user['fgender'] = $_LANG['FEMALES'];
                    break;
                default: $user['fgender'] = '';
            }
        }

        return \cms\events::call('users.get_user', $user);
    }

    public function deleteUser($user_id, $is_delete = false) {
        \cms\events::call('users.delete_user', $user_id);

        if ($user_id == 1) {
            return false;
        }

        $db = $this->inDB;
        $user_id = (int) $user_id;

        $db->resetFilters();

        if ($is_delete) {
            $profile = $this->getItemByFieldIsolated('user_profiles', 'user_id', $user_id);
            $avatar = $profile ? $profile['imageurl'] : false;

            if ($avatar && $avatar != 'nopic.jpg') {
                @unlink(PATH . '/images/users/avatars/' . $avatar);
                @unlink(PATH . '/images/users/avatars/small/' . $avatar);
            }

            $db->delete('users', $user_id);
            $db->filterEqual('user_id', $user_id)->deleteFiltered('user_profiles');
            $db->filterEqual('user_id', $user_id)->filterEqual('usertype', 'user')->deleteFiltered('user_wall');
            $db->filterStart()->
                    filterEqual('to_id', $user_id)->
                    filterOr()->
                    filterEqual('from_id', $user_id)->
                    filterEnd()->
                    deleteFiltered('user_friends');
            $db->filterEqual('user_id', $user_id)->deleteFiltered('user_clubs');

            $inBlog = cmsBlogs::getInstance();
            $inBlog->owner = 'user';

            $user_blog = $inBlog->getBlogByUserId($user_id);

            if ($user_blog) {
                \cms\events::call('blogs.delete', $user_blog['id']);
                $inBlog->deleteBlog($user_blog['id']);
            }
        } else {
            $db->update('users', $user_id, array('is_deleted' => 1));
        }

        $db->resetFilters();

        $db->filterEqual('user_id', $user_id)->deleteFiltered('user_awards');
        $db->filterEqual('user_id', $user_id)->deleteFiltered('subscribe');

        cmsActions::removeUserLog($user_id);
    }

    public function deleteUsers($id_list) {
        foreach ($id_list as $key => $id) {
            $this->deleteUser($id);
        }

        return true;
    }

    public function deleteGroup($group_id) {
        \cms\events::call('users.delete_group', $group_id);

        $this->inDB->resetFilters();

        $users = $this->inDB->filterEqual('group_id', $group_id)->get('users', false, false);

        if ($users) {
            foreach ($users as $user) {
                $this->deleteUser($user['id']);
            }
        }

        $this->inDB->delete('user_groups', (int) $group_id);

        return true;
    }

    public function deleteGroups($id_list) {
        foreach ($id_list as $key => $id) {
            $this->deleteGroup($id);
        }

        return true;
    }

    public function getPluginsOutput($user) {
        $plugins_list = [];

        $data = \cms\events::call('users.view_profile', $user, 'multi');

        if (!empty($data)) {
            foreach ($data as $plugin_data) {
                if (isset($plugin_data['info']['plugin']) && isset($plugin_data['config'])) {
                    $plugins_list[] = array(
                        'name' => $plugin_data['info']['plugin'],
                        'title' => !empty($plugin_data['info']['tab']) ? $plugin_data['info']['tab'] : $plugin_data['info']['title'],
                        'ajax_link' => !empty($plugin_data['info']['ajax_link']) ? $plugin_data['info']['ajax_link'] : '',
                        'html' => $plugin_data['result']
                    );
                } else {
                    $plugins_list[] = $plugin_data;
                }
            }
        }

        return $plugins_list;
    }

    //=============================== ИНВАЙТЫ ================================//

    public function addInvite($invite) {
        $this->inDB->insert('user_invites', array(
            'code' => $invite['code'],
            'owner_id' => $invite['owner_id'],
            'createdate' => date('Y-m-d H:i:s'),
            'is_used' => 0,
            'is_sended' => 0
        ));

        return true;
    }

    public function giveInvites($count, $has_karma, $inv_period = false) {
        if (!$inv_period) {
            $sql_period = 'DAY';
        } else {
            $sql_period = $inv_period;
        }

        // интервал передаётся прямо в SQL, поэтому допускаем только слово
        if (!preg_match('/^[A-Za-z]+$/', (string) $sql_period)) {
            $sql_period = 'DAY';
        }

        $db = $this->inDB;

        $db->resetFilters();

        $users = $db->selectOnly('i.id', 'id')->
                select("IFNULL((i.invdate < DATE_SUB(NOW(), INTERVAL 1 " . $sql_period . ")) OR i.invdate IS NULL, 0)", 'is_time')->
                select('IFNULL(SUM(k.points), 0)', 'karma')->
                joinLeft('user_karma', 'k', 'k.user_id = i.id')->
                filterEqual('i.is_deleted', 0)->
                groupBy('i.id')->
                get('users', false, false);

        if (!$users) {
            return false;
        }

        $given = 0;

        foreach ($users as $user) {
            if ($user['karma'] < $has_karma) {
                continue;
            }

            if ($inv_period && !$user['is_time']) {
                continue;
            }

            for ($c = 1; $c <= $count; $c++) {
                $invite['code'] = md5($user['id'] . '$' . microtime() . '$' . uniqid() . '$' . $c);
                $invite['owner_id'] = $user['id'];

                $this->addInvite($invite);

                $given++;
            }

            $db->update('users', (int) $user['id'], array('invdate' => date('Y-m-d H:i:s')));
        }

        return $given;
    }

    public function giveInvitesCron() {
        $inCore = cmsCore::getInstance();

        $cfg = $inCore->loadComponentConfig('registration');

        if (!isset($cfg['reg_type'])) {
            $cfg['reg_type'] = 'open';
        }

        if (!isset($cfg['inv_count'])) {
            $cfg['inv_count'] = 5;
        }

        if (!isset($cfg['inv_karma'])) {
            $cfg['inv_karma'] = 50;
        }

        if (!isset($cfg['inv_period'])) {
            $cfg['inv_period'] = 'WEEK';
        }

        if ($cfg['reg_type'] != 'invite') {
            return false;
        }

        $this->giveInvites($cfg['inv_count'], $cfg['inv_karma'], $cfg['inv_period']);

        return true;
    }

    public function checkInvite($code) {
        if (!preg_match('/^([a-z0-9]{32})$/ui', $code)) {
            return false;
        }

        $this->inDB->resetFilters();

        $invite = $this->inDB->filterEqual('is_used', 0)->
                getItemByField('user_invites', 'code', $code);

        $this->inDB->resetFilters();

        return (bool) ($invite ? $invite['id'] : false);
    }

    public function getInviteOwner($code) {
        if (!preg_match('/^([a-z0-9]{32})$/ui', $code)) {
            return false;
        }

        $this->inDB->resetFilters();

        $invite = $this->inDB->filterEqual('is_used', 0)->
                getItemByField('user_invites', 'code', $code);

        $this->inDB->resetFilters();

        return $invite ? $invite['owner_id'] : false;
    }

    public function getInvite($owner_id) {
        $this->inDB->resetFilters();

        $invite = $this->inDB->filterEqual('owner_id', $owner_id)->
                filterEqual('is_used', 0)->
                filterEqual('is_sended', 0)->
                orderBy('i.id', 'ASC')->
                getItem('user_invites');

        $this->inDB->resetFilters();

        return $invite;
    }

    public function getUserInvitesCount($owner_id) {
        $this->inDB->resetFilters();

        $count = $this->inDB->filterEqual('owner_id', $owner_id)->
                filterEqual('is_used', 0)->
                filterEqual('is_sended', 0)->
                getCount('user_invites');

        $this->inDB->resetFilters();

        return $count;
    }

    public function sendInvite($owner_id, $email) {
        $inCore = cmsCore::getInstance();
        $inConf = cmsConfig::getInstance();

        global $_LANG;

        $user = cmsUser::getShortUserData($owner_id);

        if (!$user) {
            return false;
        }

        $invite = $this->getInvite($owner_id);

        if (!$invite) {
            return false;
        }

        $letter = cmsCore::getLanguageTextFile('invite');
        $letter = str_replace('{sitename}', $inConf->sitename, $letter);
        $letter = str_replace('{site_url}', HOST, $letter);
        $letter = str_replace('{invite_code}', $invite['code'], $letter);
        $letter = str_replace('{username}', $user['nickname'], $letter);

        $inCore->mailText($email, sprintf($_LANG['INVITE_SUBJECT'], $user['nickname']), $letter);

        $this->inDB->resetFilters();

        $this->inDB->update('user_invites', (int) $invite['id'], array('is_sended' => 1));

        return true;
    }

    public function closeInvite($code) {
        if (!preg_match('/^([a-z0-9]{32})$/ui', $code)) {
            return false;
        }

        $this->inDB->resetFilters();

        $this->inDB->filterEqual('code', $code)->updateFiltered('user_invites', array('is_used' => 1));

        return true;
    }

    public function deleteInvites() {
        $this->inDB->resetFilters();

        $this->inDB->filterEqual('is_used', 0)->deleteFiltered('user_invites');

        return true;
    }

    public function clearInvites() {
        $this->inDB->resetFilters();

        $this->inDB->filterEqual('is_used', 1)->
                filterEqual('is_sended', 1)->
                deleteFiltered('user_invites');

        return true;
    }

    //======================== Пользовательские фото =========================//

    public function addPhotoAlbum($album) {
        $album = \cms\events::call('users.add_photo_album', $album);

        if (!$album['allow_who']) {
            $album['allow_who'] = 'all';
        }

        $album_id = $this->inDB->insert('user_albums', array(
            'user_id' => $album['user_id'],
            'title' => $album['title'],
            'pubdate' => date('Y-m-d H:i:s'),
            'allow_who' => $album['allow_who'],
            'description' => $album['description']
        ));

        return $album_id;
    }

    public function updatePhotoAlbum($album) {
        if (!$album['allow_who']) {
            $album['allow_who'] = 'all';
        }

        $this->inDB->resetFilters();

        $this->inDB->update('user_albums', (int) $album['id'], array(
            'title' => $album['title'],
            'description' => $album['description'],
            'allow_who' => $album['allow_who']
        ));

        return true;
    }

    public function getPhotoAlbum($type, $id) {
        $album = array();

        if ($type == 'private') {
            $album = $this->getItemByIdIsolated('user_albums', $id);
        }

        if ($type == 'public') {
            $album = $this->getItemByIdIsolated('photo_albums', $id);
        }

        return $album ? $album : false;
    }

    public function getPhoto($id) {
        $photo = $this->getItemByIdIsolated('user_photos', $id);

        return $photo ? $photo : false;
    }

    public function getUserPhotoCount($user_id) {
        $this->inDB->resetFilters();

        $count = $this->inDB->filterEqual('user_id', $user_id)->getCount('user_photos');

        $this->inDB->resetFilters();

        return $count;
    }

    public function getAlbumPhotos($user_id, $album_type, $album_id, $is_friends = false) {
        $inUser = cmsUser::getInstance();
        $is_my = $inUser->id == $user_id;
        $is_friends = (int) $is_friends;
        $photos = array();
        $db = $this->inDB;

        if ($album_type == 'private') {
            $db->resetFilters();

            $db->selectOnly('i.id')->
                    select('i.pubdate')->
                    select('i.imageurl', 'file')->
                    select('i.hits')->
                    select('i.title')->
                    filterEqual('i.user_id', $user_id)->
                    filterEqual('i.album_id', $album_id);

            // фотографии видны по настройкам приватности
            if (!$is_my) {
                $db->filterStart()->
                        filterEqual('i.allow_who', 'all')->
                        filterOr()->
                        filter("i.allow_who = 'registered' AND " . (int) $inUser->id . " > 0")->
                        filterOr()->
                        filter("i.allow_who = 'friends' AND " . (int) $is_friends . " = 1")->
                        filterEnd();
            }

            $db->orderBy('i.id', 'DESC');

            $rows = $db->get('user_photos', false, false);

            foreach ($rows as $photo) {
                $photo['file'] = '/images/users/photos/small/' . $photo['file'];
                $photo['url'] = '/users/' . $user_id . '/photo' . $photo['id'] . '.html';
                $photo['fpubdate'] = cmsCore::dateFormat($photo['pubdate']);
                $photos[] = $photo;
            }
        }

        if ($album_type == 'public') {
            $db->resetFilters();

            $db->selectOnly('i.id')->
                    select('i.pubdate')->
                    select('i.file')->
                    select('i.hits')->
                    select('i.title')->
                    select('i.owner')->
                    select('a.NSDiffer')->
                    joinInner('photo_albums', 'a', 'a.id = i.album_id')->
                    filterEqual('a.published', 1)->
                    filterEqual('i.user_id', $user_id)->
                    filterEqual('i.album_id', $album_id)->
                    filterEqual('i.published', 1);

            $rows = $db->get('photo_files', false, false);

            foreach ($rows as $photo) {
                $photo['file'] = '/images/photos/small/' . $photo['file'];
                $photo['url'] = $photo['NSDiffer'] == '' ? '/photos/photo' . $photo['id'] . '.html' : '/clubs/photo' . $photo['id'] . '.html';
                $photo['fpubdate'] = cmsCore::dateFormat($photo['pubdate']);
                $photos[] = $photo;
            }
        }

        return $photos;
    }

    public function getPhotoAlbums($user_id, $is_friends = false, $only_private = false) {
        $inUser = cmsUser::getInstance();
        $is_my = $inUser->id == $user_id || $inUser->is_admin;
        $is_friends = (int) $is_friends;
        $albums = array();
        $db = $this->inDB;

        $db->resetFilters();

        $db->selectOnly('MIN(a.id)', 'id')->
                select('MIN(a.title)', 'title')->
                select('MIN(a.pubdate)', 'pubdate')->
                select('MIN(a.allow_who)', 'allow_who')->
                select("'private'", 'type')->
                select('MIN(i.imageurl)', 'imageurl')->
                select('COUNT(i.id)', 'photos_count')->
                joinInner('user_albums', 'a', 'a.id = i.album_id')->
                filterEqual('i.user_id', $user_id)->
                groupBy('i.album_id');

        if (!$is_my) {
            $db->filterStart()->
                    filterEqual('a.allow_who', 'all')->
                    filterOr()->
                    filter("a.allow_who = 'registered' AND " . (int) $inUser->id . " > 0")->
                    filterOr()->
                    filter("a.allow_who = 'friends' AND " . (int) $is_friends . " = 1")->
                    filterEnd();
        }

        $rows = $db->get('user_photos', false, false);

        foreach ($rows as $album) {
            $album['imageurl'] = "/images/users/photos/small/" . $album['imageurl'];
            $album['pubdate'] = cmsCore::dateFormat($album['pubdate']);
            $albums[] = $album;
        }

        if ($only_private) {
            $albums = \cms\events::call('users.get_albums', $albums);
            return $albums;
        }

        $db->resetFilters();

        $db->selectOnly('MIN(a.id)', 'id')->
                select('MIN(a.title)', 'title')->
                select('MIN(a.pubdate)', 'pubdate')->
                select("'all'", 'allow_who')->
                select("'public'", 'type')->
                select('MIN(i.file)', 'imageurl')->
                select('COUNT(i.id)', 'photos_count')->
                joinLeft('photo_albums', 'a', 'a.id = i.album_id')->
                filterEqual('i.user_id', $user_id)->
                filterEqual('i.published', 1)->
                groupBy('i.album_id');

        $rows = $db->get('photo_files', false, false);

        foreach ($rows as $album) {
            $album['imageurl'] = "/images/photos/small/" . $album['imageurl'];
            $album['pubdate'] = cmsCore::dateFormat($album['pubdate']);
            $albums[] = $album;
        }

        $albums = \cms\events::call('users.get_albums', $albums);

        return $albums;
    }

    public function addUploadedPhoto($user_id, $photo) {
        $this->inDB->insert('user_photos', array(
            'user_id' => $user_id,
            'album_id' => 0,
            'pubdate' => date('Y-m-d H:i:s'),
            'title' => $photo['filename'],
            'description' => '',
            'allow_who' => 'none',
            'hits' => 0,
            'imageurl' => $photo['imageurl']
        ));

        return true;
    }

    public function getUploadedPhotos($user_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $db->filterEqual('i.user_id', $user_id)->
                filterStart()->
                filterEqual('i.album_id', 0)->
                filterOr();

        $session_photos = cmsUser::sessionGet('photos_list');

        if ($session_photos) {
            $db->filterIn('i.id', array_map('intval', (array) $session_photos));
        } else {
            $db->filter('1 = 0');
        }

        $db->filterEnd();

        $photos = $db->get('user_photos');

        $photos = \cms\events::call('users.get_uploaded_photos', $photos);

        return $photos ? $photos : false;
    }

    public function deletePhoto($photo_id) {
        cmsCore::loadLib('tags');

        $photo = $this->getItemByIdIsolated('user_photos', (int) $photo_id);

        if ($photo) {
            @unlink(PATH . '/images/users/photos/' . $photo['imageurl']);
            @unlink(PATH . '/images/users/photos/small/' . $photo['imageurl']);
            @unlink(PATH . '/images/users/photos/medium/' . $photo['imageurl']);
            $this->inDB->delete('user_photos', (int) $photo_id);
            cmsCore::deleteComments('userphoto', $photo_id);
            cmsActions::removeObjectLog('add_user_photo', $photo_id);
            cmsClearTags('userphoto', $photo_id);
        }

        return true;
    }

    public function deletePhotoAlbum($user_id, $album_id) {
        $photos = $this->getAlbumPhotos($user_id, 'private', $album_id);

        if ($photos) {
            foreach ($photos as $photo) {
                $this->deletePhoto($photo['id']);
            }
        }

        cmsActions::removeTargetLog('add_user_photo_multi', $album_id);

        $this->inDB->resetFilters();

        $this->inDB->delete('user_albums', (int) $album_id);

        return true;
    }

    public function clearUploadedPhotos() {
        $db = $this->inDB;

        $db->resetFilters();

        $photos = $db->selectOnly('i.id')->
                filterStart()->
                filterEqual('i.album_id', 0)->
                filterOr()->
                filterEqual('i.allow_who', 'none')->
                filterEnd()->
                orderBy('i.id', 'ASC')->
                get('user_photos', false, false);

        foreach ($photos as $photo) {
            $this->deletePhoto($photo['id']);
        }

        return true;
    }

    public function deleteInactiveUsers() {
        $db = $this->inDB;

        $db->resetFilters();

        $users_list = $db->selectOnly('i.id')->
                filter('DATE_SUB(NOW(), INTERVAL ' . (int) $this->config['deltime'] . ' MONTH) > i.logdate')->
                get('users', false, false);

        if (!$users_list) {
            return false;
        }

        foreach ($users_list as $usr) {
            $this->deleteUser($usr['id']);
        }

        return true;
    }

    //============================= Сообщения ================================//

    public function deleteOldNotification() {
        $this->inDB->resetFilters();

        $this->inDB->filterIn('from_id', array(-1, -2))->
                filterEqual('is_new', 0)->
                filter('DATE_SUB(NOW(), INTERVAL 1 MONTH) > i.senddate')->
                deleteFiltered('user_msg');

        return true;
    }

    public function markAsReadMessage($to_id, $limit = 15, $is_user = true) {
        $db = $this->inDB;

        $db->resetFilters();

        // сначала выбираем только первые $limit непрочитанных сообщений,
        // затем помечаем именно их (UPDATE с ORDER BY/LIMIT builder не умеет)
        $rows = $db->selectOnly('i.id')->
                filterEqual('i.is_new', 1)->
                filterEqual('i.to_id', $to_id)->
                filterFunc('i.from_id', 0, $is_user ? '>' : '<')->
                orderBy('i.id', 'ASC')->
                limit(0, (int) $limit)->
                get('user_msg', false, false);

        $ids = array();

        foreach ($rows as $row) {
            $ids[] = (int) $row['id'];
        }

        if ($ids) {
            $db->filterIn('i.id', $ids)->updateFiltered('user_msg', array('is_new' => 0));
        }

        return true;
    }

    public function getReplyMessage($msg_id, $user_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $msg = $db->selectOnly('i.id')->
                select('i.senddate')->
                select('i.message')->
                select('u.login')->
                select('u.nickname')->
                joinLeft('users', 'u', 'u.id = i.from_id')->
                filterEqual('i.id', $msg_id)->
                filterEqual('i.to_id', $user_id)->
                getItem('user_msg');

        $db->resetFilters();

        if (!$msg) {
            return false;
        }

        $msg['senddate'] = cmsCore::dateFormat($msg['senddate'], true, true);

        return $msg;
    }

    public function getMessages($show_notice = false) {
        if ($show_notice) {
            return $this->getNotices();
        }

        $db = $this->inDB;

        // join'ы, where, order и limit уже заданы вызывающим кодом
        $db->selectOnly('m.*')->
                select('i.id', 'user_id')->
                select('i.nickname', 'author')->
                select('i.login', 'author_login')->
                select('i.logdate')->
                select('m.from_id', 'sender_id')->
                select('i.is_deleted')->
                select('p.imageurl')->
                joinInner('user_profiles', 'p', 'p.user_id = i.id');

        $rows = $db->get('users', false, false);

        if (!$rows) {
            return false;
        }

        $msgs = array();

        foreach ($rows as $msg) {
            $msg['authorlink'] = cmsUser::getProfileLink($msg['author_login'], $msg['author']);
            $msg['fpubdate'] = cmsCore::dateFormat($msg['senddate'], true, true, true);
            $msg['user_img'] = cmsUser::getUserAvatarUrl($msg['sender_id'], 'small', $msg['imageurl'], $msg['is_deleted']);
            $msg['online_status'] = cmsUser::getOnlineStatus($msg['user_id'], $msg['logdate']);

            $msgs[] = $msg;
        }

        return $msgs;
    }

    private function getNotices() {
        global $_LANG;

        $db = $this->inDB;

        $db->selectOnly('i.*')->select('i.from_id', 'sender_id');
        $db->filterLt('i.from_id', 0);
        $db->orderBy('i.id', 'DESC');

        $rows = $db->get('user_msg', false, false);

        if (!$rows) {
            return false;
        }

        $msgs = array();

        foreach ($rows as $msg) {
            if ($msg['sender_id'] == USER_UPDATER) {
                $msg['authorlink'] = $_LANG['SERVICE_UPDATE'];
            }

            if ($msg['sender_id'] == USER_MASSMAIL) {
                $msg['authorlink'] = $_LANG['SERVICE_MAILING'];
            }

            $msg['fpubdate'] = cmsCore::dateFormat($msg['senddate'], true, true, true);
            $msg['user_img'] = cmsUser::getUserAvatarUrl($msg['sender_id'], 'small', '', '');

            $msgs[] = $msg;
        }

        return $msgs;
    }

    public function getMessagesCount($show_notice = false) {
        if ($show_notice) {
            $this->inDB->filterLt('i.from_id', 0);

            return $this->inDB->getCount('user_msg');
        }

        return $this->inDB->getCount('users');
    }

    public function sendNotificationByEmail($to_id = 0, $from_id = 0, $msg_id = 0) {
        if (!$from_id || !$to_id || !$msg_id) {
            return false;
        }

        $inUser = cmsUser::getInstance();
        $inCore = cmsCore::getInstance();
        $inConf = cmsConfig::getInstance();

        global $_LANG;

        //проверяем подписку на уведомления
        $profile = $this->getItemByFieldIsolated('user_profiles', 'user_id', $to_id);
        $needmail = $profile ? $profile['email_newmsg'] : false;

        //если подписан и не онлайн, отправляем уведомление на email
        if (!$inUser->isOnline($to_id) && $needmail) {
            $postdate = date('d/m/Y H:i:s');
            $to_user = $this->getItemByFieldIsolated('users', 'id', $to_id);
            $to_email = $to_user ? $to_user['email'] : false;
            $from_nick = $inUser->nickname;
            $answerlink = HOST . '/users/' . $to_id . '/messages.html';

            $letter = cmsCore::getLanguageTextFile('newmessage');
            $letter = str_replace('{sitename}', $inConf->sitename, $letter);
            $letter = str_replace('{answerlink}', $answerlink, $letter);
            $letter = str_replace('{date}', $postdate, $letter);
            $letter = str_replace('{from}', $from_nick, $letter);
            $inCore->mailText($to_email, $_LANG['YOU_HAVE_NEW_MESS'] . '! - ' . $inConf->sitename, $letter);

            return true;
        }

        return false;
    }

    public function isUserCanChangeKarma($user_id) {
        $inUser = cmsUser::getInstance();

        if ($user_id == $inUser->id) {
            return false;
        }

        $interval = isset($this->config['karmaint']) ? $this->config['karmaint'] : 'DAY';

        if (!preg_match('/^[A-Za-z]+$/', (string) $interval)) {
            $interval = 'DAY';
        }

        $db = $this->inDB;

        $db->resetFilters();

        $karma = $db->filterEqual('i.user_id', $user_id)->
                filterEqual('i.sender_id', $inUser->id)->
                filter('i.senddate >= DATE_SUB(NOW(), INTERVAL ' . (int) $this->config['karmatime'] . ' ' . $interval . ')')->
                getCount('user_karma');

        $db->resetFilters();

        return !$karma;
    }

    public function getUserKarma($user_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $rows = $db->selectOnly('i.*')->
                select('i.points', 'kpoints')->
                select('u.nickname')->
                select('u.login')->
                joinLeft('users', 'u', 'u.id = i.sender_id')->
                filterEqual('i.user_id', $user_id)->
                orderBy('i.senddate', 'DESC')->
                limit(0, 50)->
                get('user_karma', false, false);

        $karma = array();

        foreach ($rows as $k) {
            $k['fsenddate'] = cmsCore::dateFormat($k['senddate'], true, true);
            $karma[] = $k;
        }

        return $karma;
    }

    public function getUserAwards($user_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $rows = $db->filterEqual('i.user_id', $user_id)->
                orderBy('i.pubdate', 'DESC')->
                get('user_awards', false, false);

        $aws = array();

        foreach ($rows as $aw) {
            $aw['pubdate'] = cmsCore::dateFormat($aw['pubdate']);
            $aws[] = $aw;
        }

        return $aws;
    }

    public function getUserFilesSize($user_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $size = $db->selectOnly('SUM(i.filesize)', 'totalsize')->
                filterEqual('i.user_id', $user_id)->
                groupBy('i.user_id')->
                getItem('user_files');

        $db->resetFilters();

        return $size ? $size['totalsize'] : 0;
    }

    public function getUserFiles($for_all = false) {
        $db = $this->inDB;

        if (!$for_all) {
            $db->filterEqual('i.allow_who', 'all');
        }

        $rows = $db->get('user_files', false, false);

        if (!$rows) {
            return array();
        }

        $files = array();
        $rownum = 0;

        foreach ($rows as $file) {
            $file['pubdate'] = cmsCore::dateFormat($file['pubdate']);
            $file['filelink'] = HOST . '/users/files/download' . $file['id'] . '.html';
            $file['fileicon'] = cmsCore::fileIcon($file['filename']);
            $file['mb'] = round(($file['filesize'] / 1024) / 1024, 2);

            if ($file['mb'] == '0') {
                $file['mb'] = '~ 0';
            }

            $file['rownum'] = $rownum;
            $rownum++;

            $files[] = $file;
        }

        return $files;
    }

    public function getUserFilesCount($for_all = false) {
        $db = $this->inDB;

        if (!$for_all) {
            $db->filterEqual('i.allow_who', 'all');
        }

        return $db->getCount('user_files');
    }
}