<?php

namespace cms;

class db
{

    use \Singeltone;

    /**
     * Префикс таблиц
     * @var string
     */
    public $prefix;

    /**
     * Массив кешированного списка полей для запрашиваемых таблиц
     * @var array
     */
    protected $table_fields = [];

    /**
     * Объект, представляющий подключение к серверу MySQL
     * @var \mysqli
     */
    protected $mysqli;

    /**
     * Время соединения с базой
     * @var int
     */
    protected $init_start_time;

    /**
     * Время, через которое при PHP_SAPI == 'cli' нужно сделать реконнект
     * для случаев, когда mysql.connect_timeout по дефолту (60 с) и переопределить это поведение нельзя
     * @var int
     */
    protected $reconnect_time = 60;

    /**
     * Ошибка подключения к базе
     * @var bool|string
     */
    protected $connect_error = false;

    /**
     * Настройки базы данных
     * @var array
     */
    protected $options = [];

//============================================================================//

    public function __construct()
    {
        $this->setOptions(\cmsConfig::getInstance()->getConfig());
        $this->connect();
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function __destruct()
    {
        if ( $this->ready() ) {
            $this->mysqli->close();
        }
    }

    public function __get($name)
    {
        if ( $name == 'nestedSets' ) {
            $this->nestedSets = new nestedSets($this);
            return $this->nestedSets;
        }
    }

    protected function connect()
    {
        if ( !empty($this->options['debug']) ) {
            debug::startTimer('db');
        }

        mysqli_report(MYSQLI_REPORT_STRICT);

        try {
            $this->mysqli = new \mysqli($this->options['db_host'], $this->options['db_user'], $this->options['db_pass'], $this->options['db_base']);
        }
        catch ( \Exception $e ) {
            $this->connect_error = $e->getMessage();
            return false;
        }

        $this->mysqli->set_charset('utf8');

        if ( !empty($this->options['clear_sql_mode']) ) {
            $this->mysqli->query("SET sql_mode=''");
        }

        if ( !empty($this->options['debug']) ) {
            debug::setDebugInfo('db', 'Database connection', false, 3);
        }

        $this->setTimezone();
        $this->setLcMessages();

        $this->prefix = $this->options['db_prefix'] . '_';

        $this->init_start_time = time();

        return true;
    }

    public function ready()
    {
        return $this->connect_error === false;
    }

    public function connectError()
    {
        return $this->connect_error;
    }

    public function reconnect($is_force = false)
    {
        if ( $is_force || !$this->mysqli->ping() ) {
            $this->mysqli->close();
            return $this->connect();
        }

        return true;
    }

    public function getStat()
    {
        if ( isset($this->mysqli->stat) ) {
            return $this->mysqli->stat;
        }

        return '';
    }

    public function setTimezone()
    {
        $this->query("SET `time_zone` = '%s'", date('P'));
        return $this;
    }

    public function setLcMessages()
    {
        if ( defined('LC_LANGUAGE_TERRITORY') ) {
            $this->mysqli->query("SET lc_messages = '" . LC_LANGUAGE_TERRITORY . "'");
        }

        return $this;
    }

//============================================================================//

    /**
     * Подготавливает строку перед запросом
     *
     * @param string $string
     *
     * @return string
     */
    public function escape($string)
    {
        return $this->mysqli->real_escape_string($string);
    }

    /**
     * Выполняет запрос в базе
     *
     * @param string $sql Строка запроса
     * @param array|string $params Аргументы запроса, которые будут переданы в vsprintf
     * @param bool $quiet В случае ошибки запроса отдавать false, а не "умирать"
     *
     * @return boolean|\mysqli_result
     */
    public function query($sql, $params = false, $quiet = false)
    {
        if ( !empty($this->options['debug']) ) {
            debug::startTimer('db');
        }

        $sql = str_replace([ '{#}{users}', '{users}', '{#}' ], [ $this->options['db_users_table'], $this->options['db_users_table'], $this->prefix ], $sql);

        if ( $params ) {
            if ( !is_array($params) ) {
                $params = [ $params ];
            }

            foreach ( $params as $index => $param ) {
                if ( !is_numeric($param) ) {
                    $params[$index] = $this->escape($param);
                }
            }

            $sql = vsprintf($sql, $params);
        }

        if ( PHP_SAPI == 'cli' && (time() - $this->init_start_time) >= $this->reconnect_time ) {
            $this->reconnect();
        }

        $result = $this->mysqli->query($sql);

        if ( !empty($this->options['debug']) ) {
            debug::setDebugInfo('db', $sql);
        }

        if ( !$this->mysqli->errno ) {
            return $result;
        }

        if ( $quiet ) {
            return false;
        }

        die($this->error() . PHP_EOL . $sql);

        //cmsCore::error(sprintf(ERR_DATABASE_QUERY, $this->error()), $sql);
    }

//============================================================================//

    public function freeResult($result)
    {
        $result->close();
    }

    public function affectedRows()
    {
        return $this->mysqli->affected_rows;
    }

    public function numRows($result)
    {
        return $result ? $result->num_rows : false;
    }

    public function fetchAssoc($result)
    {
        return $result->fetch_assoc();
    }

    public function fetchRow($result)
    {
        return $result->fetch_row();
    }

    public function errno()
    {
        return $this->mysqli->errno;
    }

    public function error()
    {
        return $this->mysqli->error;
    }

//============================================================================//

    /**
     * Возвращает ID последней вставленной записи из таблицы
     *
     * @return int
     */
    public function lastId()
    {
        return $this->mysqli->insert_id;
    }

    /**
     * Возвращает все названия полей для таблицы
     *
     * @param string $table
     *
     * @return array
     */
    public function getTableFields($table)
    {
        if ( isset($this->table_fields[$table]) ) {
            return $this->table_fields[$table];
        }

        $result = $this->query('SHOW COLUMNS FROM `{#}' . $table . '`');

        $fields = [];

        while ( $data = $this->fetchAssoc($result) ) {
            $fields[] = $data['Field'];
        }

        $this->table_fields[$table] = $fields;

        return $fields;
    }

//============================================================================//

    /**
     * Подготавливает значение $value поля $field для вставки в запрос
     *
     * @param string $field
     * @param string $value
     *
     * @return string
     */
    public function prepareValue($field, $value)
    {
        // если значение поля - массив,
        // то преобразуем его в YAML
        if ( is_array($value) ) {
            $value = "'" . $this->escape(model::arrayToYaml($value)) . "'";
        }
        else

        // если это поле даты публикации и оно не установлено,
        // то используем текущее время
        if ( strpos($field, 'date_') === 0 && ($value === false) ) {
            $value = "NULL";
        }
        else
        if ( strpos($field, 'date_') === 0 && ($value == '' || is_null($value)) ) {
            $value = "CURRENT_TIMESTAMP";
        }
        else

        // если это поле булево,
        // то преобразуем его в число
        if ( is_bool($value) ) {
            $value = (int) $value;
        }
        else

        // если значение поля не задано,
        // то запишем в базу NULL
        if ( $value === '' || is_null($value) ) {
            $value = 'NULL';
        }
        else {
            $value = $this->escape(trim($value));
            $value = "'{$value}'";
        }

        return $value;
    }

//============================================================================//

    /**
     * Возвращает строку в формате '`' . $field . '` = ' . $value для SET запроса mysql
     *
     * @param string $table Таблица
     * @param array $data Массив[Название поля] = значение поля
     * @param bool $skip_check_fields Не проверять наличие обновляемых полей
     *
     * @return boolean|string
     */
    public function getSetString($table, $data, $skip_check_fields = false)
    {
        if ( empty($data) || !is_array($data) ) {
            return false;
        }

        if ( !$skip_check_fields ) {
            $table_fields = $this->getTableFields($table);
        }

        $set = [];

        foreach ( $data as $field => $value ) {
            if ( !$skip_check_fields && !in_array($field, $table_fields) ) {
                continue;
            }

            $value = $this->prepareValue($field, $value);
            $set[] = '`' . $field . '` = ' . $value;
        }

        return !empty($set) ? implode(', ', $set) : false;
    }

    /**
     * Выполняет запрос UPDATE
     *
     * @param string $table Таблица
     * @param string $where Критерии запроса
     * @param array $data Массив[Название поля] = значение поля
     * @param bool $skip_check_fields Не проверять наличие обновляемых полей
     *
     * @return boolean
     */
    public function update($table, $where, $data, $skip_check_fields = false)
    {
        $set = $this->getSetString($table, $data, $skip_check_fields);

        if ( !empty($set) ) {
            $sql = 'UPDATE {#}' . $table . ' i SET ' . $set . ' WHERE ' . $where;

            if ( $this->query($sql) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Выполняет запрос INSERT
     *
     * @param string $table Таблица
     * @param array $data Массив[Название поля] = значение поля
     * @param bool $skip_check_fields Не проверять наличие обновляемых полей
     *
     * @return bool
     */
    public function insert($table, $data, $skip_check_fields = false)
    {
        $set = $this->getSetString($table, $data, $skip_check_fields);

        if ( !empty($set) ) {
            $sql = 'INSERT INTO {#}' . $table . ' SET ' . $set;

            if ( $this->query($sql) ) {
                return $this->lastId();
            }
        }

        return false;
    }

    /**
     * Выполняет запрос INSERT
     * при совпадении PRIMARY или UNIQUE ключа выполняет UPDATE вместо INSERT
     *
     * @param string $table Таблица
     * @param array $data Массив данных для вставки в таблицу
     * @param array $update_data Массив данных для обновления при совпадении ключей
     *
     * @return bool
     */
    public function insertOrUpdate($table, $data, $update_data = false, $skip_check_fields = false)
    {
        $set     = $this->getSetString($table, $data, $skip_check_fields);
        $upd_set = $this->getSetString($table, $update_data, $skip_check_fields);

        if ( !empty($set) ) {
            $sql = 'INSERT INTO {#}' . $table . ' SET ' . $set . ' ON DUPLICATE KEY UPDATE ' . (!empty($upd_set) ? $upd_set : $set);

            if ( $this->query($sql) ) {
                return $this->lastId();
            }
        }

        return false;
    }

    /**
     * Выполняет запрос DELETE
     *
     * @param string $table_name Таблица
     * @param string $where Критерии запроса
     *
     * @return type
     */
    public function delete($table_name, $where, $limit = false)
    {
        $where = str_replace('i.', '', $where);
        return $this->query('DELETE FROM {#}' . $table_name . ' WHERE ' . $where . ($limit ? ' LIMIT ' . $limit : ''));
    }

    /**
     * Позволяет быстро изменить значения одного столбца таблицы
     *
     * @param string $table_name Таблица
     * @param string $field_name Название столбца
     * @param string $field_value Новое значение
     * @param string $where Условие
     * @param int|bool $limit Лимит
     *
     * @return $this
     */
    public function changeValue($table_name, $field_name, $field_value, $where, $limit = 1)
    {
        $this->query('UPDATE {#}' . $table_name . ' SET `' . $field_name . "` = '" . $this->escape($field_value) . "' WHERE " . $where . ($limit ? ' LIMIT ' . $limit : ''));
        return $this;
    }

//============================================================================//

    /**
     * Возвращает массив со всеми строками полученными после запроса
     *
     * @param string $table_name
     * @param string $where
     * @param string $fields
     * @param string $order
     *
     * @return boolean|array
     */
    public function getRows($table_name, $where = '1', $fields = '*', $order = 'id ASC', $quiet = false)
    {
        $sql    = 'SELECT ' . $fields . ' FROM {#}' . $table_name . ' WHERE ' . $where . ' ORDER BY ' . $order;
        $result = $this->query($sql, false, $quiet);

        if ( !$this->mysqli->errno ) {
            $data = [];

            while ( $item = $this->fetchAssoc($result) ) {
                $data[] = $item;
            }

            $this->freeResult($result);

            return $data;
        }
        else {
            return false;
        }
    }

    /**
     * Возвращает массив с одной строкой из базы
     *
     * @param string $table
     * @param string $where
     * @param string $fields
     * @param string $order
     *
     * @return boolean|array
     */
    public function getRow($table, $where = '1', $fields = '*', $order = '')
    {
        $sql = 'SELECT ' . $fields . ' FROM {#}' . $table . ' WHERE ' . $where;

        if ( $order ) {
            $sql .= ' ORDER BY ' . $order;
        }

        $sql    .= ' LIMIT 1';
        $result = $this->query($sql);

        if ( $result ) {
            $data = $this->fetchAssoc($result);
            $this->freeResult($result);
            return $data;
        }
        else {
            return false;
        }
    }

//============================================================================//

    /**
     * Возвращает одно поле из таблицы в базе
     *
     * @param string $table
     * @param string $where
     * @param string $field
     * @param string $order
     *
     * @return mixed
     */
    public function getField($table, $where, $field, $order = '')
    {
        $row = $this->getRow($table, $where, $field, $order);
        return isset($row[$field]) ? $row[$field] : false;
    }

    public function getFields($table, $where, $fields = '*', $order = '')
    {
        return $this->getRow($table, $where, $fields, $order);
    }

//============================================================================//

    /**
     * Возвращает количество строк выведенных запросом
     *
     * @param string $table
     * @param string $where
     * @param int $limit
     *
     * @return boolean|int
     */
    public function getRowsCount($table, $where = '1', $limit = false)
    {
        $sql = 'SELECT COUNT(1) FROM {#}' . $table . ' WHERE ' . $where;

        if ( $limit ) {
            $sql .= ' LIMIT ' . $limit;
        }

        $result = $this->query($sql);

        if ( $result ) {
            $row   = $this->fetchRow($result);
            $count = $row[0];
            $this->freeResult($result);
            return $count;
        }
        else {
            return false;
        }
    }

//============================================================================//

    /**
     * Расставляет правильные порядковые номера (ordering) у элементов NS
     *
     * @param string $table
     */
    public function reorderNS($table)
    {
        $sql    = 'SELECT * FROM {#}' . $table . ' ORDER BY ns_left';
        $result = $this->query($sql);

        if ( $this->numRows($result) ) {
            $level = [];

            while ( $item = $this->fetchAssoc($result) ) {
                if ( isset($level[$item['ns_level']]) ) {
                    $level[$item['ns_level']] += 1;
                }
                else {
                    $level[] = 1;
                }

                $this->query('UPDATE ' . $table . ' SET ordering = ' . $level[$item['ns_level']] . ' WHERE id=' . $item['id']);
            }
        }

        $this->freeResult($result);
    }

//============================================================================//

    public function createTable($table_name, $structure, $engine = 'MYISAM')
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `{#}' . $table_name . '` (' . PHP_EOL;

        $fcount = 0;
        $ftotal = sizeof($structure);

        $indexes         = $fulltext        = $unique          = $indexes_created = [];

        foreach ( $structure as $name => $field ) {
            $fcount++;
            $sep = ($fcount == $ftotal) ? PHP_EOL : ',' . PHP_EOL;

            $default  = (!isset($field['default']) ? 'NULL' : "NOT NULL DEFAULT '" . $field['default'] . "'");
            $unsigned = (!isset($field['unsigned']) ? '' : 'UNSIGNED');

            // обычный индекс
            if ( isset($field['index']) ) {
                if ( $field['index'] === true ) {
                    $indexes[$name] = [ $name ];
                }
                else if ( is_string($field['index']) ) {
                    $indexes[$field['index']][$field['composite_index']] = $name;
                }
                else if ( is_array($field['index']) ) {
                    foreach ( $field['index'] as $k => $i_name ) {
                        $indexes[$i_name][$field['composite_index'][$k]] = $name;
                    }
                }
            }
            // уникальный индекс
            if ( isset($field['unique']) ) {
                $unique[] = $name;
            }
            // полнотекстовый индекс
            if ( isset($field['fulltext']) ) {

                if ( $field['fulltext'] === true ) {
                    $fulltext[$name] = [ $name ];
                }
            }

            switch ( $field['type'] ) {
                case 'primary':
                    $sql .= "\t`" . $name . '` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' . $sep;
                    break;

                case 'bool':
                    $sql .= "\t`" . $name . '` tinyint(1) UNSIGNED ' . $default . $sep;
                    break;

                case 'timestamp':
                    $current = (isset($field['default_current']) && $field['default_current'] == true) ? "NOT NULL DEFAULT CURRENT_TIMESTAMP" : 'NULL';
                    $sql     .= "\t`" . $name . '` TIMESTAMP ' . $current . $sep;
                    break;

                case 'tinyint':
                    $sql .= "\t`" . $name . '` TINYINT ' . $unsigned . ' ' . $default . $sep;
                    break;

                case 'int':
                    if ( !isset($field['size']) ) {
                        $field['size'] = 11;
                    }
                    $sql .= "\t`" . $name . '` INT( ' . $field['size'] . ' ) ' . $unsigned . ' ' . $default . $sep;
                    break;

                case 'varchar':
                    if ( !isset($field['size']) ) {
                        $field['size'] = 255;
                    }
                    $sql .= "\t`" . $name . '` VARCHAR( ' . $field['size'] . ' ) ' . $default . $sep;
                    break;

                case 'text':
                    $sql .= "\t`" . $name . "` TEXT NULL" . $sep;
                    break;

                case 'set':
                    $values = array_map(function($item) {
                        return '"' . $item . '"';
                    }, $field['items']);
                    $values = implode(',', $values);
                    $sql    .= "\t`" . $name . '` SET (' . $values . ') ' . $default . $sep;
                    break;

                default: break;
            }
        }

        $sql .= ') ENGINE=' . $engine . ' DEFAULT CHARSET=utf8';

        $this->query($sql);

        foreach ( $indexes as $index_name => $fields ) {
            if ( in_array($index_name, $indexes_created) ) {
                continue;
            }

            $this->addIndex($table_name, $fields, $index_name);

            $indexes_created[] = $index_name;
        }

        foreach ( $unique as $field ) {
            if ( in_array($field, $indexes_created) ) {
                continue;
            }

            $this->addIndex($table_name, $field, $field, 'UNIQUE');

            $indexes_created[] = $field;
        }

        foreach ( $fulltext as $index_name => $fields ) {
            if ( in_array($index_name, $indexes_created) ) {
                continue;
            }

            $this->addIndex($table_name, $fields, $index_name, 'FULLTEXT');

            $indexes_created[] = $index_name;
        }
    }

//============================================================================//

    public function createCategoriesTable($table_name)
    {
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

        $this->nestedSets->setTable($table_name);
        $this->nestedSets->addRootNode();

        return true;
    }

    public function createCategoriesBindsTable($table_name)
    {
        $sql = "CREATE TABLE `{#}" . $table_name . "` (
                  `item_id` int(11) UNSIGNED DEFAULT NULL,
                  `category_id` int(11) UNSIGNED DEFAULT NULL,
                  KEY `item_id` (`item_id`),
                  KEY `category_id` (`category_id`)
                ) ENGINE=" . $this->options['db_engine'] . " DEFAULT CHARSET=utf8";

        $this->query($sql);

        return true;
    }

//============================================================================//

    public function dropTable($table_name)
    {
        $sql = 'DROP TABLE IF EXISTS `{#}' . $table_name . '`';

        $this->query($sql);
    }

    public function dropTableField($table_name, $field_name)
    {
        $sql = 'ALTER TABLE `{#}' . $table_name . '` DROP `' . $field_name . '`';

        $this->query($sql);
    }

    public function isTableExists($table_name)
    {
        $this->query('SELECT 1 FROM `{#}' . $table_name . '` LIMIT 1', false, true);

        if ( $this->mysqli->errno ) {
            return false;
        }

        return true;
    }

//============================================================================//

    public function isFieldUnique($table_name, $field_name, $value, $exclude_row_id = false)
    {
        $value = $this->escape(trim($value));

        $where = '(`' . $field_name . "` = '" . $value . "')";

        if ( $exclude_row_id ) {
            $where .= " AND (id <> '" . $exclude_row_id . "')";
        }

        return !(bool) $this->getRowsCount($table_name, $where, 1);
    }

    public function isFieldExists($table_name, $field)
    {
        $table_fields = $this->getTableFields($table_name);

        return in_array($field, $table_fields, true);
    }

//============================================================================//
    /**
     * Возвращает поля, участвующие в индексе или false, если индекса нет
     * если индекс составной, то поля будут упорядочены в массиве как в индексе
     *
     * @param string $table Название таблицы без префикса
     * @param string $index_name Название индекса
     *
     * @return array|boolean
     */
    public function getIndex($table, $index_name)
    {
        $result = $this->query('SHOW INDEX FROM  `{#}' . $table . "` WHERE `Key_name` =  '" . $index_name . "'");

        if ( $this->numRows($result) ) {
            $fields = [];

            while ( $i = $this->fetchAssoc($result) ) {
                $fields[] = $i['Column_name'];
            }

            return $fields;
        }
        else {
            return false;
        }
    }

    /**
     * Возвращает все индексы таблицы
     *
     * @param string $table Название таблицы без префикса
     * @param string $index_type Тип индекса
     *
     * @return boolean|array
     */
    public function getTableIndexes($table, $index_type = null)
    {
        $sql = 'SHOW INDEX FROM  `{#}' . $table . '`';

        if ( $index_type ) {
            $sql .= " WHERE `Index_type` = '" . $index_type . "'";
        }

        $result = $this->query($sql);

        if ( $this->numRows($result) ) {
            $indexes = [];

            while ( $i = $this->fetchAssoc($result) ) {
                $indexes[$i['Key_name']][] = $i['Column_name'];
            }

            return $indexes;
        }

        return false;
    }

    /**
     * Проверяет, есть ли указанный индекс в таблице
     *
     * @param string $table Название таблицы без префикса
     * @param string $index_name Название индекса
     *
     * @return boolean
     */
    public function isIndexExists($table, $index_name)
    {
        return $this->getIndex($table, $index_name) !== false;
    }

    /**
     * Удаляет индекс из таблицы, если он там есть
     *
     * @param string $table Название таблицы без префикса
     * @param string $index_name Название индекса
     *
     * @return boolean
     */
    public function dropIndex($table, $index_name)
    {
        if ( $this->isIndexExists($table, $index_name) ) {
            $this->query('ALTER TABLE `{#}' . $table . '` DROP INDEX `' . $index_name . '`');
            return true;
        }

        return false;
    }

    /**
     * Добавляет индекс к таблице
     *
     * @param string $table Название таблицы без префикса
     * @param array|string $fields Поле или поля, участвующие в индексе
     * @param string $index_name Название индекса. Если не передано, название будет по последнему элементу
     * @param string $index_type Тип индекса
     *
     * @return boolean FALSE если индекс с таким названием уже есть
     */
    public function addIndex($table, $fields, $index_name = '', $index_type = 'INDEX')
    {
        if ( is_string($fields) ) {
            $fields = [ $fields ];
        }

        if ( !$index_name ) {
            $index_name = end($fields);
        }

        if ( $this->isIndexExists($table, $index_name) ) {
            return false;
        }

        ksort($fields);

        $fields_str = '`' . implode('`, `', $fields) . '`';

        $this->query('ALTER TABLE `{#}' . $table . '` ADD ' . $index_type . ' `' . $index_name . '` (' . $fields_str . ')');

        return true;
    }

//============================================================================//

    public function importDump($file, $delimiter = ';')
    {
        if ( !is_file($file) ) {
            return false;
        }

        @set_time_limit(0);

        $file = fopen($file, 'r');

        $query = [];

        while ( feof($file) === false ) {
            $query[] = fgets($file);

            if ( preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1 ) {
                $query = trim(implode('', $query));

                $result = $this->query(str_replace([ 'InnoDB', '#__' ], [ $this->options['db_engine'], '{#}' ], $query));

                if ( $result === false ) {
                    return false;
                }
            }

            if ( is_string($query) === true ) {
                $query = [];
            }
        }

        fclose($file);

        return true;
    }

}
