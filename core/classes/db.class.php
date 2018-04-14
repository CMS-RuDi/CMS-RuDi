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

class cmsDatabase
{

    use \Singeltone;

    public $join     = '';
    public $select   = '';
    public $where    = '';
    public $group_by = '';
    public $order_by = '';
    public $limit    = '1000';
    public $page     = 1;
    public $perpage  = 10;
    private $cache   = []; // кеш некоторых запросов
    public $prefix;

    /**
     * ========= DEPRECATED =========
     */
    public $q_dump = [];
    private $db;

    protected function __construct()
    {
        $this->db     = \cms\db::getInstance();
        $this->prefix = cmsConfig::getConfig('db_prefix') . '_';
    }

    public function __destruct()
    {
        $this->db->__destruct();
    }

    /**
     * Реинициализирует соединение с базой
     */
    public static function reinitializedConnection()
    {
        return self::getInstance()->reconnect();
    }

    public function reconnect()
    {
        return $this->db->reconnect();
    }

    /**
     * Сбрасывает условия
     */
    public function resetConditions()
    {
        $this->where    = '';
        $this->select   = '';
        $this->join     = '';
        $this->group_by = '';
        $this->order_by = '';
        $this->limit    = '1000';

        return $this;
    }

    public function addJoin($join)
    {
        $this->join .= $join . PHP_EOL;
        return $this;
    }

    public function addSelect($condition)
    {
        $this->select .= ', ' . $condition;
        return $this;
    }

    public function where($condition)
    {
        $this->where .= ' AND (' . $condition . ')' . PHP_EOL;
        return $this;
    }

    public function groupBy($field)
    {
        $this->group_by = 'GROUP BY ' . $field;
        return $this;
    }

    public function orderBy($field, $direction = 'ASC')
    {
        $this->order_by = 'ORDER BY ' . $field . ' ' . $direction;
        return $this;
    }

    public function limit($howmany)
    {
        return $this->limitIs(0, $howmany);
    }

    public function limitIs($from, $howmany = '')
    {
        $this->limit = (int) $from;

        if ( $howmany ) {
            $this->limit .= ', ' . $howmany;
        }

        return $this;
    }

    public function limitPage($page, $perpage)
    {
        $this->page    = $page;
        $this->perpage = $perpage;

        return $this->limitIs(($page - 1) * $perpage, $perpage);
    }

    protected function replacePrefix($sql, $prefix = 'cms_')
    {
        if ( $prefix == $this->prefix ) {
            return trim($sql);
        }

        return trim(str_replace($prefix, $this->prefix, $sql));
    }

    protected static function replacePrefixTable($table)
    {
        $part = explode(' ', trim($table));

        foreach ( $part as $key => $value ) {
            if ( $key == 0 ) {
                $part[$key] = str_replace('cms_', '', $value);
            }
            else {
                $part[$key] = str_replace('cms_', '{#}', $value);
            }
        }

        return implode(' ', $part);
    }

    public function query($sql, $ignore_errors = false, $replace_prefix = true)
    {
        if ( empty($sql) ) {
            return false;
        }

        $sql = $replace_prefix ? $this->replacePrefix($sql) : $sql;

        return $this->db->query($sql, false, !(\cmsConfig::getInstance()->debug && !$ignore_errors));
    }

    public function num_rows($result)
    {
        return $this->db->numRows($result);
    }

    public function fetch_assoc($result)
    {
        return $this->db->fetchAssoc($result);
    }

    public function fetch_row($result)
    {
        return $this->db->fetchRow($result);
    }

    public function free_result($result)
    {
        return $this->db->freeResult();
    }

    public function fetch_all($result)
    {
        $array = [];

        if ( $this->num_rows($result) ) {
            while ( $object = $result->fetch_object() ) {
                $array[] = $object;
            }
        }

        return $array;
    }

    public function affected_rows()
    {
        return $this->affectedRows();
    }

    public function get_last_id($table = '')
    {
        if ( !$table ) {
            return $this->db->lastId();
        }

        $result = $this->query('SELECT LAST_INSERT_ID() as lastid FROM ' . $table . ' LIMIT 1');

        if ( $this->num_rows($result) ) {
            $data = $this->fetch_assoc($result);
            return $data['lastid'];
        }
        else {
            return 0;
        }
    }

    public function rows_count($table, $where, $limit = false)
    {
        return $this->db->getRowsCount(self::replacePrefixTable($table), $where, $limit);
    }

    public function get_field($table, $where = '1', $field = 'id')
    {
        $row = $this->db->getRow(self::replacePrefixTable($table), $where, $field);

        if ( empty($row) ) {
            return false;
        }

        return $row[$field];
    }

    public function get_fields($table, $where, $fields = '*', $order = 'id ASC')
    {
        return $this->db->getRow(self::replacePrefixTable($table), $where, $fields, $order);
    }

    public function get_table($table, $where = '', $fields = '*')
    {
        return $this->db->getRows(self::replacePrefixTable($table), $where, $fields, 'id ASC', true);
    }

    public function errno()
    {
        return $this->db->errno();
    }

    public function error()
    {
        return $this->db->error();
    }

    public function escape_string($value)
    {
        if ( is_array($value) ) {
            foreach ( $value as $key => $string ) {
                $value[$this->escape_string((string) $key)] = $this->escape_string($string);
            }

            return $value;
        }

        return $this->db->escape(stripcslashes($value));
    }

    public function isFieldExists($table, $field)
    {
        return $this->db->isFieldExists(str_replace('cms_', '', $table), $field);
    }

    public function isFieldType($table, $field, $type)
    {
        $sql = 'SHOW COLUMNS FROM ' . $table . " WHERE Field = '" . $field . "' AND Type = '" . $type . "'";

        $result = $this->query($sql);

        if ( $this->errno() ) {
            return false;
        }

        return (bool) $this->num_rows($result);
    }

    public function isTableExists($table)
    {
        return $this->db->isTableExists(str_replace('cms_', '', $table));
    }

    public static function optimizeTables($tlist = '')
    {
        $inDB = self::getInstance();

        if ( is_array($tlist) ) {
            foreach ( $tlist as $tname ) {
                $inDB->query('OPTIMIZE TABLE ' . $tname, true);
                $inDB->query('ANALYZE TABLE ' . $tname, true);
            }
        }
        else if ( $inDB->isTableExists('information_schema.tables') ) {
            $base = cmsConfig::getConfig('db_base');

            $tlist = $inDB->get_table('information_schema.tables', "table_schema = '" . $base . "'", 'table_name');

            if ( !is_array($tlist) ) {
                return false;
            }

            foreach ( $tlist as $tname ) {
                $inDB->query('OPTIMIZE TABLE ' . $tname['table_name'], true);
                $inDB->query('ANALYZE TABLE ' . $tname['table_name'], true);
            }
        }

        if ( $inDB->errno() ) {
            return false;
        }

        return true;
    }

    /**
     * Добавляет массив записей в таблицу
     * ключи массива должны совпадать с полями в таблице
     */
    public function insert($table, $insert_array, $ignore = false)
    {
        // убираем из массива ненужные ячейки
        $insert_array = $this->removeTheMissingCell($table, $insert_array);
        $set          = '';

        // формируем запрос на вставку в базу
        foreach ( $insert_array as $field => $value ) {
            $set .= "`" . $field . "` = '" . $value . "',";
        }

        // убираем последнюю запятую
        $set = rtrim($set, ',');

        $i = $ignore ? 'IGNORE' : '';

        $this->query('INSERT ' . $i . ' INTO ' . $table . ' SET ' . $set);

        if ( $this->errno() ) {
            return false;
        }

        return $this->get_last_id($table);
    }

    /**
     * Обновляет данные в таблице
     * ключи массива должны совпадать с полями в таблице
     */
    public function update($table, $update_array, $id)
    {
        if ( isset($update_array['id']) ) {
            unset($update_array['id']);
        }

        // id или where
        if ( is_numeric($id) ) {
            $where = "id = '" . $id . "' LIMIT 1";
        }
        else {
            $where = $id;
        }

        // убираем из массива ненужные ячейки
        $update_array = $this->removeTheMissingCell($table, $update_array);

        $set = '';

        // формируем запрос на вставку в базу
        foreach ( $update_array as $field => $value ) {
            $set .= '`' . $field . "` = '" . $value . "',";
        }

        // убираем последнюю запятую
        $set = rtrim($set, ',');

        $this->query('UPDATE ' . $table . ' SET ' . $set . ' WHERE ' . $where);

        if ( $this->errno() ) {
            return false;
        }

        return true;
    }

    /**
     * Убирает из массива ячейки, которых нет в таблице назначения
     * используется при вставке/обновлении значений таблицы
     */
    public function removeTheMissingCell($table, $array)
    {
        $result = $this->query('SHOW COLUMNS FROM `' . $table . '`');

        $list = [];

        while ( $data = $this->fetch_assoc($result) ) {
            $list[$data['Field']] = '';
        }

        // убираем ненужные ячейки массива
        foreach ( $array as $k => $v ) {
            if ( !isset($list[$k]) ) {
                unset($array[$k]);
            }
        }

        if ( !$array || !is_array($array) ) {
            return [];
        }

        return $array;
    }

    public function delete($table, $where = '', $limit = false)
    {
        return $this->db->delete(self::replacePrefixTable($table), $where, $limit);
    }

    public function setFlag($table, $id, $flag, $value)
    {
        $this->query('UPDATE ' . $table . ' SET ' . $flag . " = '" . $value . "' WHERE id='" . $id . "'");
        return $this;
    }

    public function setFlags($table, $items, $flag, $value)
    {
        $ids = [];

        foreach ( $items as $id ) {
            $id = (int) $id;

            if ( !empty($id) ) {
                $ids[] = $id;
            }
        }

        $this->query('UPDATE ' . $table . ' SET ' . $flag . " = '" . $value . "' WHERE `id` IN (" . implode(',', $ids) . ') LIMIT ' . count($ids));

        return $this;
    }

    public function deleteNS($table, $id, $differ = '')
    {
        return cmsCore::getInstance()->nestedSetsInit($table)->DeleteNode($id, $differ);
    }

    public function getNsRootCatId($table, $differ = '')
    {
        if ( isset($this->cache[$table][$differ]) ) {
            return $this->cache[$table][$differ];
        }

        $root_cat = $this->getNsCategory($table, 0, $differ);

        return $root_cat ? ($this->cache[$table][$differ] = $root_cat['id']) : false;
    }

    public function getNsCategory($table, $cat_id_or_link = 0, $differ = '')
    {
        if ( isset($this->cache[$table][$cat_id_or_link][$differ]) ) {
            return $this->cache[$table][$cat_id_or_link][$differ];
        }

        if ( !$cat_id_or_link ) {
            $where = 'NSLevel = 0';
        }
        else {
            if ( is_numeric($cat_id_or_link) ) { // если пришла цифра, считаем ее cat_id
                $where = "id = '" . $cat_id_or_link . "'";
            }
            else {
                $where = "seolink = '" . $cat_id_or_link . "'";
            }
        }

        if ( isset($differ) ) {
            $where .= " AND NSDiffer = '" . $differ . "'";
        }

        $cat = $this->get_fields($table, $where, '*');

        return $cat ? $this->cache[$table][$cat_id_or_link][$differ] = $cat : false;
    }

    public function moveNsCategory($table, $cat_id, $dir = 'up')
    {
        $ns = cmsCore::getInstance()->nestedSetsInit($table);

        if ( $dir == 'up' ) {
            $ns->MoveOrdering($cat_id, -1);
        }
        else {
            $ns->MoveOrdering($cat_id, 1);
        }

        return true;
    }

    public function addNsCategory($table, $cat, $differ = '')
    {
        $cat_id = cmsCore::getInstance()->nestedSetsInit($table)->AddNode($cat['parent_id'], -1, $differ);

        if ( !$cat_id ) {
            return false;
        }

        $this->update($table, $cat, $cat_id);

        return $cat_id;
    }

    public function addRootNsCategory($table, $differ = '', $cat)
    {
        $cat_id = cmsCore::getInstance()->nestedSetsInit($table)->AddRootNode($differ);

        if ( !$cat_id ) {
            return false;
        }

        $this->update($table, $cat, $cat_id);

        return $cat_id;
    }

    public function getNsCategoryPath($table, $left_key, $right_key, $fields = '*', $differ = '', $only_nested = false)
    {
        $nested_sql = $only_nested ? '' : '=';

        $path = $this->get_table($table, 'NSLeft <' . $nested_sql . ' ' . $left_key . ' AND NSRight >' . $nested_sql . ' ' . $right_key . " AND parent_id > 0 AND NSDiffer = '" . $differ . "' ORDER BY NSLeft", $fields);

        return $path;
    }

    /**
     * Обновляет ссылку на категорию и вложенные в нее
     * Подразумевается, что заголовок категории или поле url изменен заранее
     * @return bool
     */
    public function updateNsCategorySeoLink($table, $cat_id, $is_url_cyrillic = false)
    {
        // получаем изменяемую категорию
        $cat = $this->getNsCategory($table, $cat_id);

        if ( !$cat ) {
            return false;
        }

        // обновляем для нее сеолинк
        $cat_seolink = cmsCore::generateCatSeoLink($cat, $table, $is_url_cyrillic);
        $this->query('UPDATE ' . $table . " SET seolink='" . $cat_seolink . "' WHERE id = '" . $cat['id'] . "'");

        // Получаем вложенные категории для нее
        $path_list = $this->get_table($table, 'NSLeft > ' . $cat['NSLeft'] . ' AND NSRight < ' . $cat['NSRight'] . ' AND parent_id > 0 ORDER BY NSLeft');

        if ( $path_list ) {
            foreach ( $path_list as $pcat ) {
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
    public function importFromFile($sql_file)
    {
        return $this->db->importDump($sql_file);
    }

}
