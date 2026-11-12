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

define('USER_UPDATER', -1);
define('USER_MASSMAIL', -2);
define('ONLINE_INTERVAL', 3); // интервал в минутах, когда пользователь считается online

class cmsUser {

    use \Singeltone;

    const PROFILE_LINK_PREFIX = 'users/';

    protected static $_ip;
    private $friends = array();
    private $new_msg = array();
    private $geo_is_loaded = false;
    private static $guest_group_info = array();
    private static $cache = array();
    private $loads_users = array();
    public $id = 0;
    public $is_admin = 0;
    public $online_users;
    public $online_users_ids;

    /**
     * Обновляет данные пользователя, если он не забанен
     * заполняя ими свойства объекта
     * @return bool
     */
    public function update() {
        // привязка ip адреса к сессии
        if (!$this->checkSpoofingSession()) {
            $this->logout();
            cmsCore::redirectBack();
        }

        $user_id = (int) (isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0);

        // Свойства для гостя
        if (!$user_id) {
            self::setUserLogdate();

            $guest_info = self::getGuestInfo();

            foreach ($guest_info as $key => $value) {
                $this->{$key} = $value;
            }
        } else {
            // свойства для авторизованного пользователя
            $info = $this->loadUser($user_id);

            if (!$info) {
                return false;
            }

            foreach ($info as $key => $value) {
                $this->{$key} = $value;
            }

            $this->new_msg = self::getNewMessages($user_id);
            $this->new_msg_count = $this->new_msg['total'];

            $this->logdate = self::getUserLogdate();
        }

        // проверяем бан
        $this->checkBan();

        return true;
    }

    /**
     * Загружает в свойства данные местоположения пользователя
     */
    public function loadUserGeo() {
        if ($this->geo_is_loaded) {
            return true;
        }

        $inCore = cmsCore::getInstance();

        $geo_cfg = $inCore->loadComponentConfig('geo');

        if ($geo_cfg['component_enabled'] && $geo_cfg['autodetect'] && class_exists('cms' . $geo_cfg['class'])) {
            $geo_data = call_user_func(array('cms' . $geo_cfg['class'], 'getInfo'), $this->ip);

            if ($geo_data) {
                foreach ($geo_data as $k => $v) {
                    $this->geo[$k] = $v;
                }

                // если пользователь город не указал или это гость
                if (!$this->city) {
                    $this->city = isset($this->geo['city']) ? $this->geo['city'] : '';
                }

                $this->geo_is_loaded = true;

                return true;
            }
        }

        return false;
    }

    private function checkSpoofingSession() {
        // первый раз зашли
        if (!isset($_SESSION['user_net'])) {
            $octets = explode('.', $_SERVER['REMOTE_ADDR']);
            $_SESSION['user_net'] = rtrim($_SERVER['REMOTE_ADDR'], end($octets));
            return true;
        }

        return mb_strstr($_SERVER['REMOTE_ADDR'], $_SESSION['user_net']);
    }

    /**
     * Загружает данные пользователя из базы
     * @param int $user_id
     * @return array
     */
    public function loadUser($user_id, $where = '') {
        if ($user_id) {
            if (isset($this->loads_users[$user_id])) {
                return $this->loads_users[$user_id];
            }
        }

        $where = $user_id ? "i.id = '" . (int) $user_id . "'" : $where;

        if (!$where) {
            return false;
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $info = $db->select('i.*')
                ->select('g.is_admin')
                ->select('g.alias')
                ->select('g.access')
                ->select('p.imageurl')
                ->select('p.imageurl', 'orig_imageurl')
                ->select('p.karma')
                ->select('p.city')
                ->joinInner('user_groups', 'g', 'g.id = i.group_id')
                ->joinInner('user_profiles', 'p', 'p.user_id = i.id')
                ->filter($where)
                ->filterEqual('i.is_deleted', 0)
                ->filterEqual('i.is_locked', 0)
                ->getItem('users');

        $db->resetFilters();

        if (!$info) {
            return false;
        }

        $info['ip'] = self::getIp();

        $info['imageurl'] = self::getUserAvatarUrl($info['id'], 'small', $info['imageurl'], $info['is_deleted']);

        $info['access'] = explode(',', str_replace(', ', ',', $info['access']));

        return $this->loads_users[$info['id']] = \cms\events::call('users.load_user', $info);
    }

    /**
     * Проверяет, находится ли текущий посетитель в бан-листе
     * Если да, то показывает сообщение и завершает работу
     */
    private function checkBan() {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $db->filterStart();

        if ($this->id) {
            $db->filterEqual('ip', $this->ip)
                    ->filterOr()
                    ->filterEqual('user_id', (int) $this->id);
        } else {
            $db->filterEqual('ip', $this->ip);
        }

        $db->filterEnd()->filterEqual('status', 1);

        // Проверяем бан
        $ban = $db->select('i.int_num')
                ->select('i.int_period')
                ->select('i.autodelete')
                ->select('i.id')
                ->select('i.status')
                ->select('i.bandate')
                ->select('i.user_id')
                ->select('i.cause')
                ->getItem('banlist');

        $db->resetFilters();

        if (!$ban) {
            return;
        }

        if ($this->id) {
            $db->filterEqual('user_id', (int) $this->id)->updateFiltered('banlist', array(
                'ip' => $this->ip
            ));
        }

        $interval = $ban['int_num'] . ' ' . $ban['int_period'];

        $db->resetFilters();

        $is_expired = $db->filterEqual('id', (int) $ban['id'])
                ->filterFunc('bandate', 'DATE_SUB(NOW(), INTERVAL ' . $interval . ')', '<=')
                ->filterGt('int_num', 0)
                ->getCount('banlist');

        $db->resetFilters();

        // проверяем истек ли срок бана
        if ($is_expired) {
            // если истек и флаг автоудаления есть, удаляем
            if ($ban['autodelete']) {
                $db->delete('banlist', (int) $ban['id']);
            } else {
                $db->update('banlist', (int) $ban['id'], array('status' => 0));
            }
        } else {
            global $_LANG;
            $ban['bandate'] = cmsCore::dateFormat($ban['bandate']);
            $ban['enddate'] = cmsCore::spellCount($ban['int_num'], $_LANG[$ban['int_period'] . '1'], $_LANG[$ban['int_period'] . '2'], $_LANG[$ban['int_period'] . '10']);
            cmsPage::includeTemplateFile('special/bantext.php', array('ban' => $ban));
            $this->logout();
            cmsCore::halt();
        }
    }

    /**
     * Проверяет наличие кукиса "запомнить меня" и если он найден - авторизует пользователя
     * @return bool
     */
    public function autoLogin() {
        $user_id = (int) (isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0);

        if (cmsCore::getCookie('userid') && !$user_id) {
            $cookie_code = (string) cmsCore::getCookie('userid');

            if (!preg_match('/^[0-9a-f]{32}$/i', $cookie_code)) {
                return false;
            }

            $user = $this->loadUser(0, "md5(CONCAT(i.id, i.password,'" . \cms\db::getInstance()->escape_string(PATH) . "')) = '$cookie_code'");

            if ($user) {
                $_SESSION['user'] = $user;
                \cms\events::call('users.login', $_SESSION['user']);
                self::setUserLogdate($user['id']);
            } else {
                cmsCore::unsetCookie('user_id');
            }
        }

        return true;
    }

    /**
     * Возвращает url аватара пользователя
     * @param int $user_id
     * @param str $size
     * @param str $file_name
     * @param int $usr_is_deleted
     * @return str
     */
    public static function getUserAvatarUrl($user_id, $size = 'small', $file_name = '', $usr_is_deleted = 0) {
        // службы обновлений и рассылки
        if ($user_id == -1) {
            return '/images/messages/update.jpg';
        }

        if ($user_id == -2) {
            return '/images/messages/massmail.jpg';
        }

        // пользователь без аватара
        if (!$file_name || !file_exists(PATH . '/images/users/avatars/' . $file_name)) {
            if ($size == 'small') {
                return '/images/users/avatars/small/nopic.png';
            } else {
                return '/images/users/avatars/nopic.png';
            }
        }

        if ($usr_is_deleted) {
            if ($size == 'small') {
                return '/images/users/avatars/small/noprofile.png';
            } else {
                return '/images/users/avatars/noprofile.png';
            }
        } else {
            if ($size == 'small') {
                return '/images/users/avatars/small/' . $file_name;
            } else {
                return '/images/users/avatars/' . $file_name;
            }
        }
    }

    /**
     * Возвращает некешированный рейтинг пользователя
     * @param int $user_id ID пользователя
     * @return int
     */
    public static function getRating($user_id) {
        $db = \cms\db::getInstance();

        $rating = 0;

        $db->resetFilters();

        $targets = $db->select('i.target')
                ->select('i.user_weight')
                ->select('i.target_table')
                ->filterEqual('i.is_user_affect', 1)
                ->orderBy('i.user_weight', 'ASC')
                ->get('rating_targets', false, '');

        $db->resetFilters();

        if (!$targets) {
            return $rating;
        }

        foreach ($targets as $target) {
            $target_table = $target['target_table'];

            if ($db->prefix && strpos($target_table, $db->prefix) === 0) {
                $target_table = substr($target_table, strlen($db->prefix));
            }

            $db->resetFilters();

            $data = $db->select('SUM(i.total_rating)', 'rating')
                    ->joinInner($target_table, $target['target'], $target['target'] . '.id = i.item_id')
                    ->filterEqual('i.target', $target['target'])
                    ->filterEqual($target['target'] . '.user_id', $user_id)
                    ->getItem('ratings_total');

            $db->resetFilters();

            $rating += (int) @$data['rating'] * (int) $target['user_weight'];
        }

        return $rating;
    }

    /**
     * Возвращает некешированное значение кармы пользователя
     * @param int $user_id
     * @return int
     */
    public static function getKarma($user_id) {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $data = $db->select('SUM(i.points)', 'karma')
                ->filterEqual('i.user_id', (int) $user_id)
                ->groupBy('i.user_id')
                ->getItem('user_karma');

        $db->resetFilters();

        if (!$data) {
            return 0;
        }

        return (int) $data['karma'];
    }

    /**
     * Изменяет карму пользователя
     * @param int $to_user_id Кому изменяем карму
     * @param int $points На сколько
     * @param int $from_user_id Кто изменяет карму
     * @return int
     */
    public static function changeKarmaUser($to_user_id, $points, $from_user_id = 0) {
        $db = \cms\db::getInstance();

        $from_user_id = $from_user_id ? $from_user_id : self::getInstance()->id;

        $db->insert('user_karma', array(
            'user_id' => (int) $to_user_id,
            'sender_id' => (int) $from_user_id,
            'points' => (int) $points,
            'senddate' => date('Y-m-d H:i:s')
        ));

        $user_karma = self::getKarma($to_user_id);

        $db->filterEqual('user_id', (int) $to_user_id)->updateFiltered('user_profiles', array(
            'karma' => $user_karma
        ));

        self::checkAwards($to_user_id);

        return $user_karma;
    }

    /**
     * Возвращает ссылки на профили именниников
     * @return html
     */
    public static function getBirthdayUsers() {
        $db = \cms\db::getInstance();

        $today = date("d-m");

        $db->resetFilters();

        $users = $db->select('i.id', 'id')
                ->select('i.nickname', 'nickname')
                ->select('i.login', 'login')
                ->select('i.birthdate')
                ->select('p.gender', 'gender')
                ->joinLeft('user_profiles', 'p', 'p.user_id = i.id')
                ->filterEqual('i.is_locked', 0)
                ->filterEqual('i.is_deleted', 0)
                ->filterEqual('p.showbirth', 1)
                ->filter("DATE_FORMAT(i.birthdate, '%d-%m') = '" . $db->escape($today) . "'")
                ->get('users', false, '');

        $db->resetFilters();

        if (!$users) {
            return false;
        }

        $now = 0;
        $html = '';

        $total = count($users);

        foreach ($users as $usr) {
            $html .= self::getGenderLink($usr['id'], $usr['nickname'], $usr['gender'], $usr['login']);

            if ($now < $total - 1) {
                $html .= ', ';
            }

            $now++;
        }

        return $html;
    }

    /**
     * Возвращает элементы <option> для списка пользователей
     * @param int $selected
     * @param array $exclude
     * @return html
     */
    public static function getUsersList($selected = 0, $exclude = array()) {
        $db = \cms\db::getInstance();

        $html = '';

        $db->resetFilters();

        $users = $db->select('i.id')
                ->select('i.nickname')
                ->filterEqual('i.is_locked', 0)
                ->filterEqual('i.is_deleted', 0)
                ->orderBy('i.nickname', 'ASC')
                ->get('users', false, '');

        $db->resetFilters();

        if (!$users) {
            return;
        }

        foreach ($users as $u) {
            if (!in_array($u['id'], $exclude)) {
                if ($selected) {
                    if ($u['id'] == $selected) {
                        $html .= '<option value="' . $u['id'] . '" selected="selected">' . $u['nickname'] . '</option>';
                    } else {
                        $html .= '<option value="' . $u['id'] . '">' . $u['nickname'] . '</option>';
                    }
                } else {
                    $html .= '<option value="' . $u['id'] . '">' . $u['nickname'] . '</option>';
                }
            }
        }

        return $html;
    }

    /**
     * Возвращает элементы <option> для списка пользователей
     * @param int $selected
     * @param array $exclude
     * @return html
     */
    public static function getAuthorsList($authors, $selected = '') {
        if (!$authors) {
            return;
        }

        $db = \cms\db::getInstance();
        $html = '';

        $db->resetFilters();

        $users = $db->select('i.id')
                ->select('i.nickname')
                ->filterIn('i.id', $authors)
                ->get('users', false, '');

        $db->resetFilters();

        if ($users) {
            foreach ($users as $u) {
                if ($selected) {
                    if (in_array($u['id'], $selected)) {
                        $html .= '<option value="' . $u['id'] . '" selected="selected">' . $u['nickname'] . '</option>';
                    } else {
                        $html .= '<option value="' . $u['id'] . '">' . $u['nickname'] . '</option>';
                    }
                } else {
                    $html .= '<option value="' . $u['id'] . '">' . $u['nickname'] . '</option>';
                }
            }
        }

        return $html;
    }

    /**
     * Возвращает массив картинок наград
     * @param str $dir
     * @return array
     */
    public static function getAwardsImages($dir = '/images/users/awards') {
        $images = array();

        if ($handle = opendir(PATH . $dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && mb_strstr($file, '.gif')) {
                    $tag = str_replace('.gif', '', $file);

                    $images[] = htmlspecialchars($tag . '.gif');
                }
            }

            closedir($handle);
        }

        return $images;
    }

    /**
     * Возвращает элементы <option> для списка друзей пользователя
     * @param int $user_id
     * @param int $selected
     * @return html
     */
    public static function getFriendsList($user_id, $selected = 0) {
        $db = \cms\db::getInstance();

        $html = '';

        $db->resetFilters();

        $friend_id = (int) $user_id;

        $friends = $db->select("CASE WHEN i.from_id = " . $friend_id . " THEN i.to_id WHEN i.to_id = " . $friend_id . " THEN i.from_id END", 'id')
                ->select('u.nickname', 'nickname')
                ->joinLeft('users', 'u', 'u.id = CASE WHEN i.from_id = ' . $friend_id . ' THEN i.to_id WHEN i.to_id = ' . $friend_id . ' THEN i.from_id END')
                ->filterStart()
                ->filterEqual('i.from_id', $friend_id)
                ->filterOr()
                ->filterEqual('i.to_id', $friend_id)
                ->filterEnd()
                ->filterEqual('i.is_accepted', 1)
                ->get('user_friends', false, '');

        $db->resetFilters();

        if ($friends) {
            foreach ($friends as $friend) {
                if (@$selected == $friend['id']) {
                    $s = 'selected';
                } else {
                    $s = '';
                }

                $html .= '<option value="' . $friend['id'] . '" ' . $s . '>' . $friend['nickname'] . '</option>';
            }
        } else {
            $html = '<option value="0" selected>-- Нет друзей --</option>';
        }
        return $html;
    }

    /**
     * Проверяет дружбу текущего пользователя с $user_id
     * @return bool
     */
    public static function isFriend($user_id = 0) {
        if (!$user_id) {
            return false;
        }

        $my_id = self::getInstance()->id;

        if (!$my_id) {
            return false;
        }

        $my_friends = cmsUser::getFriends($my_id);

        if (!$my_friends) {
            return false;
        }

        $is_friend = false;

        foreach ($my_friends as $friend) {
            if ($friend['id'] == $user_id) {
                $is_friend = true;
                break;
            } else {
                $is_friend = false;
            }
        }

        return $is_friend;
    }

    /**
     * Создает запрос на добавление текущего пользователя в друзья к $user_id
     * @param int $user_id
     * @return bool
     */
    public static function addFriend($user_id = 0) {
        if (!$user_id) {
            return false;
        }

        \cms\events::call('users.add_friend', $user_id);

        $my_id = self::getInstance()->id;

        if (!$my_id) {
            return false;
        }

        return \cms\db::getInstance()->insert('user_friends', array(
                    'to_id' => (int) $user_id,
                    'from_id' => (int) $my_id,
                    'logdate' => date('Y-m-d H:i:s'),
                    'is_accepted' => 0
        ));
    }

    /**
     * Удаляет пользователя $user_id из списка друзей текущего пользователя
     * @param int $user_id
     * @return bool
     */
    public static function deleteFriend($user_id = 0) {
        if (!$user_id) {
            return false;
        }

        $friend_field_id = self::getFriendFieldId($user_id);

        if ($friend_field_id) {
            \cms\events::call('users.delete_friend', $user_id);

            \cms\db::getInstance()->delete('user_friends', (int) $friend_field_id);

            cmsActions::removeObjectLog('add_friend', $friend_field_id);
            cmsUser::clearSessionFriends();

            return true;
        }

        return false;
    }

    /**
     * Возвращает ID записи, где указана ваша дружба с $user_id
     * @param int $user_id
     * @param mixed $acepted Возможные значения: false, 0, 1
     * @return bool
     */
    public static function getFriendFieldId($user_id = 0, $accepted = false, $to_from = false) {
        if (!$user_id) {
            return false;
        }

        $my_id = self::getInstance()->id;

        if (!$my_id) {
            return false;
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        if ($to_from === false) {
            $db->filterIn('to_id', array((int) $user_id, (int) $my_id))
                    ->filterIn('from_id', array((int) $my_id, (int) $user_id));
        } else {
            // Если ко мне в друзья просились
            if ($to_from == 'to_me') {
                $db->filterEqual('to_id', (int) $my_id)->filterEqual('from_id', (int) $user_id);
            } else { // я просился в друзья
                $db->filterEqual('to_id', (int) $user_id)->filterEqual('from_id', (int) $my_id);
            }
        }

        if ($accepted !== false) {
            $db->filterEqual('is_accepted', $accepted ? 1 : 0);
        }

        $friend_field_id = $db->getFieldFiltered('user_friends', 'id');

        $db->resetFilters();

        return $friend_field_id;
    }

    /**
     * Возвращает количество друзей пользователя
     * @param int $user_id
     * @return int
     */
    public static function getFriendsCount($user_id = 0) {
        if (!$user_id) {
            return 0;
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $count = $db->filterStart()
                ->filterEqual('from_id', (int) $user_id)
                ->filterOr()
                ->filterEqual('to_id', (int) $user_id)
                ->filterEnd()
                ->filterEqual('is_accepted', 1)
                ->getCount('user_friends');

        $db->resetFilters();

        return $count;
    }

    /**
     * Возвращает список друзей пользователя
     * и помещает в текущую сессию
     * @param int $user_id
     * @return array
     */
    public static function getFriends($user_id = 0) {
        if (!$user_id) {
            return array();
        }

        $friends = array();

        // уже полученных друзей отдаем сразу
        if (isset(self::getInstance()->friends[$user_id])) {
            return self::getInstance()->friends[$user_id];
        }

        $is_me = (@$_SESSION['user']['id'] == $user_id);

        // Если список уже в сессии, возвращаем
        if ($is_me && !empty(self::sessionGet('friends'))) {
            foreach (self::sessionGet('friends') as $key => $friend) {
                $friend['flogdate'] = self::getOnlineStatus($friend['id'], $friend['logdate']);
                $friends[$key] = $friend;
            }

            return $friends;
        }

        //иначе получаем список из базы, кладем в сессию и возвращаем
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $friend_id = (int) $user_id;

        $rs = $db->select("CASE WHEN i.from_id = " . $friend_id . " THEN i.to_id WHEN i.to_id = " . $friend_id . " THEN i.from_id END", 'id')
                ->select('u.nickname', 'nickname')
                ->select('u.login', 'login')
                ->select('u.is_deleted')
                ->select('u.status')
                ->select('u.logdate')
                ->select('p.imageurl')
                ->joinLeft('users', 'u', 'u.id = CASE WHEN i.from_id = ' . $friend_id . ' THEN i.to_id WHEN i.to_id = ' . $friend_id . ' THEN i.from_id END')
                ->joinInner('user_profiles', 'p', 'p.user_id = u.id')
                ->filterStart()
                ->filterEqual('i.from_id', $friend_id)
                ->filterOr()
                ->filterEqual('i.to_id', $friend_id)
                ->filterEnd()
                ->filterEqual('i.is_accepted', 1)
                ->orderBy('u.logdate', 'DESC')
                ->get('user_friends', false, '');

        $db->resetFilters();

        if ($rs) {
            foreach ($rs as $friend) {
                $friend['avatar'] = self::getUserAvatarUrl($friend['id'], 'small', $friend['imageurl'], $friend['is_deleted']);
                $friend['is_online'] = self::isOnline($friend['id']);
                $friend['flogdate'] = self::getOnlineStatus($friend['id'], $friend['logdate']);
                $friends[$friend['id']] = $friend;
            }
        }

        // своих друзей кладем в сессию
        if ($is_me) {
            self::sessionPut('friends', $friends);
        }

        // Запоминаем список друзей пользователя
        self::getInstance()->friends[$user_id] = $friends;

        return $friends;
    }

    /*
     * Очищает список друзей в сессии
     */

    public static function clearSessionFriends() {
        self::sessionDel('friends');
    }

    /**
     * Возвращает html стены пользователя
     * @param int $selected
     * @param array $exclude
     * @param string $limit Готовое значение LIMIT (необязательно)
     * @return html
     */
    public static function getUserWall($target_id, $component = 'users', $my_profile = 0, $is_admin = 0, $limit = null) {
        $db = \cms\db::getInstance();
        $inUser = self::getInstance();

        // AJAX-постраничная загрузка передает LIMIT явно из нового слоя БД
        if ($limit === null) {
            $limit = $db->limit;
        }

        // сортировку можно заранее задать через builder
        $order_by = $db->order_by;

        cmsCore::loadLanguage('components/' . $component);

        if (!$my_profile && !$is_admin) {
            $my_profile = $inUser->is_admin;
        }

        $records = array();

        //получаем общее число записей на стене этого пользователя
        $db->resetFilters();

        $total = $db->filterEqual('i.user_id', (int) $target_id)
                ->filterEqual('i.usertype', $component)
                ->getCount('user_wall');

        if ($total) {
            if ($limit) {
                $db->limit = $limit;
            }

            if ($order_by) {
                $db->order_by = $order_by;
            } else {
                $db->orderBy('i.pubdate', 'DESC');
            }

            $records = $db->select('i.*')
                    ->select('g.gender')
                    ->select('g.imageurl')
                    ->select('u.nickname', 'author')
                    ->select('u.login', 'author_login')
                    ->select('u.is_deleted')
                    ->select('i.pubdate')
                    ->joinInner('users', 'u', 'u.id = i.author_id')
                    ->joinInner('user_profiles', 'g', 'g.user_id = u.id')
                    ->filterEqual('i.user_id', (int) $target_id)
                    ->filterEqual('i.usertype', $component)
                    ->get('user_wall', false, '');

            foreach ($records as $key => $record) {
                $records[$key]['is_today'] = time() - strtotime($record['pubdate']) < 86400;
                $records[$key]['fpubdate'] = $record['is_today'] ? cmsCore::dateDiffNow($record['pubdate']) : cmsCore::dateFormat($record['pubdate']);
                $records[$key]['avatar'] = cmsUser::getUserAvatarUrl($record['author_id'], 'small', $record['imageurl'], $record['is_deleted']);
            }

            $records = \cms\events::call('users.get_wall_posts', $records);
        }

        // не оставляем фильтры стены в общем builder'е для следующих запросов
        $perpage = $db->perpage;
        $page = isset($db->page) ? $db->page : 1;
        $db->resetFilters();

        ob_start();

        cmsPage::initTemplate('components', 'com_users_wall')->
                assign('records', $records)->
                assign('user_id', $inUser->id)->
                assign('target_id', $target_id)->
                assign('my_profile', $my_profile)->
                assign('is_admin', $is_admin)->
                assign('component', $component)->
                assign('total', $total)->
                assign('pagebar', cmsPage::getPagebar($total, $page, $perpage, 'javascript:wallPage(%page%)'))->
                display('com_users_wall.tpl');

        return ob_get_clean();
    }

    /**
     * Проверяет доступ авторизованного пользователя к чему-либо
     * @param str $allow_who
     * @return bool
     */
    public static function checkUserContentAccess($allow_who, $user_id) {
        $access = false;

        $inUser = self::getInstance();

        // автору показываем всегда
        if ($inUser->id == $user_id) {
            return true;
        }

        // администраторам показываем всегда
        if ($inUser->is_admin) {
            return true;
        }

        switch ($allow_who) {
            case 'all': $access = true;
                break;
            case 'registered': $access = $inUser->id ? true : false;
                break;
            case 'nobody': $access = $inUser->id == $user_id ? true : false;
                break;
            case 'friends': $access = $inUser->isFriend($user_id);
                break;
            default: $access = false;
        }

        return $access;
    }

    /**
     * Проверяет, голосовал ли пользователь за указанную цель
     * @return int
     */
    public static function isRateUser($target = '', $user_id = 0, $item_id = 0) {
        // если на входе одноги из параметров нет, считаем, что пользователь голосовал
        if (!$target || !$user_id || !$item_id) {
            return true;
        }

        // если для этого запроса есть кеш, возвращаем значение из кеша
        if (isset(self::$cache[$target][$item_id][$user_id])) {
            return self::$cache[$target][$item_id][$user_id];
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $is_rate = $db->filterEqual('item_id', (int) $item_id)
                ->filterEqual('target', $target)
                ->filterEqual('user_id', $user_id)
                ->getCount('ratings');

        $db->resetFilters();

        // возвращаем и кешируем значение
        return self::$cache[$target][$item_id][$user_id] = $is_rate;
    }

    /**
     * Возвращает массив информации для пользователя "Гость"
     * @return array
     */
    public static function getGuestInfo($field = '') {
        if (!self::$guest_group_info) {
            $db = \cms\db::getInstance();

            $db->resetFilters();

            $data = $db->select('i.id')->select('i.access')
                    ->filterEqual('i.alias', 'guest')
                    ->getItem('user_groups');

            $db->resetFilters();

            if ($data) {
                $data['group_id'] = $data['id'];
                $data['access'] = explode(',', str_replace(', ', ',', $data['access']));
                $data['id'] = 0;
                $data['ip'] = self::getIp();
                $data['is_admin'] = 0;
                $data['karma'] = -1000000;
                $data['logdate'] = self::getUserLogdate();
                $data['city'] = '';
                self::$guest_group_info = \cms\events::call('users.get_guest', $data);
            } else {
                self::$guest_group_info = array();
            }
        }

        // если запрашивали конкретное что то, возвращаем если есть
        // иначе возвращаем массив
        if ($field) {
            return isset(self::$guest_group_info[$field]) ? self::$guest_group_info[$field] : false;
        } else {
            return self::$guest_group_info;
        }
    }

    /**
     * Возвращает дату последнего посещения пользователя
     * @return str
     */
    public static function getUserLogdate() {
        // получаем значение из сессии
        $ses_logdate = self::sessionGet('logdate');

        // если есть, возвращаем
        if ($ses_logdate) {
            return date('Y-m-d H:i:s', $ses_logdate);
        }

        // Получаем время визита из куков
        $cookie_logdate = (int) cmsCore::getCookie('logdate');

        // если есть, возвращаем
        if ($cookie_logdate) {
            return date('Y-m-d H:i:s', $cookie_logdate);
        }

        // Иначе возвращаем текущую дату
        return date('Y-m-d H:i:s');
    }

    /**
     * Устанавливает дату последнего посещения пользователя
     * @return str
     */
    public static function setUserLogdate($user_id = 0) {
        $ses_logdate = self::sessionGet('logdate');

        // Если нет в сессии, кладем в сессию предыдущее значение
        // из куков, если там тоже нет, то текущее
        if (!$ses_logdate) {
            $cookie_logdate = (int) cmsCore::getCookie('logdate');
            self::sessionPut('logdate', ($cookie_logdate ? $cookie_logdate : time()));
            cmsCore::setCookie('logdate', time(), time() + 60 * 60 * 24 * 30);
        }

        if ($user_id) {
            \cms\db::getInstance()->update('users', (int) $user_id, array(
                'logdate' => date('Y-m-d H:i:s')
            ));
        }

        return true;
    }

    /**
     * Обновляет статистику посещений сайта
     */
    public function onlineStats() {
        // бот пришел или куки отключены, статистику не обновляем
        if (empty($_COOKIE)) {
            return;
        }

        //если учет отключен
        if (!cmsConfig::getConfig('user_stats')) {
            return;
        }

        //собираем информацию о текущем пользователе
        $sess_id = session_id();
        $useragent = cmsCore::strClear((string) $_SERVER['HTTP_USER_AGENT']);
        $page = cmsCore::isAjax() ? '' : cmsCore::strClear((string) $_SERVER['REQUEST_URI']);

        // поля online.agent/viewurl — varchar(250)
        $useragent = mb_substr($useragent, 0, 250);
        $page = mb_substr($page, 0, 250);

        \cms\db::getInstance()->insertOrUpdate('online', array(
            'ip' => $this->ip,
            'sess_id' => $sess_id,
            'user_id' => $this->id,
            'agent' => $useragent,
            'viewurl' => $page
                ), array(
            'agent' => $useragent,
            'viewurl' => $page
        ));

        // удаляем старые записи
        if (cmsConfig::getConfig('user_stats') == 1) {
            self::clearOnlineUsers();
        }

        // загружаем всех пользователей, кто онлайн
        $this->loadOnlineUsers();
    }

    /**
     * Удаляет просроченные данные об online пользователях
     */
    public static function clearOnlineUsers() {
        return \cms\db::getInstance()
                        ->filterFunc('lastdate', 'DATE_SUB(NOW(), INTERVAL ' . ONLINE_INTERVAL . ' MINUTE)', '<=')
                        ->deleteFiltered('online');
    }

    /**
     * Загружает всех пользователей и гостей кто онлайн
     */
    private function loadOnlineUsers() {
        if (!isset($this->online_users)) {
            $this->online_users = \cms\db::getInstance()->getRows('online');
        }
    }

    /**
     * Возвращает массив с количеством гостей и пользователей онлайн
     * @return array
     */
    public static function getOnlineCount() {
        $ou = self::getInstance()->online_users;

        $guests = 0;
        $online = array();

        if (!empty($ou)) {
            foreach ($ou as $o) {
                if ($o['user_id'] == 0 || $o['user_id'] == '') {
                    $guests++;
                } else {
                    $online[$o['user_id']][] = $o;
                }
            }
        }

        $people['guests'] = $guests;
        $people['users'] = sizeof($online);

        return $people;
    }

    /**
     * Возвращает отформатированную дату последнего визита
     * @param int $user_id ID пользователя
     * @param str $logdate Дата последнего визита
     * @return str
     */
    public static function getOnlineStatus($user_id, $logdate = '') {
        global $_LANG;

        if (self::isOnline($user_id)) {
            $status = '<span class="online">' . $_LANG['ONLINE'] . '</span>';
        } else {
            if ($logdate) {
                $status = '<span class="logdate">' . cmsCore::dateDiffNow($logdate) . ' ' . $_LANG['BACK'] . '</span>';
            } else {
                $status = '<span class="offline">' . $_LANG['OFFLINE'] . '</span>';
            }
        }

        return $status;
    }

    /**
     * Проверяет, на сайте ли указанный пользователь
     * @param int $user_id
     * @return bool
     */
    public static function isOnline($user_id) {
        if ($user_id <= 0) {
            return false;
        }

        $inUser = self::getInstance();

        $online_users = array();

        if (!isset($inUser->online_users_ids)) {
            if ($inUser->online_users) {
                foreach ($inUser->online_users as $data) {
                    if ($data['user_id']) {
                        $online_users[] = $data['user_id'];
                    }
                }
            }

            $inUser->online_users_ids = $online_users;
        } else {
            $online_users = $inUser->online_users_ids;
        }

        return in_array($user_id, $online_users);
    }

    /**
     * Возвращает массив количества новых сообщений и уведомлений
     * @param int $user_id
     * @return array
     */
    public static function getNewMessages($user_id) {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $rs = $db->selectOnly('i.from_id')
                ->filterEqual('i.to_id', (int) $user_id)
                ->filterEqual('i.to_del', 0)
                ->filterEqual('i.is_new', 1)
                ->get('user_msg', false, '');

        $db->resetFilters();

        $messages = 0;
        $notices = 0;

        foreach ($rs as $o) {
            if ($o['from_id'] < 0) {
                $notices++;
            } else {
                $messages++;
            }
        }

        $counts['messages'] = $messages;
        $counts['notices'] = $notices;
        $counts['total'] = $notices + $messages;

        return $counts;
    }

    public function getNewMsg() {
        return $this->new_msg;
    }

    /**
     * Возвращает список наград пользователя
     * @param int $user_id
     * @return array
     */
    public static function getAwardsList($user_id) {
        if (!$user_id) {
            return array();
        }

        if (isset(self::$cache['users_awards'][$user_id])) {
            return self::$cache['users_awards'][$user_id];
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $aw = $db->filterEqual('i.user_id', (int) $user_id)
                ->orderBy('i.id', 'ASC')
                ->get('user_awards', false, '');

        $db->resetFilters();

        return self::$cache['users_awards'][$user_id] = $aw;
    }

    /**
     * Возвращает предустановленные награды
     * @return array
     */
    public static function getAutoAwards() {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $awards = $db->filterEqual('i.published', 1)
                ->orderBy('i.title', 'ASC')
                ->get('user_autoawards', false, '');

        $db->resetFilters();

        return $awards;
    }

    /**
     * Проверяет условия получения наград и выдает награду пользователю, если нужно
     * @param int $user_id
     * @return bool
     */
    public static function checkAwards($user_id = 0) {
        if (!$user_id) {
            return false;
        }

        $db = \cms\db::getInstance();

        $awards = self::getAutoAwards();

        if (!$awards) {
            return false;
        }

        $p_content = $db->resetFilters()->filterEqual('user_id', (int) $user_id)
                        ->filterEqual('published', 1)->getCount('content');
        $db->resetFilters();
        $p_comment = $db->filterEqual('user_id', (int) $user_id)
                        ->filterEqual('published', 1)->getCount('comments');
        $db->resetFilters();
        $p_blog = $db->filterEqual('user_id', (int) $user_id)
                        ->filterEqual('published', 1)->getCount('blog_posts');
        $db->resetFilters();
        $p_forum = \cms\controller::enabled('forum') ? $db->filterEqual('user_id', (int) $user_id)->getCount('forum_posts') : 0;
        $db->resetFilters();
        $p_photo = $db->filterEqual('user_id', (int) $user_id)
                        ->filterEqual('published', 1)->getCount('photo_files');
        $db->resetFilters();
        $p_privphoto = $db->filterEqual('user_id', (int) $user_id)->getCount('user_photos');
        $db->resetFilters();
        $p_karma = $db->filterEqual('user_id', (int) $user_id)->getFieldFiltered('user_profiles', 'karma');

        foreach ($awards as $award) {
            $db->resetFilters();

            $is_awarded = $db->filterEqual('user_id', (int) $user_id)
                    ->filterEqual('award_id', (int) $award['id'])
                    ->getCount('user_awards');

            $db->resetFilters();

            if ($is_awarded) {
                continue;
            }

            $granted = ($award['p_content'] <= $p_content) &&
                    ($award['p_comment'] <= $p_comment) &&
                    ($award['p_blog'] <= $p_blog) &&
                    ($award['p_forum'] <= $p_forum) &&
                    ($award['p_photo'] <= $p_photo) &&
                    ($award['p_privphoto'] <= $p_privphoto) &&
                    ($award['p_karma'] <= $p_karma);

            if (!$granted) {
                continue;
            }

            self::giveAward($award, $user_id);
        }

        return true;
    }

    /**
     * Выдает награду
     * @param array $award
     * @param int $user_id
     * @return int $award_id
     */
    public static function giveAward($award, $user_id) {
        if (!$award || !$user_id) {
            return false;
        }

        global $_LANG;

        $db = \cms\db::getInstance();

        $user = self::getShortUserData($user_id);

        if (!$user) {
            return false;
        }

        if (!file_exists(PATH . '/images/users/awards/' . $award['imageurl'])) {
            return false;
        }

        $award_id = $db->insert('user_awards', array(
            'user_id' => (int) $user_id,
            'pubdate' => date('Y-m-d H:i:s'),
            'title' => $award['title'],
            'description' => $award['description'],
            'imageurl' => $award['imageurl'],
            'from_id' => isset($award['from_id']) ? (int) $award['from_id'] : 0,
            'award_id' => (int) $award['id']
        ));

        if (!$award_id) {
            return false;
        }

        cmsActions::log('add_award', array(
            'object' => '"' . $award['title'] . '"',
            'user_id' => $user_id,
            'object_url' => '',
            'object_id' => $award['id'],
            'target' => '',
            'target_url' => '',
            'target_id' => 0,
            'description' => '<img src="/images/users/awards/' . $award['imageurl'] . '" border="0" alt="' . htmlspecialchars($award['description']) . '">'
        ));

        self::sendMessage(USER_UPDATER, $user_id, '<b>' . $_LANG['RECEIVED_AWARD'] . ':</b> <a href="' . cmsUser::getProfileURL($user['login']) . '#upr_awards">' . $award['title'] . '</a>');

        return \cms\events::call('users.give_award', $award_id);
    }

    /**
     * Отправляет личное сообщение пользователю
     * @param int $sender_id
     * @param int $receiver_id
     * @param string $message
     * @return bool
     */
    public static function sendMessage($sender_id, $receiver_id, $message) {
        $msg_id = \cms\db::getInstance()->insert('user_msg', array(
            'to_id' => (int) $receiver_id,
            'from_id' => (int) $sender_id,
            'senddate' => date('Y-m-d H:i:s'),
            'is_new' => 1,
            'message' => $message
        ));

        return $msg_id ? $msg_id : false;
    }

    /**
     * Отправляет личное сообщение списку пользователей
     * @param int $sender_id
     * @param array $receiver_ids
     * @param string $message
     * @return int
     */
    public static function sendMessages($sender_id, $receiver_ids, $message) {
        if (!is_array($receiver_ids) || !$receiver_ids) {
            return false;
        }

        $msg = array();

        foreach ($receiver_ids as $receiver_id) {
            $msg[] = self::sendMessage($sender_id, $receiver_id, $message);
        }

        return count($msg); // возвращаем количество отправленных сообщений
    }

    /**
     * Отправляет личное сообщение группе(ам) пользователей
     * @param int $sender_id
     * @param array or int $group
     * @param string $message
     * @return int
     */
    public static function sendMessageToGroup($sender_id, $group, $message) {
        // если отсылаем нескольким группам
        if (is_array($group)) {
            $count = 0;

            foreach ($group as $group_id) {
                $count += self::sendMessageToGroup($sender_id, $group_id, $message);
            }
        } else { // отсылаем одной группе
            // получаем участников групппы
            $user_list = self::getGroupMembers($group);

            if (!$user_list) {
                return false;
            }

            // получаем id пользователей
            $user_ids = array_keys($user_list);

            return self::sendMessages($sender_id, $user_ids, $message);
        }

        return $count;
    }

    /**
     * Проверяет подписан ли пользователь на обновления контента
     * @param int $user_id
     * @param string $target
     * @param int $target_id
     * @return bool
     */
    public static function isSubscribed($user_id, $target, $target_id) {
        if (!$user_id) {
            return false;
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $is_subscribed = $db->filterEqual('user_id', (int) $user_id)
                ->filterEqual('target', $target)
                ->filterEqual('target_id', (int) $target_id)
                ->getCount('subscribe');

        $db->resetFilters();

        return (bool) $is_subscribed;
    }

    /**
     * Добавляет/удаляет подписку пользователя на обновления контента
     * @param int $user_id
     * @param string $target
     * @param int $target_id
     * @param bool $subscribe
     * @return bool
     */
    public static function subscribe($user_id, $target, $target_id, $subscribe = true) {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        if ($subscribe) {
            $is_subscribed = $db->filterEqual('user_id', (int) $user_id)
                    ->filterEqual('target', $target)
                    ->filterEqual('target_id', (int) $target_id)
                    ->getCount('subscribe');

            $db->resetFilters();

            if (!$is_subscribed) {
                $db->insert('subscribe', array(
                    'user_id' => (int) $user_id,
                    'target' => $target,
                    'target_id' => (int) $target_id,
                    'pubdate' => date('Y-m-d H:i:s')
                ));
            }
        } else {
            $db->filterEqual('user_id', (int) $user_id)
                    ->filterEqual('target', $target)
                    ->filterEqual('target_id', (int) $target_id)
                    ->deleteFiltered('subscribe');
        }
        return true;
    }

    /**
     * Рассылает личные уведомления для подписанных на что-либо пользователей
     * @param string $target Назначение
     * @param int $target_id ID назначения
     * @param array $item Массив того, о чем уведомляем - ключи: array('link','title','author', 'letter_file');
     * @return bool
     */
    public static function sendUpdateNotify($target, $target_id, $item = array()) {
        if (!$item) {
            return false;
        }

        global $_LANG;

        $inUser = self::getInstance();
        $db = \cms\db::getInstance();

        //получаем список подписанных пользователей
        $db->resetFilters();

        $subscribers = $db->select('u.id')
                ->select('u.email')
                ->select('p.cm_subscribe', 'subscribe_type')
                ->joinInner('users', 'u', 'u.id = i.user_id')
                ->joinInner('user_profiles', 'p', 'p.user_id = u.id')
                ->filterEqual('i.target', $target)
                ->filterEqual('i.target_id', (int) $target_id)
                ->get('subscribe', false, '');

        $db->resetFilters();

        if (!$subscribers) {
            return false;
        }

        $letter = cmsCore::getLanguageTextFile($item['letter_file']);

        if (!$letter) {
            return false;
        }

        foreach ($subscribers as $user) {
            if ($user['id'] == $inUser->id) {
                continue;
            }

            if ($user['subscribe_type'] == 'priv' || $user['subscribe_type'] == 'both') {
                $message = $_LANG['WAS_UPDATED'] . ' <a href="' . $item['link'] . '">' . $item['title'] . '</a>';
                self::sendMessage(USER_UPDATER, $user['id'], $message);
            }

            if ($user['subscribe_type'] == 'mail' || $user['subscribe_type'] == 'both') {
                $user_letter = str_replace('{sitename}', cmsConfig::getConfig('sitename'), $letter);
                $user_letter = str_replace('{answerlink}', HOST . $item['link'], $user_letter);
                $user_letter = str_replace('{pagetitle}', $item['title'], $user_letter);
                $user_letter = str_replace('{date}', date('d/m/Y H:i:s'), $user_letter);
                $user_letter = str_replace('{author}', $item['author'], $user_letter);
                cmsCore::mailText($user['email'], '', $user_letter);
                unset($user_letter);
            }
        }

        return true;
    }

    /**
     * Возвращает тег ссылки на профиль пользователя с иконкой его пола
     * @param int $user_id
     * @param string $nickname
     * @param char $gender m / f
     * @param string $login
     * @param string $css_style
     * @return html
     */
    public static function getGenderLink($user_id, $nickname = '', $gender = '', $login = '', $css_style = '') {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        if (!$gender) {
            $gender = $db->filterEqual('user_id', (int) $user_id)
                    ->getFieldFiltered('user_profiles', 'gender');

            $db->resetFilters();
        }

        if (!$nickname || !$login) {
            $user = $db->getItemByField('users', 'id', (int) $user_id);

            $db->resetFilters();

            $nickname = $user['nickname'];
            $login = $user['login'];
        }

        return '<a href="' . cmsUser::getProfileURL($login) . '" class="user_gender_' . $gender . '" style="' . $css_style . '"><i class="fa"></i>' . $nickname . '</a>';
    }

    public static function getProfileURL($user_login) {
        if (!$user_login) {
            global $_LANG;
            return 'javascript:core.alert(\'' . $_LANG['USER_IS_DELETE'] . '\',\'' . $_LANG['ATTENTION'] . '\');';
        }

        return '/' . self::PROFILE_LINK_PREFIX . urlencode($user_login);
    }

    public static function getProfileLink($user_login, $user_nickname) {
        if (!$user_login) {
            global $_LANG;
            return $_LANG['USER_IS_DELETE'];
        }

        return '<a href="' . self::getProfileURL($user_login) . '" title="' . htmlspecialchars($user_nickname) . '">' . $user_nickname . '</a>';
    }

    /**
     * Запоминает текущий URI в сессии и перенаправляет пользователя на форму логина
     */
    public static function goToLogin() {
        self::sessionPut('auth_back_url', cmsCore::strClear($_SERVER['REQUEST_URI']));

        cmsCore::redirect('/login');
    }

    /**
     * Находит в строке все выражения вида {user.property} и заменяет property
     * на соответствующее свойство объекта cmsUser
     *
     * @param string $string
     *
     * @return string
     */
    public static function stringReplaceUserProperties($string, $is_title = false) {
        $matches_count = preg_match_all('/{user.([a-z0-9_]+)}/i', $string, $matches);

        if ($matches_count) {
            $user = self::getInstance();

            for ($i = 0; $i < $matches_count; $i++) {
                $tag = $matches[0][$i];
                $property = $matches[1][$i];

                if (isset($user->$property)) {
                    if (is_numeric($user->$property) && $is_title) {
                        if ($user->$property) {
                            $string = str_replace($tag, ' (' . $user->$property . ')', $string);
                        } else {
                            $string = str_replace($tag, '', $string);
                        }
                    } else {
                        $string = str_replace($tag, $user->$property, $string);
                    }
                }
            }
        }

        return $string;
    }

    /**
     * Возвращает список всех активных пользователей
     * @return array
     */
    public static function getAllUsers() {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $users = $db->filterGt('i.id', 0)
                ->filterEqual('i.is_locked', 0)
                ->filterEqual('i.is_deleted', 0)
                ->orderBy('i.id', 'ASC')
                ->get('users', false, '');

        $db->resetFilters();

        return $users;
    }

    /**
     * Возвращает количество всех активных пользователей
     * @return int
     */
    public static function getCountAllUsers() {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $count = $db->filterGt('i.id', 0)
                ->filterEqual('i.is_locked', 0)
                ->filterEqual('i.is_deleted', 0)
                ->getCount('users');

        $db->resetFilters();

        return $count;
    }

    /**
     * Возвращает данные из cms_users пользователя по логину, email или id
     * @param str $id
     * @return array
     */
    public static function getShortUserData($id = '') {
        if (!$id) {
            return false;
        }

        $inUser = self::getInstance();

        // для текущего юзера смысла данные из базы брать нет
        if ($inUser->id && in_array($id, array($inUser->login, $inUser->id))) {
            return get_object_vars($inUser);
        }

        if (isset(self::$cache['get_short_user'][$id])) {
            return self::$cache['get_short_user'][$id];
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        if (is_numeric($id)) {
            $db->filterEqual('id', (int) $id);
        } else {
            if (preg_match("/^(?:[a-z0-9\._\-]+)@(?:[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?\.)+(?:[a-z]{2,6})$/ui", $id)) {
                $db->filterEqual('email', $id);
            } else {
                $db->filterEqual('login', $id);
            }
        }

        $user = $db->orderBy('id', 'DESC')->getItem('users');

        $db->resetFilters();

        return self::$cache['get_short_user'][$id] = $user;
    }

    /**
     * Возвращает массив id групп администраторов
     * @return array
     */
    public static function getAdminGroups() {
        $db = \cms\db::getInstance();

        $groups = array();

        $db->resetFilters();

        $rs = $db->selectOnly('i.id')->filterEqual('i.is_admin', 1)->get('user_groups', false, '');

        $db->resetFilters();

        if ($rs) {
            foreach ($rs as $group) {
                $groups[] = $group['id'];
            }
        }

        return $groups;
    }

    /**
     * Возвращает id группы пользователя
     * @param int $user_id
     * @return int
     */
    public static function getGroupIdByUserId($user_id) {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $group_id = $db->filterEqual('id', (int) $user_id)->getFieldFiltered('users', 'group_id');

        $db->resetFilters();

        return $group_id;
    }

    /**
     * Возвращает название группы пользователей
     * @param int $group_id
     * @return str
     */
    public static function getGroupTitle($group_id) {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $title = $db->filterEqual('id', (int) $group_id)->getFieldFiltered('user_groups', 'title');

        $db->resetFilters();

        return $title;
    }

    /**
     * Возвращает список групп пользователей
     * @param bool $no_guests Если TRUE, группа "Гости" не выводится
     * @return array
     */
    public static function getGroups($no_guests = false) {
        $db = \cms\db::getInstance();

        $groups = array();

        $db->resetFilters();

        $db->select('i.id')
                ->select('i.title')
                ->select('i.alias')
                ->select('i.is_admin')
                ->select('i.access');

        if ($no_guests) {
            $db->filterNotEqual('i.alias', 'guest');
        }

        $db->orderBy('i.is_admin', 'ASC');

        $rs = $db->get('user_groups', false, '');

        $db->resetFilters();

        return $rs ? $rs : $groups;
    }

    /**
     * Возвращает пользователей в указанной группе
     * @param int $group_id
     * @return array
     */
    public static function getGroupMembers($group_id) {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $users = $db->select('i.id')
                ->select('i.nickname')
                ->select('i.login')
                ->filterEqual('i.group_id', (int) $group_id)
                ->filterEqual('i.is_deleted', 0)
                ->get('users');

        $db->resetFilters();

        return $users;
    }

    /**
     * Удаляет тип доступа группы
     * @param str $access_type
     * @return bool
     */
    public static function deleteGroupAccessType($access_type = '') {
        return \cms\db::getInstance()
                        ->filterEqual('access_type', $access_type)
                        ->deleteFiltered('user_groups_access');
    }

    /**
     * Добавляет тип доступа группы
     * @param str $access_type
     * @param str $access_name
     * @return bool
     */
    public static function registerGroupAccessType($access_type = '', $access_name = '', $hide_for_guest = 0) {
        if (!$access_type || !$access_name) {
            return false;
        }

        return \cms\db::getInstance()->insert('user_groups_access', array(
                    'access_type' => $access_type,
                    'access_name' => $access_name,
                    'hide_for_guest' => (int) $hide_for_guest
                        ), false, 'json', true);
    }

    /**
     * Авторизует пользователя
     * возвращает url для редиректа
     * @param str $login
     * @param str $passw
     * @param int $remember_pass
     * @return srt $back_url
     */
    public function signInUser($login = '', $passw = '', $remember_pass = 1, $pass_in_md5 = 0) {
        if ($this->id) {
            return cmsCore::getBackURL();
        }

        $default_back_url = '/auth/error.html';

        if (!$login || !$passw) {
            return $default_back_url;
        }

        $db = \cms\db::getInstance();
        $inCore = cmsCore::getInstance();

        // Авторизация по логину или e-mail
        if (!preg_match("/^([a-z0-9\._-]+)@([a-z0-9\._-]+)\.([a-z]{2,4})$/ui", $login)) {
            $where_login = "i.login = '" . $login . "'";
        } else {
            $where_login = "i.email = '" . $login . "'";
        }

        $where_pass = $pass_in_md5 ? "i.password = '" . $passw . "'" : "i.password = md5('" . $passw . "')";

        // Проверяем локальную пару логин + пароль
        $user = $this->loadUser(0, $where_login . " AND " . $where_pass);

        // иначе пытаемся авторизоваться через плагины
        if (!$user) {
            $user = \cms\events::call('users.signin', array('login' => $login, 'pass' => $passw));
        }

        if (!$user) {
            return $default_back_url;
        }

        $_SESSION['user'] = $user;

        \cms\events::call('users.login', $_SESSION['user']);

        if ($remember_pass) {
            $cookie_code = md5($user['id'] . $user['password'] . PATH);
            cmsCore::setCookie('userid', $cookie_code, time() + 2592000);
        }

        // Флаг первой авторизации
        $first_time_auth = !$user['is_logged_once'];

        // обновляем дату последнего визита, ip
        self::setUserLogdate($user['id']);

        $db->update('users', (int) $user['id'], array(
            'last_ip' => $this->ip,
            'is_logged_once' => 1
        ));

        // помечаем, что пользователь онлайн
        $db->filterEqual('sess_id', session_id())->updateFiltered('online', array(
            'user_id' => (int) $user['id']
        ));

        //////////////  юзер уже авторизован //////////////////////////
        // Формируем url редиректа после авторизации
        // Получаем настройки что делать после авторизации
        $cfg = $inCore->loadComponentConfig('registration');

        // Получаем URL, предыдущий перед формой логина
        $auth_back_url = cmsUser::sessionGet('auth_back_url');
        $auth_back_url = $auth_back_url ? $auth_back_url : cmsCore::getBackURL();

        if (strpos($auth_back_url, $_SERVER['HTTP_HOST']) === false || strpos($auth_back_url, '/auth/') !== false) {
            $auth_back_url = '/';
        }

        cmsUser::sessionDel('auth_back_url');

        // Авторизация в админку
        if ($_SESSION['user']['is_admin'] && cmsCore::inRequest('is_admin')) {
            return '/admin/';
        }

        // Остальные пользователи
        if ($_SESSION['user']['id']) {
            if ($first_time_auth) {
                $cfg['auth_redirect'] = $cfg['first_auth_redirect'];
            }

            switch ($cfg['auth_redirect']) {
                case 'none': $url = $auth_back_url;
                    break;
                case 'index': $url = '/';
                    break;
                case 'profile': $url = cmsUser::getProfileURL($user['login']);
                    break;
                case 'editprofile': $url = '/users/' . $user['id'] . '/editprofile.html';
                    break;
                default : $url = $default_back_url;
            }

            return $url;
        }

        return $default_back_url;
    }

    /**
     * Разлогинивает пользователя
     * @return bool
     */
    public function logout() {
        $db = \cms\db::getInstance();

        cmsCore::unsetCookie('userid');

        $sess_id = session_id();

        \cms\events::call('users.logout', $this->id);

        self::setUserLogdate($this->id);

        $db->filterEqual('user_id', (int) $this->id)->deleteFiltered('online');
        $db->filterEqual('session_id', $sess_id)->deleteFiltered('search');

        session_destroy();
    }

    /**
     * Проверяет, является ли пользователь администратором
     * @param int $userid
     * @return bool
     */
    public static function userIsAdmin($user_id) {
        if (!$user_id) {
            return false;
        }

        $inUser = self::getInstance();

        if ($user_id == $inUser->id) {
            return $inUser->is_admin;
        } else {
            if (isset(self::$cache['is_admin'][$user_id])) {
                return self::$cache['is_admin'][$user_id];
            }

            $db = \cms\db::getInstance();

            $db->resetFilters();

            $is_admin = $db->joinInner('user_groups', 'g', 'g.id = i.group_id')
                    ->filterEqual('i.id', (int) $user_id)
                    ->getFieldFiltered('users', 'g.is_admin');

            $db->resetFilters();

            return self::$cache['is_admin'][$user_id] = $is_admin;
        }
    }

    /**
     * Проверяет, является ли пользователь редактором статей
     * Возвращает массив id категорий, где пользователь редактор
     * @param int $userid
     * @return array
     */
    public static function userIsEditor($userid = 0) {
        $inUser = self::getInstance();

        // если проверяем текущего пользователя
        if (!$userid || $userid == $inUser->id) {
            if (!$inUser->id) {
                return false;
            }

            $group_id = $inUser->group_id;
        } elseif ($userid) {
            $group_id = self::getGroupIdByUserId($userid);
        }

        if (!@$group_id) {
            return false;
        }

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $cat = $db->selectOnly('i.id')
                ->filterEqual('i.modgrp_id', (int) $group_id)
                ->get('category', false, '');

        $db->resetFilters();

        return $cat ? $cat : false;
    }

    /**
     * Возвращает массив с администраторскими правами доступа текущего пользователя
     * @return array
     */
    public static function getAdminAccess() {
        $inUser = self::getInstance();

        if ($inUser->id) {
            return $inUser->access;
        }

        $db = \cms\db::getInstance();

        $access = $db->getField("user_groups", $inUser->group_id, 'access');

        if ($access) {
            $access = str_replace(', ', ',', $access);
            $access = explode(',', $access);
            return $access;
        }

        return false;
    }

    /**
     * Проверяет что администратор имеет право на указанное действие
     * $access_type like "admin/modules" or "admin/users"
     * @param string $access_type
     * @param array $access_list
     * @return bool
     */
    public static function isAdminCan($access_type, $access_list) {
        $inUser = self::getInstance();

        if (!$inUser->is_admin) {
            return false;
        }

        if ($inUser->id == 1) {
            return true;
        }

        if (in_array($access_type, $access_list)) {
            return true;
        }

        return false;
    }

    /**
     * Проверяет что пользователь имеет право на указанное действие
     * $access_type like "comments/delete" or "photo/edit"
     * @param string $access_type
     * @return bool
     */
    public static function isUserCan($access_type) {
        $inUser = self::getInstance();

        if ($inUser->is_admin) {
            return true;
        }

        return in_array($access_type, $inUser->access);
    }

    public static function getIp() {
        if (self::$_ip === null) {
            $config = cmsConfig::getInstance();

            self::$_ip = isset($_SERVER[$config->detect_ip_key]) ? $_SERVER[$config->detect_ip_key] : '127.0.0.1';

            if (!filter_var(self::$_ip, FILTER_VALIDATE_IP)) {
                self::$_ip = '127.0.0.1';
            }
        }

        return self::$_ip;
    }

    // ============================= DEPRECATED ==============================//
    // Методы не желательные к использованию в новых компонентах, плагинах и модулях
    // но оставленные для совместимости со старыми версиями

    public static function setMaximumTokensCount($num) {
        return;
    }

    public static function setCsrfTokensName($name) {
        return;
    }

    /**
     * @see \cms\csrf_token::get()
     */
    public static function getCsrfToken() {
        return \cms\csrf_token::get();
    }

    /**
     * @see \cms\csrf_token::check()
     */
    public static function checkCsrfToken() {
        return \cms\csrf_token::check(\cms\request::getInstance()->get('csrf_token', 'string', '', 'post'));
    }

    public static function clearCsrfToken() {
        return true;
    }

    /**
     * @see \cms\session::set()
     */
    public static function sessionPut($param, $value, $box = \cms\session::NAME_SPACE) {
        \cms\session::set($param, $value, $box);

        return $value;
    }

    /**
     * @see \cms\session::get()
     */
    public static function sessionGet($param, $box = \cms\session::NAME_SPACE) {
        $value = \cms\session::get($param, $box);

        // Совместимость с InstantCMS 1.10.x: старые данные лежали в $_SESSION['icms']
        if ($value === null && $box === \cms\session::NAME_SPACE && !empty($_SESSION['icms'])) {
            $value = \cms\session::get($param, 'icms');
        }

        return $value;
    }

    /**
     * @see \cms\session::delete()
     */
    public static function sessionDel($param, $box = \cms\session::NAME_SPACE) {
        \cms\session::delete($param, $box);
    }

    /**
     * @see \cms\session::clear()
     */
    public static function sessionClearAll($box = \cms\session::NAME_SPACE) {
        \cms\session::clear($box);
    }
}