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

class cmsDatabase {

    use \Singeltone;

    /**
     * @deprecated
     * @var string
     */
    public $join = '';

    /**
     * @deprecated
     * @var string
     */
    public $select = '';

    /**
     * @deprecated
     * @var string
     */
    public $where = '';

    /**
     * @deprecated
     * @var string
     */
    public $group_by = '';

    /**
     * @deprecated
     * @var string
     */
    public $order_by = '';

    /**
     * @deprecated
     * @var int
     */
    public $limit = '1000';

    /**
     * @deprecated
     * @var int
     */
    public $page = 1;

    /**
     * @deprecated
     * @var int
     */
    public $perpage = 10;

    /**
     * @deprecated
     * @var array
     */
    private $cache = []; // кеш некоторых запросов

    /**
     * @deprecated
     * @var string
     */
    public $prefix;

    /**
     * @deprecated
     * @var array
     */
    public $q_dump = [];

    /**
     * Класс обработки запросов к БД
     * @var \cms\db
     */
    private $db;

    protected function __construct() {
        $this->db = \cms\db::getInstance();
        $this->prefix = $this->db->prefix;
    }

    /**
     * Прозрачно отдаёт сторонним компонентам методы нового cms\db,
     * которых ещё нет в историческом cmsDatabase.
     */
    public function __call($name, $arguments) {
        if (is_callable([$this->db, $name])) {
            return call_user_func_array([$this->db, $name], $arguments);
        }

        trigger_error('Call to undefined method cmsDatabase::' . $name . '()', E_USER_ERROR);
    }

    /**
     * Реинициализирует соединение с базой
     */
    public static function reinitializedConnection() {
        return self::getInstance()->reconnect();
    }

    public function reconnect($is_force = false) {
        $result = $this->db->reconnect($is_force);
        $this->prefix = $this->db->prefix;

        return $result;
    }

    public function ready() {
        return $this->db->ready();
    }

    public function connectError() {
        return $this->db->connectError();
    }

    public function connectErrno() {
        return $this->db->connectErrno();
    }

    public function createDatabase($db_name) {
        return $this->db->createDatabase($db_name);
    }

    /**
     * Старое название reconnect() из InstantCMS 1.10.x.
     *
     * @return mixed
     */
    public function initConnection() {
        return $this->reconnect();
    }

    /**
     * Сбрасывает условия
     */
    public function resetConditions() {
        $this->join = '';
        $this->select = '';
        $this->where = '';
        $this->group_by = '';
        $this->order_by = '';
        $this->limit = '1000';

        $this->db->resetFilters();

        return $this;
    }

    public function addJoin($join) {
        $this->join .= $join . PHP_EOL;

        return $this;
    }

    public function addSelect($condition) {
        $this->select .= ', ' . $condition;

        return $this;
    }

    public function where($condition) {
        $this->where .= ' AND (' . $condition . ')' . PHP_EOL;

        return $this;
    }

    public function groupBy($field) {
        $this->group_by = 'GROUP BY ' . $field;

        return $this;
    }

    public function orderBy($field, $direction = 'ASC') {
        $this->order_by = 'ORDER BY ' . $field . ' ' . $direction;

        return $this;
    }

    public function limit($howmany) {
        $this->limit = (int) $howmany;

        return $this;
    }

    public function limitIs($from, $howmany = '') {
        $this->limit = (int) $from . ', ' . (int) $howmany;

        return $this;
    }

    public function limitPage($page, $perpage) {
        $page = (int) $page;
        $perpage = (int) $perpage;

        if ($perpage <= 0) {
            $perpage = 10;
        }

        $this->limit = (($page - 1) * $perpage) . ', ' . $perpage;

        return $this;
    }

    protected function replacePrefix($sql, $prefix = 'cms_') {
        return $this->db->replacePrefix($sql);
    }

    /**
     * @deprecated
     * @var string
     */
    protected static function replacePrefixTable($table) {
        $part = explode(' ', trim($table));

        foreach ($part as $key => $value) {
            if ($key == 0) {
                $part[$key] = str_replace('cms_', '', $value);
            } else {
                $part[$key] = str_replace('cms_', '{#}', $value);
            }
        }

        return implode(' ', $part);
    }

    public function query($sql, $ignore_errors = false, $replace_prefix = true) {
        return $this->db->query($sql, false, !(\cmsConfig::getInstance()->debug && !$ignore_errors));
    }

    public function num_rows($result) {
        return $result ? $result->rowsCount() : 0;
    }

    public function fetch_assoc($result) {
        return $result ? $result->fetchAssoc() : false;
    }

    public function fetch_row($result) {
        return $result ? $result->fetchRow() : false;
    }

    public function free_result($result) {
        unset($result);
    }

    public function fetch_all($result) {
        return $result ? $result->fetchAll() : [];
    }

    public function affected_rows() {
        return $this->db->affectedRows();
    }

    public function get_last_id($table = '') {
        if (!$table) {
            return $this->db->lastId();
        }

        $table = '`' . str_replace('`', '``', $table) . '`';

        $result = $this->db->query('SELECT MAX(id) as lastid FROM ' . $table);

        if ($result && $result->rowsCount()) {
            $data = $result->fetchAssoc();
            return isset($data['lastid']) ? (int) $data['lastid'] : 0;
        }

        return 0;
    }

    public function rows_count($table, $where, $limit = false) {
        if ($limit) {
            $this->db->limit(0, $limit);
        }
        if (!empty($where)) {
            $this->db->filterStart()->filter($where)->filterEnd();
        }
        return $this->db->getCount($table);
    }

    public function get_field($table, $where = '1', $field = 'id') {
        $row = $this->db->getRow(self::replacePrefixTable($table), $where, $field);

        if (empty($row)) {
            return false;
        }

        return $row[$field];
    }

    public function get_fields($table, $where, $fields = '*', $order = 'id ASC') {
        return $this->db->getRow(self::replacePrefixTable($table), $where, $fields, $order);
    }

    public function get_table($table, $where = '', $fields = '*') {
        return $this->db->getRows(self::replacePrefixTable($table), $where, $fields, 'id ASC', true);
    }

    public function errno() {
        return $this->db->errno();
    }

    public function error() {
        return $this->db->error();
    }

    public function escape_string($value) {
        if (is_array($value)) {
            foreach ($value as $key => $string) {
                // Ключи — это имена полей, их нельзя менять экранированием.
                $value[$key] = $this->escape_string($string);
            }

            return $value;
        }

        return $this->db->escape(stripcslashes($value));
    }

    public function isFieldExists($table, $field) {
        return $this->db->isFieldExists(str_replace('cms_', '', $table), $field);
    }

    public function isFieldType($table, $field, $type) {
        return $this->db->isFieldType(self::replacePrefixTable($table), $field, $type);
    }

    public function isTableExists($table) {
        return $this->db->isTableExists(str_replace('cms_', '', $table));
    }

    public static function optimizeTables($tlist = '') {
        $inDB = self::getInstance();

        if (is_array($tlist)) {
            foreach ($tlist as $tname) {
                $inDB->query('OPTIMIZE TABLE ' . $tname, true);
                $inDB->query('ANALYZE TABLE ' . $tname, true);
            }
        } else if ($inDB->isTableExists('information_schema.tables')) {
            $base = cmsConfig::getConfig('db_base');

            $tlist = $inDB->get_table('information_schema.tables', "table_schema = '" . $base . "'", 'table_name');

            if (!is_array($tlist)) {
                return false;
            }

            foreach ($tlist as $tname) {
                $inDB->query('OPTIMIZE TABLE ' . $tname['table_name'], true);
                $inDB->query('ANALYZE TABLE ' . $tname['table_name'], true);
            }
        }

        if ($inDB->errno()) {
            return false;
        }

        return true;
    }

    /**
     * Добавляет массив записей в таблицу.
     * Все значения готовит cms\db::prepareValue(), запрос строит DSSoft\DB.
     */
    public function insert($table, $insert_array, $ignore = false) {
        return $this->db->insert(
            self::replacePrefixTable($table),
            $this->stripSlashesRecursive($insert_array),
            false,
            'yaml',
            $ignore
        );
    }

    /**
     * Обновляет данные в таблице.
     *
     * Исторический порядок CMS: update($table, $data, $id|where).
     */
    public function update($table, $update_array, $id) {
        // Поддержка обеих сигнатур:
        // cmsDatabase: update($table, $data, $id|where)
        // cms\db:      update($table, $where, $data)
        if (is_string($update_array) && is_array($id)) {
            $tmp = $update_array;
            $update_array = $id;
            $id = $tmp;
        }

        if (!is_array($update_array)) {
            return false;
        }

        if (isset($update_array['id'])) {
            unset($update_array['id']);
        }

        $table = self::replacePrefixTable($table);
        $data = $this->stripSlashesRecursive($update_array);

        if (is_numeric($id)) {
            return $this->db->update($table, (int) $id, $data, false, 'yaml');
        }

        return $this->db->_update($table, $id, $data, false, 'yaml');
    }

    /**
     * Исторические компоненты экранировали значения через escape_string(),
     * после чего insert/update экранировали их повторно. Перед передачей
     * в DSSoft снимаем старые слеши.
     */
    private function stripSlashesRecursive($value) {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->stripSlashesRecursive($item);
            }

            return $value;
        }

        if (is_string($value)) {
            return stripslashes($value);
        }

        return $value;
    }

    /**
     * @deprecated Оставлено для сторонних компонентов InstantCMS 1.x.
     */
    public function removeTheMissingCell($table, $array) {
        if (!is_array($array)) {
            return [];
        }

        $fields = $this->db->getTableFields(self::replacePrefixTable($table));

        foreach ($array as $k => $v) {
            if (!in_array($k, $fields, true)) {
                unset($array[$k]);
            }
        }

        return $array;
    }

    public function delete($table, $where = '', $limit = false) {
        return $this->db->delete(self::replacePrefixTable($table), $where, $limit);
    }

    public function setFlag($table, $id, $flag, $value) {
        $this->db->changeValue(self::replacePrefixTable($table), $flag, $value, 'id=' . $id);
        return $this;
    }

    public function setFlags($table, $items, $flag, $value) {
        $ids = [];

        foreach ($items as $id) {
            $id = (int) $id;

            if (!empty($id)) {
                $ids[] = $id;
            }
        }

        if ($ids) {
            $this->db->changeValue(self::replacePrefixTable($table), $flag, $value, 'id IN (' . implode(',', $ids) . ')', count($ids));
        }

        return $this;
    }

    public function deleteNS($table, $id, $differ = '') {
        return cmsCore::getInstance()->nestedSetsInit($table)->DeleteNode($id, $differ);
    }

    public function getNsRootCatId($table, $differ = '') {
        if (isset($this->cache[$table][$differ])) {
            return $this->cache[$table][$differ];
        }

        $root_cat = $this->getNsCategory($table, 0, $differ);

        return $root_cat ? ($this->cache[$table][$differ] = $root_cat['id']) : false;
    }

    public function getNsCategory($table, $cat_id_or_link = 0, $differ = '') {
        if (isset($this->cache[$table][$cat_id_or_link][$differ])) {
            return $this->cache[$table][$cat_id_or_link][$differ];
        }

        if (!$cat_id_or_link) {
            $where = 'NSLevel = 0';
        } else {
            if (is_numeric($cat_id_or_link)) { // если пришла цифра, считаем ее cat_id
                $where = "id = '" . $cat_id_or_link . "'";
            } else {
                $where = "seolink = '" . $cat_id_or_link . "'";
            }
        }

        if (isset($differ)) {
            $where .= " AND NSDiffer = '" . $differ . "'";
        }

        $cat = $this->get_fields($table, $where, '*');

        return $cat ? $this->cache[$table][$cat_id_or_link][$differ] = $cat : false;
    }

    public function moveNsCategory($table, $cat_id, $dir = 'up') {
        $ns = cmsCore::getInstance()->nestedSetsInit($table);

        if ($dir == 'up') {
            $ns->MoveOrdering($cat_id, -1);
        } else {
            $ns->MoveOrdering($cat_id, 1);
        }

        return true;
    }

    public function addNsCategory($table, $cat, $differ = '') {
        $cat_id = cmsCore::getInstance()->nestedSetsInit($table)->AddNode($cat['parent_id'], -1, $differ);

        if (!$cat_id) {
            return false;
        }

        $this->update($table, $cat, $cat_id);

        return $cat_id;
    }

    public function addRootNsCategory($table, $differ = '', $cat = array()) {
        $cat_id = cmsCore::getInstance()->nestedSetsInit($table)->AddRootNode($differ);

        if (!$cat_id) {
            return false;
        }

        $this->update($table, $cat, $cat_id);

        return $cat_id;
    }

    public function getNsCategoryPath($table, $left_key, $right_key, $fields = '*', $differ = '', $only_nested = false) {
        $nested_sql = $only_nested ? '' : '=';

        $path = $this->get_table($table, 'NSLeft <' . $nested_sql . ' ' . $left_key . ' AND NSRight >' . $nested_sql . ' ' . $right_key . " AND parent_id > 0 AND NSDiffer = '" . $differ . "' ORDER BY NSLeft", $fields);

        return $path;
    }

    /**
     * Обновляет ссылку на категорию и вложенные в нее
     * Подразумевается, что заголовок категории или поле url изменен заранее
     * @return bool
     */
    public function updateNsCategorySeoLink($table, $cat_id, $is_url_cyrillic = false) {
        // получаем изменяемую категорию
        $cat = $this->getNsCategory($table, $cat_id);

        if (!$cat) {
            return false;
        }

        // обновляем для нее сеолинк
        $cat_seolink = cmsCore::generateCatSeoLink($cat, $table, $is_url_cyrillic);
        $this->query('UPDATE ' . $table . " SET seolink='" . $cat_seolink . "' WHERE id = '" . $cat['id'] . "'");

        // Получаем вложенные категории для нее
        $path_list = $this->get_table($table, 'NSLeft > ' . $cat['NSLeft'] . ' AND NSRight < ' . $cat['NSRight'] . ' AND parent_id > 0 ORDER BY NSLeft');

        if ($path_list) {
            foreach ($path_list as $pcat) {
                $subcat_seolink = cmsCore::generateCatSeoLink($pcat, $table, $is_url_cyrillic);
                $this->query('UPDATE ' . $table . " SET seolink='" . $subcat_seolink . "' WHERE id = '" . $pcat['id'] . "'");
            }
        }

        return true;
    }

    /**
     * Выполняет SQL из файла
     * @param str $sql_file Полный путь к файлу
     * @return bool
     */
    public function importFromFile($sql_file) {
        return $this->db->importDump($sql_file);
    }
}