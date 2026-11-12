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

namespace cms;

/**
 * Слой доступа к базе данных CMS RuDi.
 *
 * Наследует всю работу с SQL от \DSSoft\DB и оставляет только
 * CMS-специфичные обёртки, совместимые с историческим API.
 */
class db extends \DSSoft\DB {

    use \Singeltone;

    /**
     * Префикс таблиц CMS.
     * @var string
     */
    public $prefix = '';

    /**
     * CMS по умолчанию показывает 10 записей, а не 50.
     */
    public $perpage = 10;
    public $default_perpage = 10;

    /**
     * @var nestedSets
     */
    private $nestedSets;

    /**
     * Кеш корневых категорий для старых NS-методов.
     * @var array
     */
    private $ns_cache = [];

    public function __construct() {
        $this->initConnectionFromConfig();
    }

    private function initConnectionFromConfig() {
        $config = \cmsConfig::getInstance()->getConfig();

        // db_engine в конфиге CMS — это ENGINE таблиц (MyISAM/InnoDB),
        // а не тип драйвера DSSoft. Драйвер всегда MySQL.
        $engine = !empty($config['db_engine']) ? $config['db_engine'] : 'MyISAM';

        parent::__construct(
                $config['db_host'],
                $config['db_user'],
                $config['db_pass'],
                $config['db_base'],
                'MySQL',
                [
                    'table_prefix' => $config['db_prefix'] . '_',
                    'db_users_table' => !empty($config['db_users_table']) ? $config['db_users_table'] : $config['db_prefix'] . '_users',
                    'db_charset' => 'utf8',
                    'db_engine' => $engine,
                    'db_port' => isset($config['db_port']) ? $config['db_port'] : null,
                    'db_socket' => isset($config['db_socket']) ? $config['db_socket'] : null,
                    'clear_sql_mode' => !empty($config['clear_sql_mode'])
                ]
        );

        $this->prefix = $config['db_prefix'] . '_';

        // Legacy-компоненты интерполируют $inDB->select в SQL как строку.
        $this->select = '';
    }

    public static function reinitializedConnection() {
        return self::getInstance()->reconnect();
    }

    /**
     * PHP 5.6 не умеет (clone $obj)->method(); этот хелпер возвращает
     * независимое состояние builder'а с общим соединением.
     */
    public static function cloneOf($db) {
        return clone $db;
    }

    public function initConnection() {
        return $this->reconnect();
    }

    /**
     * Клон — это отдельное состояние builder'а с общим соединением.
     * Нужно моделям, чтобы фильтры одного запроса не влияли на другой.
     */
    public function __clone() {
        $this->resetFilters();
        $this->nestedSets = null;
        $this->ns_cache = [];
    }

    public function __get($name) {
        if ($name === 'nestedSets') {
            if (!$this->nestedSets) {
                $this->nestedSets = new nestedSets($this);
            }

            return $this->nestedSets;
        }

        return null;
    }

    /**
     * Переподключение к БД с актуальными настройками.
     *
     * @param bool $is_force
     * @return bool
     */
    public function reconnect($is_force = false) {
        // Установщик и смена настроек требуют перечитать конфиг,
        // а не только переподключить старый набор параметров.
        if ($is_force) {
            $this->initConnectionFromConfig();

            return $this->status();
        }

        $result = parent::reconnect(false);

        $this->prefix = $this->options['table_prefix'];

        return $result;
    }

    public function ready() {
        return parent::status();
    }

    public function connectError() {
        return parent::ConError();
    }

    public function connectErrno() {
        return parent::ErrorNo();
    }

    /**
     * Гибкая обёртка: старый код CMS передаёт вторым аргументом bool
     * ($ignore_errors), новый — массив параметров.
     */
    public function escape_string($value) {
        if (is_array($value)) {
            foreach ($value as $key => $string) {
                // Ключи — это имена полей, их нельзя менять экранированием.
                $value[$key] = $this->escape_string($string);
            }

            return $value;
        }

        return $this->escape(stripcslashes((string) $value));
    }

    /**
     * CMS-семантика: пустая строка остаётся пустой строкой, NULL — NULL.
     * Это важно для старых NOT NULL-полей без DEFAULT.
     */
    public function prepareValue($field, $value, $array_as = 'json') {
        if (is_array($value)) {
            if (class_exists('\\cms\\model')) {
                $value = \cms\model::arrayToYaml($value);
            } elseif (class_exists('\\Spyc')) {
                $value = \Spyc::YAMLDump($value);
            } else {
                $value = json_encode($value);
            }

            return "'" . $this->escape($value) . "'";
        }

        if (strpos((string) $field, 'date_') === 0 && $value === false) {
            return 'NULL';
        }

        if (strpos((string) $field, 'date_') === 0 && ($value === '' || is_null($value))) {
            return 'CURRENT_TIMESTAMP';
        }

        if (is_bool($value)) {
            return (string) (int) $value;
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if ($value === '') {
            return "''";
        }

        if ($value instanceof \Closure) {
            $value = $value($this);
        }

        return "'" . $this->escape(trim((string) $value)) . "'";
    }

    //========================================================================//
    // Исторические методы выборки CMS

    private function normalizeTable($table_name) {
        $table_name = trim((string) $table_name);

        // исторический код CMS передаёт имена с жёстким cms_
        if ($this->prefix && strpos($table_name, $this->prefix) === 0) {
            $table_name = substr($table_name, strlen($this->prefix));
        }

        return $table_name;
    }

    private function tableSql($table_name) {
        $table_name = $this->normalizeTable($table_name);

        return preg_match('/^[a-z0-9_]+\\./i', $table_name) ? $table_name : '{#}' . $table_name;
    }

    public function getRows($table_name, $where = '1', $fields = '*', $order = 'id ASC', $quiet = false) {
        $where = ($where === '' || $where === null) ? '1' : $where;

        $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableSql($table_name)
                . ' WHERE ' . $where
                . ($order ? ' ORDER BY ' . $order : '');

        $result = $this->query($sql, false, $quiet);

        if ($result === false || !$result->rowsCount()) {
            return [];
        }

        $data = [];

        while ($item = $result->fetchAssoc()) {
            $data[] = $item;
        }

        return $data;
    }

    public function getRow($table_name, $where = '1', $fields = '*', $order = '') {
        $where = ($where === '' || $where === null) ? '1' : $where;

        $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableSql($table_name)
                . ' WHERE ' . $where
                . ($order ? ' ORDER BY ' . $order : '')
                . ' LIMIT 1';

        $result = $this->query($sql);

        if ($result === false || !$result->rowsCount()) {
            return false;
        }

        return $result->fetchAssoc();
    }

    public function get($table_name, $item_callback = false, $key_field = 'id') {
        return parent::get($this->normalizeTable($table_name), $item_callback, $key_field);
    }

    public function getCount($table_name, $by_field = 'id', $reset = false) {
        $this->where = preg_replace('/^\s*AND\s+/i', '', (string) $this->where);
        $this->group_by = preg_replace('/^\s*GROUP BY\s+/i', '', (string) $this->group_by);

        return parent::getCount($this->normalizeTable($table_name), $by_field, $reset);
    }

    public function getItem($table_name, $item_callback = false) {
        if (!is_array($this->select)) {
            $this->select = $this->select === '' ? ['i.*'] : [$this->select];
        }

        $this->where = preg_replace('/^\s*AND\s+/i', '', (string) $this->where);
        $this->group_by = preg_replace('/^\s*GROUP BY\s+/i', '', (string) $this->group_by);
        $this->order_by = preg_replace('/^\s*ORDER BY\s+/i', '', (string) $this->order_by);

        // DSSoft getItem() сам сбрасывает фильтры/select после выборки.
        return parent::getItem($this->normalizeTable($table_name), $item_callback);
    }

    public function getItemByField($table_name, $field_name, $field_value, $item_callback = false) {
        return parent::getItemByField($this->normalizeTable($table_name), $field_name, $field_value, $item_callback);
    }

    public function getFieldFiltered($table_name, $field_name) {
        return parent::getFieldFiltered($this->normalizeTable($table_name), $field_name);
    }

    public function updateFiltered($table_name, $data, $skip_check_fields = false, $array_as = 'json') {
        $this->where = preg_replace('/^\s*AND\s+/i', '', (string) $this->where);

        return parent::updateFiltered($this->normalizeTable($table_name), $data, $skip_check_fields, $array_as);
    }

    public function deleteFiltered($table_name) {
        $this->where = preg_replace('/^\s*AND\s+/i', '', (string) $this->where);

        return parent::deleteFiltered($this->normalizeTable($table_name));
    }

    public function getTableFields($table) {
        return parent::getTableFields($this->normalizeTable($table));
    }

    public function isTableExists($table) {
        $table = $this->normalizeTable($table);

        if (strpos($table, 'information_schema.') === 0) {
            $result = $this->query('SELECT 1 FROM ' . $table . ' LIMIT 1', false, true);

            return $result !== false;
        }

        return parent::isTableExists($table);
    }

    public function isFieldExists($table, $field) {
        return parent::isFieldExists($this->normalizeTable($table), $field);
    }

    public function isFieldUnique($table, $field, $value, $exclude_row_id = false) {
        return parent::isFieldUnique($this->normalizeTable($table), $field, $value, $exclude_row_id);
    }

    public function getTableFieldsTypes($table) {
        return parent::getTableFieldsTypes($this->normalizeTable($table));
    }

    /**
     * Историческая сигнатура CMS: ($table, $where, $field, $order).
     * Не путать с \DSSoft\DB::getField($table, $row_id, $field).
     */
    public function getField($table, $where, $field, $order = '') {
        // Три аргумента с числовым id — нативная сигнатура DSSoft\DB.
        if (func_num_args() === 3 && is_int($where)) {
            return parent::getField($this->normalizeTable($table), $where, $field);
        }

        $row = $this->getRow($table, $where, $field, $order);

        return isset($row[$field]) ? $row[$field] : false;
    }

    public function getFields($table, $where, $fields = '*', $order = '') {
        return $this->getRow($table, $where, $fields, $order);
    }

    public function getRowsCount($table, $where = '1', $limit = false) {
        $where = ($where === '' || $where === null) ? '1' : $where;

        $sql = 'SELECT COUNT(1) FROM ' . $this->tableSql($table) . ' WHERE ' . $where;

        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $result = $this->query($sql);

        if ($result === false || !$result->rowsCount()) {
            return false;
        }

        $row = $result->fetchRow();

        return (int) $row[0];
    }

    public function rows_count($table, $where = '1', $limit = false) {
        return $this->getRowsCount($table, $where, $limit);
    }

    public function get_table($table, $where = '', $fields = '*') {
        return $this->getRows($table, $where, $fields, 'id ASC', true);
    }

    public function get_fields($table, $where = '1', $fields = '*', $order = 'id ASC') {
        return $this->getRow($table, $where, $fields, $order);
    }

    //========================================================================//
    // Обновление/удаление с двумя историческими сигнатурами

    public function changeValue($table_name, $field_name, $field_value, $where, $limit = 1) {
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $field_name) || $where === '') {
            return false;
        }

        $this->filter($where);

        return $this->updateFiltered($this->normalizeTable($table_name), [$field_name => $field_value]);
    }

    public function delete($table, $where, $limit = false) {
        $table = $this->normalizeTable($table);

        // Историческая сигнатура cms\model: delete($table, $id, $by_field).
        if ($limit && !is_numeric($limit)) {
            $this->filterEqual($limit, $where);

            return $this->deleteFiltered($table);
        }

        if (is_numeric($where)) {
            return parent::delete($table, $where);
        }

        if (empty($where)) {
            return false;
        }

        return $this->query(
                        'DELETE FROM {#}' . $table . ' WHERE ' . $where . ($limit ? ' LIMIT ' . (int) $limit : '')
                );
    }

    //========================================================================//
    // Совместимость со старым построителем cmsDatabase

    public function resetConditions() {
        parent::resetFilters();

        $this->join = '';
        $this->select = '';
        $this->where = '';
        $this->group_by = '';
        $this->order_by = '';
        $this->limit = 1000;

        return $this;
    }

    public function resetFilters() {
        parent::resetFilters();

        $this->select = '';

        return $this;
    }

    public function select($field, $as = false) {
        if (!is_array($this->select)) {
            $this->select = $this->select === '' ? [] : [$this->select];
        }

        return parent::select($field, $as);
    }

    public function selectOnly($field, $as = false) {
        $this->select = [];

        return parent::selectOnly($field, $as);
    }

    /**
     * Legacy-сборка SQL для компонентов, которые ещё читают свойства
     * where/join/select/group_by/order_by напрямую.
     */
    public function addJoin($join) {
        $this->join .= $join . PHP_EOL;

        return $this;
    }

    public function addSelect($condition) {
        if (is_array($this->select)) {
            $this->select = implode(', ', $this->select);
        }

        if ($this->select === '') {
            $this->select = $condition;
        } else {
            $this->select .= ', ' . $condition;
        }

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
        $order = trim((string) $field);
        $direction = trim((string) $direction);

        // если направление уже входит в $field ('pubdate DESC'), не добавляем его
        if ($direction !== '' && !preg_match('/\s+(asc|desc)\b/i', $order)) {
            $order .= ' ' . $direction;
        }

        if (!preg_match('/\s+(asc|desc)\b/i', $order)) {
            $order .= ' ASC';
        }

        $this->order_by = 'ORDER BY ' . $order;

        return $this;
    }

    public function limitIs($from, $howmany = '') {
        return parent::limit((int) $from, (int) $howmany);
    }

    /**
     * Собирает SQL, понимая как новый builder, так и legacy-формат
     * свойств (AND (...), GROUP BY ..., ORDER BY ...).
     */
    public function getSQL() {
        $select = $this->select;
        $where = $this->where;
        $group_by = $this->group_by;
        $order_by = $this->order_by;

        if (!is_array($this->select)) {
            $this->select = $this->select === '' ? ['i.*'] : [$this->select];
        }

        $this->where = preg_replace('/^\s*AND\s+/i', '', (string) $this->where);
        $this->group_by = preg_replace('/^\s*GROUP BY\s+/i', '', (string) $this->group_by);
        $this->order_by = preg_replace('/^\s*ORDER BY\s+/i', '', (string) $this->order_by);

        $sql = parent::getSQL();

        $this->select = $select;
        $this->where = $where;
        $this->group_by = $group_by;
        $this->order_by = $order_by;

        return $sql;
    }

    public function replacePrefix($sql, $prefix = 'cms_') {
        $users_table = !empty($this->options['db_users_table']) ? $this->options['db_users_table'] : $this->options['table_prefix'] . 'users';

        $sql = str_replace(['{#}{users}', '{users}'], [$users_table, $users_table], $sql);

        return parent::replacePrefix($sql);
    }

    //========================================================================//
    // Прямые обёртки над результатом/соединением

    public function numRows($result) {
        return $result ? $result->rowsCount() : 0;
    }

    public function num_rows($result) {
        return $this->numRows($result);
    }

    public function fetchAssoc($result) {
        return $result ? $result->fetchAssoc() : false;
    }

    public function fetch_assoc($result) {
        return $this->fetchAssoc($result);
    }

    public function fetchRow($result) {
        return $result ? $result->fetchRow() : false;
    }

    public function fetch_row($result) {
        return $this->fetchRow($result);
    }

    public function fetchAll($result) {
        return $result ? $result->fetchAll() : [];
    }

    public function fetch_all($result) {
        return $this->fetchAll($result);
    }

    public function freeResult($result) {
        unset($result);
    }

    public function free_result($result) {
        $this->freeResult($result);
    }

    public function affectedRows() {
        return $this->getAffectedRows();
    }

    public function affected_rows() {
        return $this->affectedRows();
    }

    public function lastId() {
        return $this->db->lastId();
    }

    public function get_last_id($table = '') {
        if (!$table) {
            return $this->lastId();
        }

        $row = $this->getRow($table, '1', 'MAX(id) as lastid', '');

        return isset($row['lastid']) ? (int) $row['lastid'] : 0;
    }

    public function errno() {
        return parent::ErrorNo();
    }

    public function error() {
        return (string) parent::Error();
    }

    //========================================================================//
    // CMS-специфичные операции

    public function setFlag($table, $id, $flag, $value) {
        $this->changeValue($this->normalizeTable($table), $flag, $value, 'id=' . (int) $id);

        return $this;
    }

    public function setFlags($table, $items, $flag, $value) {
        $ids = [];

        foreach ((array) $items as $id) {
            $id = (int) $id;

            if ($id) {
                $ids[] = $id;
            }
        }

        if ($ids) {
            $this->changeValue($this->normalizeTable($table), $flag, $value, 'id IN (' . implode(',', $ids) . ')', count($ids));
        }

        return $this;
    }

    public function isFieldType($table, $field, $type) {
        $types = $this->getTableFieldsTypes($table);

        return isset($types[$field]) && $types[$field] === $type;
    }

    public function deleteNS($table, $id, $differ = '') {
        return \cmsCore::getInstance()->nestedSetsInit($table)->DeleteNode($id, $differ);
    }

    public function getNsRootCatId($table, $differ = '') {
        if (isset($this->ns_cache[$table][$differ])) {
            return $this->ns_cache[$table][$differ];
        }

        $root_cat = $this->getNsCategory($table, 0, $differ);

        return $root_cat ? ($this->ns_cache[$table][$differ] = $root_cat['id']) : false;
    }

    public function getNsCategory($table, $cat_id_or_link = 0, $differ = '') {
        if (isset($this->ns_cache['cats'][$table][$cat_id_or_link][$differ])) {
            return $this->ns_cache['cats'][$table][$cat_id_or_link][$differ];
        }

        if (!$cat_id_or_link) {
            $where = 'NSLevel = 0';
        } elseif (is_numeric($cat_id_or_link)) {
            $where = 'id = ' . (int) $cat_id_or_link;
        } else {
            $where = "seolink = '" . $this->escape($cat_id_or_link) . "'";
        }

        // null означает «любой differ»; пустая строка — конкретно пустой differ.
        if ($differ !== null) {
            $where .= " AND NSDiffer = '" . $this->escape((string) $differ) . "'";
        }

        $cat = $this->getRow($table, $where, '*');

        if (!$cat) {
            return false;
        }

        $this->ns_cache['cats'][$table][$cat_id_or_link][$differ] = $cat;

        return $cat;
    }

    public function moveNsCategory($table, $cat_id, $dir = 'up') {
        $ns = \cmsCore::getInstance()->nestedSetsInit($table);

        $ns->MoveOrdering($cat_id, $dir === 'up' ? -1 : 1);

        return true;
    }

    public function addNsCategory($table, $cat, $differ = '') {
        $cat_id = \cmsCore::getInstance()->nestedSetsInit($table)->AddNode($cat['parent_id'], -1, $differ);

        if (!$cat_id) {
            return false;
        }

        $this->update($table, $cat_id, $cat);

        return $cat_id;
    }

    public function addRootNsCategory($table, $differ = '', $cat = []) {
        $cat_id = \cmsCore::getInstance()->nestedSetsInit($table)->AddRootNode($differ);

        if (!$cat_id) {
            return false;
        }

        $this->update($table, $cat_id, $cat);

        return $cat_id;
    }

    public function getNsCategoryPath($table, $left_key, $right_key, $fields = '*', $differ = '', $only_nested = false) {
        $nested_sql = $only_nested ? '' : '=';

        return $this->getRows(
                        $table,
                        'NSLeft <' . $nested_sql . ' ' . (int) $left_key
                        . ' AND NSRight >' . $nested_sql . ' ' . (int) $right_key
                        . " AND parent_id > 0 AND NSDiffer = '" . $this->escape((string) $differ) . "'",
                        $fields,
                        'NSLeft'
                );
    }

    public function updateNsCategorySeoLink($table, $cat_id, $is_url_cyrillic = false) {
        $cat = $this->getNsCategory($table, $cat_id);

        if (!$cat) {
            return false;
        }

        $cat_seolink = \cmsCore::generateCatSeoLink($cat, $table, $is_url_cyrillic);
        $this->update($table, (int) $cat['id'], ['seolink' => $cat_seolink]);

        $path_list = $this->getRows(
                $table,
                'NSLeft > ' . (int) $cat['NSLeft'] . ' AND NSRight < ' . (int) $cat['NSRight'] . ' AND parent_id > 0',
                '*',
                'NSLeft'
        );

        foreach ($path_list as $pcat) {
            $subcat_seolink = \cmsCore::generateCatSeoLink($pcat, $table, $is_url_cyrillic);
            $this->update($table, (int) $pcat['id'], ['seolink' => $subcat_seolink]);
        }

        return true;
    }

    public static function optimizeTables($tlist = '') {
        $db = self::getInstance();

        if (is_array($tlist)) {
            foreach ($tlist as $tname) {
                $db->query('OPTIMIZE TABLE ' . $tname, true);
                $db->query('ANALYZE TABLE ' . $tname, true);
            }

            return true;
        }

        $base = \cmsConfig::getConfig('db_base');

        $rows = $db->getRows('information_schema.tables', "table_schema = '" . $db->escape($base) . "'", 'table_name', 'table_name');

        foreach ($rows as $row) {
            $db->query('OPTIMIZE TABLE `' . str_replace('`', '``', $row['table_name']) . '`', true);
            $db->query('ANALYZE TABLE `' . str_replace('`', '``', $row['table_name']) . '`', true);
        }

        return $db->errno() === 0;
    }

    public function createCategoriesTable($table_name) {
        $table_name = $this->normalizeTable($table_name);

        $sql = "CREATE TABLE `{#}" . $table_name . "` (
                  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `parent_id` int(11) UNSIGNED DEFAULT NULL,
                  `title` varchar(200) NULL DEFAULT NULL,
                  `description` text NULL DEFAULT NULL,
                  `slug` varchar(255) NULL DEFAULT NULL,
                  `slug_key` varchar(255) NULL DEFAULT NULL,
                  `seo_keys` varchar(256) DEFAULT NULL,
                  `seo_desc` varchar(256) DEFAULT NULL,
                  `seo_title` varchar(256) DEFAULT NULL,
                  `ordering` int(11) UNSIGNED DEFAULT NULL,
                  `ns_left` int(11) UNSIGNED DEFAULT NULL,
                  `ns_right` int(11) UNSIGNED DEFAULT NULL,
                  `ns_level` int(11) UNSIGNED DEFAULT NULL,
                  `ns_differ` varchar(32) NOT NULL DEFAULT '',
                  `ns_ignore` tinyint(4) UNSIGNED NOT NULL DEFAULT '0',
                  `allow_add` text,
                  PRIMARY KEY (`id`),
                  KEY `slug` (`slug`),
                  KEY `parent_id` (`parent_id`,`ns_left`),
                  KEY `ns_left` (`ns_level`,`ns_right`,`ns_left`),
                  KEY `ordering` (`ordering`)
                ) ENGINE=" . $this->options['db_engine'] . " DEFAULT CHARSET=utf8";

        $this->query($sql);

        if (!$this->nestedSets) {
            $this->nestedSets = new nestedSets($this);
        }

        $this->nestedSets->setTable($table_name);
        $this->nestedSets->addRootNode();

        return true;
    }

    public function createCategoriesBindsTable($table_name) {
        $table_name = $this->normalizeTable($table_name);

        $this->query("CREATE TABLE `{#}" . $table_name . "` (
                  `item_id` int(11) UNSIGNED DEFAULT NULL,
                  `category_id` int(11) UNSIGNED DEFAULT NULL,
                  KEY `item_id` (`item_id`),
                  KEY `category_id` (`category_id`)
                ) ENGINE=" . $this->options['db_engine'] . " DEFAULT CHARSET=utf8");

        return true;
    }

    public function importFromFile($sql_file) {
        return $this->importDump($sql_file);
    }

    public function reorderNS($table) {
        $items = $this->getRows($table, '1', '*', 'ns_left');

        if (!$items) {
            return;
        }

        $level = [];

        foreach ($items as $item) {
            $l = (int) $item['ns_level'];

            if (isset($level[$l])) {
                $level[$l]++;
            } else {
                $level[$l] = 1;
            }

            $this->update($table, (int) $item['id'], ['ordering' => $level[$l]]);
        }
    }
}