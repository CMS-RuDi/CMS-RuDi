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

class cmsActions {
    private static $instance;
    private static $defaultLogArray = array(
        'pubdate' => '',
        'user_id' => '',
        'object' => '',
        'object_url' => '',
        'object_id' => '',
        'target' => '',
        'target_url' => '',
        'target_id' => '',
        'description' => '',
        'is_friends_only' => '',
        'is_users_only' => ''
    );
    private $show_targets = true;
    private $only_friends = false;

    private function __construct() {
        $this->db = \cms\db::getInstance();
    }

    private function __clone() {

    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function checkLogArrayValues($input_array = array()) {
        if (!$input_array || !is_array($input_array)) {
            return array();
        }

        // убираем ненужные ячейки массива
        foreach ($input_array as $k => $v) {
            if (!isset(self::$defaultLogArray[$k])) {
                unset($input_array[$k]);
                continue;
            }

            $input_array[$k] = preg_replace('/\[hide(.*?)\](.*?)\[\/hide\]/sui', '', $input_array[$k]);
            $input_array[$k] = preg_replace('/\[hide(.*?)\](.*?)$/sui', '', $input_array[$k]);
            $input_array[$k] = str_replace(array('\r', '\n'), ' ', $input_array[$k]);
        }

        return $input_array;
    }

    /**
     * Регистрирует новый тип действия
     * @param str $component
     * @param array $action (name, title, message)
     * @return bool
     */
    public static function registerAction($component, $action) {
        if (self::getAction($action['name'], false)) {
            return true;
        }

        $action['is_tracked'] = 1;
        $action['is_visible'] = 1;
        $action['component'] = $component;

        \cms\db::getInstance()->insert('actions', $action);

        return true;
    }

    /**
     * Находит описание действия по его названию
     * @param str $action_name
     * @param bool $only_tracked
     * @return array | false
     */
    public static function getAction($action_name, $only_tracked = true) {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $db->filterEqual('i.name', $action_name);

        if ($only_tracked) {
            $db->filterEqual('i.is_tracked', 1);
        }

        $action = $db->getItem('actions');

        $db->resetFilters();

        return is_array($action) ? \cms\events::call('actions.get_action', $action) : false;
    }

    /**
     * Добавляет действие в ленту активности
     * @param str $action_name
     * @param array $params (object, object_url, target, target_url, description)
     * @return bool
     */
    public static function log($action_name, $params) {
        $params = self::checkLogArrayValues($params);

        if (!$params) {
            return false;
        }

        $inUser = cmsUser::getInstance();

        if (!$inUser->id && $action_name != 'add_user') {
            return false;
        }

        $action = self::getAction($action_name);
        if (!$action) {
            return false;
        }

        $params['user_id'] = !empty($params['user_id']) ? $params['user_id'] : $inUser->id;
        $params['action_id'] = $action['id'];
        $params['pubdate'] = date("Y-m-d H:i:s");

        \cms\db::getInstance()->insert('actions_log', \cms\events::call('actions.log_action', $params));

        return true;
    }

    /**
     * Удаляет из ленты активности все события определенного типа для указанного объекта
     * @param string $action_name Тип события
     * @param int $object_id Идентификатор объекта
     * @return bool
     */
    public static function removeObjectLog($action_name, $object_id, $user_id = false) {
        $arg = func_get_args();

        \cms\events::call('actions.delete_object_log', $arg);

        $action = self::getAction($action_name);

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $db->filterEqual('action_id', (int) $action['id'])
                ->filterEqual('object_id', (int) $object_id);

        if ($user_id) {
            $db->filterEqual('user_id', (int) $user_id);
        }

        $db->deleteFiltered('actions_log');

        return true;
    }

    /**
     * Удаляет из ленты активности все события определенного типа для указанной цели
     * @param string $action_name Тип события
     * @param int $target_id Идентификатор цели
     * @return bool
     */
    public static function removeTargetLog($action_name, $target_id, $user_id = false) {
        $arg = func_get_args();

        \cms\events::call('actions.delete_target_log', $arg);

        $action = self::getAction($action_name);

        $db = \cms\db::getInstance();

        $db->resetFilters();

        $db->filterEqual('action_id', (int) $action['id'])
                ->filterEqual('target_id', (int) $target_id);

        if ($user_id) {
            $db->filterEqual('user_id', (int) $user_id);
        }

        $db->deleteFiltered('actions_log');

        return true;
    }

    public static function removeLogById($id) {
        \cms\events::call('actions.delete_log', $id);

        return \cms\db::getInstance()->delete('actions_log', (int) $id);
    }

    /**
     * Обновляет запись ленты
     * @return bool
     */
    public static function updateLog($action_name, $params, $object_id = 0, $target_id = 0) {
        $db = \cms\db::getInstance();

        $params = self::checkLogArrayValues($params);

        if (!$params) {
            return false;
        }

        if (!$object_id && !$target_id) {
            return false;
        }

        $arg = func_get_args();

        \cms\events::call('actions.update_log', $arg);

        // Получаем id записи
        $action = self::getAction($action_name);

        if (!$action) {
            return false;
        }

        // если обновляем сам объект
        if ($object_id) {
            $db->resetFilters();

            $db->filterEqual('action_id', (int) $action['id'])
                    ->filterEqual('object_id', (int) $object_id)
                    ->updateFiltered('actions_log', $params);
        }

        // если обновляем все место назначения
        if ($target_id) {
            $db->resetFilters();

            $db->filterEqual('action_id', (int) $action['id'])
                    ->filterEqual('target_id', (int) $target_id)
                    ->updateFiltered('actions_log', $params);
        }

        return true;
    }

    /**
     * Управляет показом категорий, в которых находятся объекты вызвавшие события
     * @param bool $show Показывать категории?
     *
     */
    public function showTargets($show) {
        $this->show_targets = $show;
        return;
    }

    /**
     * Включает режим показа только событий друзей
     *
     */
    public function onlyMyFriends() {
        $inUser = cmsUser::getInstance();

        $friends = cmsUser::getFriends($inUser->id);

        if (!$friends) {
            $this->db->filter('1=0');
            return;
        }

        $f_list = array();

        foreach ($friends as $friend) {
            $f_list[] = $friend['id'];
        }

        if ($f_list) {
            $this->db->filterIn('i.user_id', $f_list);
        } else {
            $this->db->filter('1=0');
        }

        $this->only_friends = true;

        return;
    }

    /**
     * Показывает события определенного юзера
     *
     */
    public function whereUserIs($user_id) {
        if ($user_id) {
            $this->db->filterEqual('i.user_id', $user_id);
        } else {
            $this->db->filter('1=0');
        }

        return;
    }

    public function onlySelectedTypes($types) {
        if (!is_array($types)) {
            $this->db->filter('1=0');
            return;
        }

        $t_list = array();

        foreach ($types as $type) {
            $t_list[] = $type;
        }

        $this->db->filterIn('a.id', $t_list);

        return;
    }

    /**
     * Возвращает количество записей по условиям
     * @return int
     */
    public function getCountActions() {
        $inUser = cmsUser::getInstance();

        if (!$this->only_friends) {
            $this->db->filterEqual('i.is_friends_only', 0);
        }

        if (!$inUser->id) {
            $this->db->filterEqual('i.is_users_only', 0);
        }

        $this->db->joinLeft('actions', 'a', 'a.id = i.action_id AND a.is_visible = 1');

        $count = $this->db->getCount('actions_log', 'id');

        return $count;
    }

    /**
     * Возвращает массив событий для ленты активности
     * @return array
     */
    public function getActionsLog($filters = true) {
        $inUser = cmsUser::getInstance();

        if ($filters) {
            if (!$this->only_friends) {
                $this->db->filterEqual('i.is_friends_only', 0);
            }

            if (!$inUser->id) {
                $this->db->filterEqual('i.is_users_only', 0);
            }

            $this->db->joinLeft('actions', 'a', 'a.id = i.action_id AND a.is_visible = 1');
        }

        $pactions = \cms\events::call('actions.get_before_actions', false);
        if ($pactions !== false) {
            return $pactions;
        }

        $this->db->select('i.*')
                ->select('i.pubdate', 'orig_pubdate')
                ->select('a.message')
                ->select('a.name')
                ->select('u.nickname', 'user_nickname')
                ->select('u.login', 'user_login')
                ->joinLeft('users', 'u', 'u.id = i.user_id')
                ->orderBy('i.id', 'DESC');

        $actions = $this->db->get('actions_log', false, '');

        if (!$actions) {
            return false;
        }

        global $_LANG;

        $last_date = '';
        $today_date = date('j F Y');
        $yesterday_date = date('j F Y', time() - 3600 * 24);

        foreach ($actions as $key => $action) {
            $action['item_date'] = '';
            $item_date = date('j F Y', strtotime($action['orig_pubdate']));

            if ($item_date != $last_date) {
                switch ($item_date) {
                    case $today_date: $date = icms_ucfirst($_LANG['TODAY']);
                        break;
                    case $yesterday_date: $date = icms_ucfirst($_LANG['YESTERDAY']);
                        break;
                    default: $date = cmsCore::dateFormat($item_date, true, false, false);
                }

                $action['item_date'] = $date;
                $last_date = $item_date;
            }

            $action['object_link'] = $action['target_link'] = '';

            if ($action['object']) {
                $action['object_link'] = $action['object_url'] ? '<a href="' . $action['object_url'] . '" class="act_obj_' . $action['name'] . '">' . $action['object'] . '</a>' : $action['object'];
            }

            if ($action['target']) {
                $action['target_link'] = '<a href="' . $action['target_url'] . '" class="act_tgt_' . $action['name'] . '">' . $action['target'] . '</a>';
            }

            if ($action['message']) {
                $target_pos = mb_strpos($action['message'], '|');

                if ($target_pos !== false) {
                    if (!$this->show_targets || !$action['target']) {
                        $action['message'] = mb_substr($action['message'], 0, $target_pos);
                    } else {
                        $action['message'] = str_replace('|', '', $action['message']);
                    }
                }

                $action['message'] = sprintf($action['message'], $action['object_link'], $action['target_link']);
            }

            $action['is_new'] = (bool) (strtotime($action['pubdate']) > strtotime($inUser->logdate));
            $action['user_url'] = cmsUser::getProfileURL($action['user_login']);
            $action['pubdate'] = cmsCore::dateDiffNow($action['pubdate']);

            $actions[$key] = $action;
        }

        return \cms\events::call('actions.get_actions', $actions);
    }

    /**
     * Удаляет старые записи ленты
     * @param int $pubdays
     * @return bool
     */
    public static function removeOldLog($pubdays = 60) {
        return \cms\db::getInstance()
                        ->filter("DATEDIFF(NOW(), i.pubdate) > " . (int) $pubdays)
                        ->deleteFiltered('actions_log');
    }

    /**
     * Удаляет из ленты записи одного пользователя
     * @param int $user_id
     * @return bool
     */
    public static function removeUserLog($user_id) {
        if (!$user_id) {
            return false;
        }

        return \cms\db::getInstance()
                        ->filterEqual('user_id', (int) $user_id)
                        ->deleteFiltered('actions_log');
    }

    /**
     * Получает массив компонентов, зарегистрированных в ленте активности
     * @return array
     */
    public static function getActionsComponents() {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        return $db->select('MIN(i.title)', 'title')
                        ->select('i.link')
                        ->joinInner('actions', 'act', 'act.component = i.link')
                        ->filterEqual('i.internal', 0)
                        ->filterEqual('i.published', 1)
                        ->groupBy('i.link')
                        ->get('components', false, '');
    }
}