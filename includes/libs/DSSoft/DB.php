<?php

namespace DSSoft;

/**
 * Класс для работы с базой данных.
 *
 * Инкапсулирует подключение через драйвер (по умолчанию MySQL), построение
 * запросов SELECT/INSERT/UPDATE/DELETE, систему фильтров, JOIN, работу
 * с индексами и транзакциями.
 */
class DB {
    /**
     * Типы JOIN
     */
    const LEFT_JOIN = 'LEFT JOIN';
    const RIGHT_JOIN = 'RIGHT JOIN';
    const INNER_JOIN = 'INNER JOIN';
    const STRAIGHT_JOIN = 'STRAIGHT_JOIN';
    const LEFT_OUTER_JOIN = 'LEFT OUTER JOIN';
    const RIGHT_OUTER_JOIN = 'RIGHT OUTER JOIN';

    /**
     * Уровни изоляций транзакций
     */
    const READ_UNCOMMITTED = 'READ UNCOMMITTED';
    const READ_COMMITTED = 'READ COMMITTED';
    const REPEATABLE_READ = 'REPEATABLE READ';
    const SERIALIZABLE = 'SERIALIZABLE';

    /**
     * Объект для работы с БД
     * @var DB\Driver\DBInterface
     */
    protected $db;
    /**
     * Тип драйвера базы данных.
     *
     * @var string
     */
    protected $db_type;
    /**
     * Хост сервера базы данных.
     *
     * @var string
     */
    protected $db_host;
    /**
     * Имя пользователя базы данных.
     *
     * @var string
     */
    protected $db_user;
    /**
     * Пароль пользователя базы данных.
     *
     * @var string
     */
    protected $db_password;
    /**
     * Название базы данных.
     *
     * @var string|null
     */
    protected $db_name;
    /**
     * Дополнительные опции подключения (префикс таблиц, кодировка, порт и т.п.).
     *
     * @var array
     */
    protected $options;

    /**
     * Объект класса для отладки работы с БД
     * @var DB\Debug
     */
    protected $_debug;
    /**
     * Кеш вспомогательных данных (например, списка полей таблиц).
     *
     * @var array
     */
    protected $_cache = array('table_fields' => array());

    /**
     * Стек состояний filter_on для вложенных групп filterStart()/filterEnd()
     * @var array
     */
    protected $_filter_stack = array();
    //========================================================================//
    /**
     * Название таблицы для метода getSQL().
     *
     * @var string
     */
    public $table = '';
    /**
     * Список полей для SELECT.
     *
     * @var array
     */
    public $select = array('i.*');
    /**
     * Условие GROUP BY.
     *
     * @var string
     */
    public $group_by = '';
    /**
     * Условие ORDER BY.
     *
     * @var string
     */
    public $order_by = '';
    /**
     * Накопленное условие WHERE.
     *
     * @var string
     */
    public $where = '';
    /**
     * Разделитель для следующего условия (AND или OR).
     *
     * @var string
     */
    public $where_separator = 'AND';
    /**
     * Подсказки индексов (FORCE/USE/IGNORE INDEX).
     *
     * @var string
     */
    public $index_action = '';
    /**
     * Ограничение выборки (LIMIT).
     *
     * @var string|integer
     */
    public $limit = 1000;
    /**
     * Флаг DISTINCT: строка «DISTINCT» или пустая строка.
     *
     * @var string
     */
    public $distinct = '';
    /**
     * Флаг STRAIGHT_JOIN.
     *
     * @var string
     */
    public $straight_join = '';
    /**
     * Накопленные JOIN-выражения.
     *
     * @var string
     */
    public $join = '';
    /**
     * Тип блокировки чтения (FOR UPDATE, LOCK IN SHARE MODE).
     *
     * @var string
     */
    public $read_type = '';
    /**
     * Количество записей на страницу.
     *
     * @var integer
     */
    public $perpage = 50;
    /**
     * Количество записей на страницу по умолчанию.
     *
     * @var integer
     */
    public $default_perpage = 50;
    /**
     * Признак открытой транзакции.
     *
     * @var boolean
     */
    public $is_transaction_started = false;
    /**
     * Признак того, что в WHERE уже есть хотя бы одно условие.
     *
     * @var boolean
     */
    public $filter_on = false;
    /**
     * Не сбрасывать фильтры при вызове resetFilters().
     *
     * @var boolean
     */
    public $keep_filters = false;
    /**
     * Не прерывать выполнение при ошибке запроса.
     *
     * @var boolean
     */
    public $query_quiet = false;

    /**
     * Создаёт объект и сразу подключается к базе данных.
     *
     * @param string $host Хост сервера БД
     * @param string $user Имя пользователя
     * @param string $password Пароль
     * @param string|null $dbname Название базы данных
     * @param string $type Тип драйвера (класс в пространстве имён DSSoft\DB\Driver)
     * @param array $options Дополнительные опции подключения
     */
    public function __construct($host, $user, $password, $dbname = null, $type = 'MySQL', $options = array()) {
        $this->db_type = $type;
        $this->db_host = $host;
        $this->db_user = $user;
        $this->db_password = $password;
        $this->db_name = $dbname;
        $this->options = $options;
        $this->_debug = new DB\Debug();

        if (empty($this->options['table_prefix'])) {
            $this->options['table_prefix'] = '';
        }

        $this->connect();
    }

    /**
     * Подключается к базе данных через выбранный драйвер.
     *
     * @return boolean TRUE при успешном подключении
     */
    protected function connect() {
        $class_name = '\\DSSoft\\DB\\Driver\\' . $this->db_type;

        if (!class_exists($class_name)) {
            return false;
        }

        $this->_debug->startTimer('CONNECT_DB');

        $this->db = new $class_name();

        if (!$this->db instanceof DB\Driver\DBInterface) {
            $this->db = null;
            $this->_debug->error('CONNECT_DB', array('msg' => 'Driver ' . $class_name . ' does not implement DBInterface'));
            return false;
        }

        $this->db->connect($this->db_host, $this->db_user, $this->db_password, $this->db_name, $this->options);

        if ($this->db->ConError()) {
            $this->_debug->error('CONNECT_DB', array('msg' => $this->db->ConError()));
            return false;
        } else {
            $this->_debug->log('CONNECT_DB', array('msg' => 'SUCCESS'));
            return true;
        }
    }

    /**
     * Возвращает состояние соединения с базой данных.
     *
     * @return boolean TRUE, если соединение активно
     */
    public function status() {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return false;
        }
        return $this->db->status();
    }

    /**
     * Переподключается к базе данных.
     *
     * @param boolean $forced Переподключаться принудительно, даже если соединение активно
     * @return boolean
     */
    public function reconnect($forced = false) {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return false;
        }

        if ($forced === true || !$this->db->status()) {
            $this->db->reconnect();
        }

        return true;
    }

    /**
     * Возвращает код последней ошибки базы данных.
     *
     * @return integer Код ошибки или 0, если ошибки не было
     */
    public function ErrorNo() {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return 0;
        }

        return (int) $this->db->ErrorNo();
    }

    /**
     * Возвращает текст ошибки подключения.
     *
     * @return string|null Текст ошибки или NULL, если подключение успешно
     */
    public function ConError() {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return 'Database driver is not connected';
        }

        return $this->db->ConError();
    }

    /**
     * Закрывает соединение с базой данных при уничтожении объекта.
     */
    public function __destruct() {
        if ($this->db instanceof DB\Driver\DBInterface) {
            $this->db->close();
        }
    }

    //========================================================================//

    /**
     * Возвращает все названия полей для таблицы
     * @param string $table
     * @return array
     */
    public function getTableFields($table) {
        if (isset($this->_cache['table_fields'][$table])) {
            return $this->_cache['table_fields'][$table];
        }

        $result = $this->query("SHOW COLUMNS FROM `{#}" . $table . "`");

        $fields = array();

        if ($result) {
            while ($data = $result->fetchAssoc()) {
                $fields[] = $data['Field'];
            }
        }

        // не кешируем пустой результат: таблицы может ещё не быть
        if ($fields) {
            $this->_cache['table_fields'][$table] = $fields;
        }

        return $fields;
    }

    /**
     * Возвращает названия полей и их типы для таблицы
     * @param string $table
     * @return array
     */
    public function getTableFieldsTypes($table) {
        $result = $this->query("SELECT DATA_TYPE, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{#}" . $table . "'");

        $fields = [];

        if ($result) {
            while ($data = $result->fetchAssoc()) {
                $fields[$data['COLUMN_NAME']] = $data['DATA_TYPE'];
            }
        }

        return $fields;
    }

    //========================================================================//

    /**
     * Выполняет запрос в базе
     *
     * @param string $sql Строка запроса
     * @param array|string $params Аргументы запроса, которые будут переданы в vsprintf
     * @param boolean $quiet В случае ошибки запроса отдавать false, а не "умирать"
     *
     * @return boolean|DB\Result\ResultInterface
     */
    public function query($sql, $params = false, $quiet = false) {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return false;
        }

        $this->_debug->startTimer('DB_QUERY');

        $sql = $this->replacePrefix($sql);

        if ($params) {
            if (!is_array($params)) {
                $params = array($params);
            }

            foreach ($params as $index => $param) {
                if ($param === null) {
                    $params[$index] = 'NULL';
                } elseif (is_bool($param)) {
                    $params[$index] = $param ? '1' : '0';
                } elseif (!is_numeric($param)) {
                    $params[$index] = $this->db->escape($param);
                }
            }

            $sql = vsprintf($sql, $params);
        }

        $result = $this->db->query($sql);

        if ($result === false && $this->db->Error()) {
            if ($quiet || $this->query_quiet === true) {
                $this->_debug->error('DB_QUERY', array('msg' => $this->db->Error(), 'sql' => $sql));
                return false;
            }
        } else {
            $this->_debug->log('DB_QUERY', array('sql' => $sql));
            return $result;
        }

        exit($this->db->Error() . "\n\nSQL:\n\n" . $sql);
    }

    //========================================================================//

    /**
     * Добавляет в SELECT флаг DISTINCT.
     *
     * @return $this
     */
    public function distinctSelect() {
        $this->distinct = 'DISTINCT';

        return $this;
    }

    /**
     * Добавляет поле в список выбираемых полей.
     *
     * @param string $field Поле или SQL-выражение
     * @param string|false $as Псевдоним поля
     * @return $this
     */
    public function select($field, $as = false) {
        $this->select[] = $as ? $field . ' as `' . $as . '`' : $field;

        return $this;
    }

    /**
     * Заменяет список выбираемых полей одним полем.
     *
     * @param string $field Поле или SQL-выражение
     * @param string|false $as Псевдоним поля
     * @return $this
     */
    public function selectOnly($field, $as = false) {
        $this->select = [];
        return $this->select($field, $as);
    }

    //========================================================================//

    /**
     * Возвращает значение одного поля записи по её идентификатору.
     *
     * @param string $table_name Название таблицы
     * @param integer|string $row_id Идентификатор записи
     * @param string $field_name Название поля
     * @return mixed Значение поля или FALSE, если запись не найдена
     */
    public function getField($table_name, $row_id, $field_name) {
        $this->filterEqual('id', $row_id);
        return $this->getFieldFiltered($table_name, $field_name);
    }

    /**
     * Возвращает значение одного поля первой записи, подходящей под текущие фильтры.
     *
     * @param string $table_name Название таблицы
     * @param string $field_name Название поля
     * @return mixed Значение поля или FALSE, если записей нет
     */
    public function getFieldFiltered($table_name, $field_name) {
        $this->select = array($this->prepareFieldName($field_name));

        $this->table = $table_name;

        $this->limit(1);

        $sql = $this->getSQL();

        $this->resetFilters();

        $result = $this->query($sql);

        if ($result === false || !$result->rowsCount()) {
            return false;
        }

        return $result->fetchRow()[0];
    }

    //========================================================================//

    /**
     * Возвращает одну запись, подходящую под текущие фильтры.
     *
     * @param string $table_name Название таблицы
     * @param callable|false $item_callback Функция обработки записи
     * @return array|false Ассоциативный массив записи или FALSE
     */
    public function getItem($table_name, $item_callback = false) {
        $select = implode(', ', $this->select);

        $sql = "SELECT {$this->distinct} {$this->straight_join} {$select}
                FROM {#}{$table_name} i
                {$this->index_action}";

        if ($this->join) {
            $sql .= $this->join;
        }

        if ($this->where) {
            $sql .= 'WHERE ' . $this->where . PHP_EOL;
        }

        if ($this->group_by) {
            $sql .= 'GROUP BY ' . $this->group_by . PHP_EOL;
        }

        if ($this->order_by) {
            $sql .= 'ORDER BY ' . $this->order_by . PHP_EOL;
        }

        $sql .= 'LIMIT 1';

        if ($this->read_type) {
            $sql .= PHP_EOL . $this->read_type;
        }

        $this->resetFilters();

        $result = $this->query($sql);

        if ($result === false || !$result->rowsCount()) {
            return false;
        }

        $item = $result->fetchAssoc();

        if (is_callable($item_callback)) {
            $item = call_user_func_array($item_callback, array($item, $this));
        }

        return $item;
    }

    /**
     * Возвращает запись по её идентификатору.
     *
     * @param string $table_name Название таблицы
     * @param integer|string $id Идентификатор записи
     * @param callable|false $item_callback Функция обработки записи
     * @return array|false
     */
    public function getItemById($table_name, $id, $item_callback = false) {
        $this->filterEqual('id', $id);
        return $this->getItem($table_name, $item_callback);
    }

    /**
     * Возвращает запись по значению указанного поля.
     *
     * @param string $table_name Название таблицы
     * @param string $field_name Название поля
     * @param mixed $field_value Значение поля
     * @param callable|false $item_callback Функция обработки записи
     * @return array|false
     */
    public function getItemByField($table_name, $field_name, $field_value, $item_callback = false) {
        $this->filterEqual($field_name, $field_value);
        return $this->getItem($table_name, $item_callback);
    }

    //========================================================================//

    /**
     * Возвращает количество записей, подходящих под текущие фильтры.
     *
     * @param string $table_name Название таблицы
     * @param string $by_field Поле для COUNT()
     * @param boolean $reset Сбросить фильтры после выполнения запроса
     * @return integer
     */
    public function getCount($table_name, $by_field = 'id', $reset = false) {
        $sql = "SELECT {$this->straight_join} COUNT({$this->distinct} i.{$by_field} )
                FROM {#}{$table_name} i
                {$this->index_action}";

        if ($this->join) {
            $sql .= $this->join;
        }

        if ($this->where) {
            $sql .= 'WHERE ' . $this->where . PHP_EOL;
        }

        if ($this->group_by) {
            $sql .= 'GROUP BY ' . $this->group_by . PHP_EOL;
        }

        if ($reset) {
            $this->resetFilters();
        }

        $result = $this->query($sql);

        if ($result === false || !$result->rowsCount()) {
            $count = 0;
        } else {
            $count = (int) $result->fetchRow()[0];
        }

        return $count;
    }

    //========================================================================//

    /**
     * Возвращает записи из базы, применяя все наложенные ранее фильтры
     * @return array
     */
    public function get($table_name, $item_callback = false, $key_field = 'id') {
        $this->table = $table_name;

        $items = array();

        $sql = $this->getSQL();

        $this->resetFilters();

        $result = $this->query($sql);

        if ($result === false || !$result->rowsCount()) {
            return $items;
        }

        while ($item = $result->fetchAssoc()) {
            $key = ($key_field && isset($item[$key_field])) ? $item[$key_field] : false;

            if (is_callable($item_callback)) {
                $item = call_user_func_array($item_callback, array($item, $this));

                if ($item === false) {
                    continue;
                }
            }

            if ($key !== false) {
                $items[$key] = $item;
            } else {
                $items[] = $item;
            }
        }

        return $items;
    }

    //========================================================================//

    /**
     * Удаляет запись по значению ключевого поля.
     *
     * @param string $table_name Название таблицы
     * @param integer|string $id Значение ключевого поля
     * @param string $by_field Ключевое поле
     * @return boolean
     */
    public function delete($table_name, $id, $by_field = 'id') {
        $this->filterEqual($by_field, $id);

        return $this->deleteFiltered($table_name);
    }

    /**
     * Удаляет записи, подходящие под текущие фильтры.
     *
     * @param string $table_name Название таблицы
     * @return boolean FALSE, если фильтры не заданы
     */
    public function deleteFiltered($table_name) {
        $where = $this->where;

        $this->resetFilters();

        if (!$where) {
            return false;
        }

        return $this->_delete($table_name, $where);
    }

    /**
     * Выполняет запрос DELETE
     * @param string $table_name Таблица
     * @param string $where Критерии запроса
     * @return boolean
     */
    public function _delete($table_name, $where) {
        $where = (string) $where;

        if ($where === '') {
            return false;
        }

        // фильтры строятся с алиасом "i", поэтому удаляем через многотабличный
        // синтаксис DELETE: он позволяет использовать алиас и не портит
        // строковые значения заменой "i."
        return $this->query("DELETE i FROM {#}" . $table_name . " i WHERE " . $where);
    }

    //========================================================================//

    /**
     * Обновляет запись по её идентификатору.
     *
     * @param string $table_name Название таблицы
     * @param integer|string $id Идентификатор записи
     * @param array $data Массив «поле => значение»
     * @param boolean $skip_check_fields Не проверять наличие полей в таблице
     * @param string $array_as Способ сериализации массивов: json, yaml, serialize
     * @return boolean
     */
    public function update($table_name, $id, $data, $skip_check_fields = false, $array_as = 'json') {
        $this->filterEqual('id', $id);

        return $this->updateFiltered($table_name, $data, $skip_check_fields, $array_as);
    }

    /**
     * Обновляет записи, подходящие под текущие фильтры.
     *
     * @param string $table_name Название таблицы
     * @param array $data Массив «поле => значение»
     * @param boolean $skip_check_fields Не проверять наличие полей в таблице
     * @param string $array_as Способ сериализации массивов: json, yaml, serialize
     * @return boolean FALSE, если фильтры не заданы
     */
    public function updateFiltered($table_name, $data, $skip_check_fields = false, $array_as = 'json') {
        $where = $this->where;

        $this->resetFilters();

        if (!$where) {
            return false;
        }

        return $this->_update($table_name, $where, $data, $skip_check_fields, $array_as);
    }

    /**
     * Выполняет запрос UPDATE
     *
     * @param string $table Таблица
     * @param string $where Критерии запроса
     * @param array $data Массив[Название поля] = значение поля
     * @param boolean $skip_check_fields Не проверять наличие обновляемых полей
     * @param string $array_as Преобразовать массив в строку с помошью json, yaml, serialize
     *
     * @return boolean
     */
    public function _update($table, $where, $data, $skip_check_fields = false, $array_as = 'json') {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        if (!$where) {
            return false;
        }

        if (!$skip_check_fields) {
            $table_fields = $this->getTableFields($table);
        }

        $set = [];

        foreach ($data as $field => $value) {
            if (!$skip_check_fields && !in_array($field, $table_fields, true)) {
                continue;
            }

            $set[] = "`" . $field . "` = " . $this->prepareValue($field, $value, $array_as);
        }

        if (!$set) {
            return false;
        }

        return $this->query("UPDATE {#}" . $table . " i SET " . implode(', ', $set) . " WHERE " . $where);
    }

    /**
     * Возвращает количество строк, затронутых последним запросом.
     *
     * @return integer
     */
    public function getAffectedRows() {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return 0;
        }

        return $this->db->affectedRows();
    }

    //========================================================================//

    /**
     * Выполняет запрос INSERT
     *
     * @param string $table Таблица
     * @param array $data Массив[Название поля] = значение поля
     * @param boolean $skip_check_fields Не проверять наличие обновляемых полей
     * @param boolean $array_as Преобразовать массив в строку с помошью json, yaml, serialize
     * @param boolean $ignore Пропускать записи, если при вставке возникают ошибки (INSERT IGNORE)
     * @return boolean|int|string ID вставленной записи
     */
    public function insert($table, $data, $skip_check_fields = false, $array_as = 'json', $ignore = false) {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        if (!$skip_check_fields) {
            $table_fields = $this->getTableFields($table);
        }

        $fields = $values = [];

        foreach ($data as $field => $value) {
            if (!$skip_check_fields && !in_array($field, $table_fields)) {
                continue;
            }

            $fields[] = "`" . $field . "`";
            $values[] = $this->prepareValue($field, $value, $array_as);
        }

        if (!$fields) {
            return false;
        }

        $sql = "INSERT " . ($ignore ? 'IGNORE ' : '') . "INTO {#}" . $table . " (" . implode(', ', $fields) . ")\nVALUES (" . implode(', ', $values) . ")";

        if ($this->query($sql)) {
            return $this->db->lastId();
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
     * @param boolean $skip_check_fields Не проверять наличие обновляемых полей
     * @param string $array_as Преобразовать массив в строку с помошью json, yaml, serialize
     * @return boolean|int|string
     */
    public function insertOrUpdate($table, $data, $update_data = false, $skip_check_fields = false, $array_as = 'json') {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        if (!$skip_check_fields) {
            $table_fields = $this->getTableFields($table);
        }

        $fields = $values = $set = [];

        foreach ($data as $field => $value) {
            if (!$skip_check_fields && !in_array($field, $table_fields, true)) {
                continue;
            }

            $value = $this->prepareValue($field, $value, $array_as);

            $fields[] = "`" . $field . "`";
            $values[] = $value;

            if ($update_data === false) {
                $set[] = "`" . $field . "` = " . $value;
            }
        }

        if (!$fields) {
            return false;
        }

        if (is_array($update_data)) {
            foreach ($update_data as $field => $value) {
                if (!$skip_check_fields && !in_array($field, $table_fields, true)) {
                    continue;
                }

                $set[] = "`" . $field . "` = " . $this->prepareValue($field, $value, $array_as);
            }
        }

        $sql = "INSERT INTO {#}" . $table . " (" . implode(', ', $fields) . ")\nVALUES (" . implode(', ', $values) . ")";

        // без списка обновляемых полей "ON DUPLICATE KEY UPDATE" — синтаксическая ошибка
        if ($set) {
            $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $set);
        }

        if ($this->query($sql)) {
            return $this->db->lastId();
        }

        return false;
    }

    //========================================================================//

    /**
     * Увеличивает значение числового поля на заданный шаг.
     *
     * @param string $table Название таблицы
     * @param string $field Название поля
     * @param integer $step Шаг (может быть отрицательным)
     * @return boolean|DB\Result\ResultInterface
     */
    public function increment($table, $field, $step = 1) {
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $field)) {
            return false;
        }

        $step = (int) $step;

        $sign = $step > 0 ? '+' : '-';
        $step = abs($step);

        $sql = "UPDATE {#}" . $table . " i " . PHP_EOL . "SET i.`" . $field . "` = i.`" . $field . "` " . $sign . " " . $step . PHP_EOL;

        if ($this->where) {
            $sql .= 'WHERE ' . $this->where;
        }

        $this->resetFilters();

        return $this->query($sql, false, true);
    }

    /**
     * Уменьшает значение числового поля на заданный шаг.
     *
     * @param string $table Название таблицы
     * @param string $field Название поля
     * @param integer $step Шаг
     * @return boolean|DB\Result\ResultInterface
     */
    public function decrement($table, $field, $step = 1) {
        return $this->increment($table, $field, $step * -1);
    }

    //========================================================================//

    /**
     * Заменяет подстроку во всех значениях указанного поля.
     *
     * @param string $table_name Название таблицы
     * @param string $search Искомый текст
     * @param string $replace Текст замены
     * @param string $field Название поля
     * @return boolean|DB\Result\ResultInterface
     */
    public function replaceFieldString($table_name, $search, $replace, $field) {
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $field)) {
            return false;
        }

        $search = $this->db->escape($search);
        $replace = $this->db->escape($replace);

        return $this->query("UPDATE `{#}{$table_name}` SET `{$field}` = REPLACE(`{$field}`, '{$search}', '{$replace}') WHERE `{$field}` LIKE '%{$search}%'");
    }

    //========================================================================//

    /**
     * Задаёт ограничение выборки (LIMIT).
     *
     * @param integer $from Начальная позиция или количество записей
     * @param integer $howmany Количество записей
     * @return $this
     */
    public function limit($from, $howmany = 0) {
        $this->limit = (int) $from;
        $howmany = (int) $howmany;

        if ($this->limit < 0) {
            $this->limit = 0;
        }

        if ($howmany) {
            if ($howmany <= 0) {
                $howmany = 15;
            }

            $this->limit .= ', ' . $howmany;
        }

        return $this;
    }

    /**
     * Задаёт ограничение выборки для указанной страницы.
     *
     * @param integer $page Номер страницы, начиная с 1
     * @param integer $perpage Количество записей на страницу
     * @return $this
     */
    public function limitPage($page, $perpage = 0) {
        $page = (int) $page;
        $perpage = (int) $perpage;

        if ($perpage <= 0) {
            $perpage = $this->perpage;
        }

        $this->limit(($page - 1) * $perpage, $perpage);

        return $this;
    }

    /**
     * Как limitPage(), но запрашивает на одну запись больше.
     *
     * Используется для проверки наличия следующей страницы.
     *
     * @param integer $page Номер страницы, начиная с 1
     * @param integer $perpage Количество записей на страницу
     * @return $this
     */
    public function limitPagePlus($page, $perpage = 0) {
        $page = (int) $page;
        $perpage = (int) $perpage;

        if ($perpage <= 0) {
            $perpage = $this->perpage;
        }

        $this->limit(($page - 1) * $perpage, $perpage + 1);

        return $this;
    }

    /**
     * Устанавливает количество записей на страницу.
     *
     * @param integer $perpage Количество записей на страницу
     * @return $this
     */
    public function setPerPage($perpage) {
        $this->perpage = (int) $perpage;

        return $this;
    }

    //========================================================================//

    /**
     * Сбрасывает накопленные параметры запроса и фильтры.
     *
     * При включённом keep_filters условия WHERE сохраняются.
     *
     * @return $this
     */
    public function resetFilters() {
        $this->select = array('i.*');
        $this->group_by = '';
        $this->order_by = '';
        $this->index_action = '';
        $this->limit = '';
        $this->perpage = $this->default_perpage;
        $this->read_type = '';
        $this->join = '';
        $this->distinct = '';
        $this->straight_join = '';

        if ($this->keep_filters) {
            return $this;
        }

        $this->filter_on = false;
        $this->where = '';
        $this->_filter_stack = array();

        return $this;
    }

    /**
     * Запрещает сброс фильтров в resetFilters().
     *
     * @return $this
     */
    public function lockFilters() {
        $this->keep_filters = true;
        return $this;
    }

    /**
     * Разрешает сброс фильтров в resetFilters().
     *
     * @return $this
     */
    public function unlockFilters() {
        $this->keep_filters = false;
        return $this;
    }

    /**
     * Открывает группу условий.
     *
     * Группа закрывается методом filterEnd().
     *
     * @return $this
     */
    public function filterStart() {
        $where_length = strlen($this->where);

        if ($this->filter_on) {
            $this->where .= ' ' . $this->where_separator . ' (';
        } else {
            $this->where .= '(';
        }

        // запоминаем состояние, чтобы filterEnd() корректно закрыл (или убрал пустую) группу
        $this->_filter_stack[] = array(
            'filter_on' => $this->filter_on,
            'where_length' => $where_length,
            'open_length' => strlen($this->where) - $where_length,
        );

        $this->filter_on = false;
        $this->where_separator = ' AND ';

        return $this;
    }

    /**
     * Закрывает группу условий, открытую filterStart().
     *
     * Пустая группа удаляется целиком, чтобы в SQL не попало «()».
     *
     * @return $this
     */
    public function filterEnd() {
        $group = array_pop($this->_filter_stack);

        // в группе не было ни одного условия: убираем её целиком, иначе в SQL попадёт "()"
        if ($group !== null && strlen($this->where) === $group['where_length'] + $group['open_length']) {
            $this->where = substr($this->where, 0, $group['where_length']);
            $this->filter_on = $group['filter_on'];
            $this->where_separator = ' AND ';

            return $this;
        }

        $this->where .= ' ) ';

        // закрытая группа — такое же условие, как и одиночный filter()
        $this->filter_on = true;
        $this->where_separator = ' AND ';

        return $this;
    }

    /**
     * Устанавливает разделитель AND для следующего условия.
     *
     * @return $this
     */
    public function filterAnd() {
        $this->where_separator = ' AND ';
        return $this;
    }

    /**
     * Устанавливает разделитель OR для следующего условия.
     *
     * @return $this
     */
    public function filterOr() {
        $this->where_separator = ' OR ';
        return $this;
    }

    /**
     * Добавляет произвольное условие в WHERE.
     *
     * @param string $condition Условие на SQL
     * @return $this
     */
    public function filter($condition) {
        if ($this->filter_on) {
            $this->where .= ' ' . $this->where_separator . ' (' . $condition . ')';
        } else {
            $this->where .= '(' . $condition . ')';
            $this->filter_on = true;
        }

        $this->where_separator = ' AND ';

        return $this;
    }

    /**
     * Добавляет условие «поле = значение».
     *
     * При $value === null добавляется условие IS NULL.
     *
     * @param string $field Название поля
     * @param mixed $value Значение
     * @param boolean $binary Использовать бинарное сравнение
     * @return $this
     */
    public function filterEqual($field, $value, $binary = false) {
        if (is_null($value)) {
            return $this->filterIsNull($field);
        }

        return $this->filter(($binary ? ' BINARY ' : '') . $this->prepareFieldName($field) . " = '" . $this->db->escape($value) . "'");
    }

    /**
     * Добавляет условие «поле <> значение».
     *
     * При $value === null добавляется условие IS NOT NULL.
     *
     * @param string $field Название поля
     * @param mixed $value Значение
     * @return $this
     */
    public function filterNotEqual($field, $value) {
        if (is_null($value)) {
            return $this->filterNotNull($field);
        }

        $value = $this->db->escape($value);

        return $this->filter($this->prepareFieldName($field) . " <> '" . $value . "'");
    }

    /**
     * Добавляет условие IS NOT NULL.
     *
     * @param string $field Название поля
     * @return $this
     */
    public function filterNotNull($field) {
        return $this->filter($this->prepareFieldName($field) . ' IS NOT NULL');
    }

    /**
     * Добавляет условие IS NULL.
     *
     * @param string $field Название поля
     * @return $this
     */
    public function filterIsNull($field) {
        return $this->filter($this->prepareFieldName($field) . ' IS NULL');
    }

    /**
     * Добавляет условие с произвольным знаком сравнения.
     *
     * Значение подставляется в SQL как есть, поэтому его нужно подготовить заранее.
     *
     * @param string $field Название поля
     * @param string $value Значение или SQL-выражение
     * @param string $sign Знак сравнения (=, >, < и т.п.)
     * @return $this
     */
    public function filterFunc($field, $value, $sign = '=') {
        return $this->filter($this->prepareFieldName($field) . " " . $sign . " " . $value);
    }

    /**
     * Добавляет условие «поле > значение».
     *
     * @param string $field Название поля
     * @param mixed $value Значение
     * @return $this
     */
    public function filterGt($field, $value) {
        return $this->filterFunc($field, "'" . $this->db->escape($value) . "'", '>');
    }

    /**
     * Добавляет условие «поле < значение».
     *
     * @param string $field Название поля
     * @param mixed $value Значение
     * @return $this
     */
    public function filterLt($field, $value) {
        return $this->filterFunc($field, "'" . $this->db->escape($value) . "'", '<');
    }

    /**
     * Добавляет условие «поле >= значение».
     *
     * @param string $field Название поля
     * @param mixed $value Значение
     * @return $this
     */
    public function filterGtEqual($field, $value) {
        return $this->filterFunc($field, "'" . $this->db->escape($value) . "'", '>=');
    }

    /**
     * Добавляет условие «поле <= значение».
     *
     * @param string $field Название поля
     * @param mixed $value Значение
     * @return $this
     */
    public function filterLtEqual($field, $value) {
        return $this->filterFunc($field, "'" . $this->db->escape($value) . "'", '<=');
    }

    /**
     * Добавляет условие LIKE.
     *
     * @param string $field Название поля
     * @param string $value Шаблон поиска
     * @return $this
     */
    public function filterLike($field, $value) {
        return $this->filter($this->prepareFieldName($field) . " LIKE '" . $this->db->escape($value) . "'");
    }

    /**
     * Добавляет условие NOT LIKE.
     *
     * @param string $field Название поля
     * @param string $value Шаблон поиска
     * @return $this
     */
    public function filterNotLike($field, $value) {
        return $this->filter($this->prepareFieldName($field) . " NOT LIKE '" . $this->db->escape($value) . "'");
    }

    /**
     * Добавляет условие BETWEEN.
     *
     * @param string $field Название поля
     * @param mixed $start Начало диапазона
     * @param mixed $end Конец диапазона
     * @return $this
     */
    public function filterBetween($field, $start, $end) {
        return $this->filter($this->prepareFieldName($field) . " BETWEEN '" . $this->db->escape($start) . "' AND '" . $this->db->escape($end) . "'");
    }

    /**
     * Добавляет условие «дата в поле не старше указанного интервала».
     *
     * @param string $field Название поля
     * @param integer $value Величина интервала
     * @param string $interval Единица измерения (DAY, HOUR и т.п.)
     * @return $this
     */
    public function filterDateYounger($field, $value, $interval = 'DAY') {
        return $this->filter($this->prepareFieldName($field) . " >= DATE_SUB(NOW(), INTERVAL " . $this->db->escape($value) . " " . $this->db->escape($interval) . ")");
    }

    /**
     * Добавляет условие «разница между текущим временем и полем не больше указанной».
     *
     * @param string $field Название поля
     * @param integer $value Величина интервала
     * @param string $interval Единица измерения (DAY, HOUR и т.п.)
     * @return $this
     */
    public function filterTimestampYounger($field, $value, $interval = 'DAY') {
        return $this->filter("TIMESTAMPDIFF(" . $this->db->escape($interval) . ", " . $this->prepareFieldName($field) . ", NOW()) <= " . (int) $value);
    }

    /**
     * Добавляет условие «дата в поле старше указанного интервала».
     *
     * @param string $field Название поля
     * @param integer $value Величина интервала
     * @param string $interval Единица измерения (DAY, HOUR и т.п.)
     * @return $this
     */
    public function filterDateOlder($field, $value, $interval = 'DAY') {
        return $this->filter($this->prepareFieldName($field) . " < DATE_SUB(NOW(), INTERVAL " . $this->db->escape($value) . " " . $this->db->escape($interval) . ")");
    }

    /**
     * Добавляет условие «UNIX_TIMESTAMP(поле) > значение».
     *
     * @param string $field Название поля
     * @param integer|string $value Метка времени
     * @return $this
     */
    public function filterTimestampGt($field, $value) {
        return $this->filter("UNIX_TIMESTAMP(" . $this->prepareFieldName($field) . ") > '" . $this->db->escape($value) . "'");
    }

    /**
     * Добавляет условие «UNIX_TIMESTAMP(поле) < значение».
     *
     * @param string $field Название поля
     * @param integer|string $value Метка времени
     * @return $this
     */
    public function filterTimestampLt($field, $value) {
        return $this->filter("UNIX_TIMESTAMP(" . $this->prepareFieldName($field) . ") < '" . $this->db->escape($value) . "'");
    }

    /**
     * Добавляет условие IN (или NOT IN).
     *
     * @param string $field Название поля
     * @param mixed $value Значение или массив значений
     * @param boolean $not Использовать NOT IN
     * @return $this
     */
    public function filterIn($field, $value, $not = false) {
        if (is_array($value)) {
            if (!$value) {
                // IN () — синтаксическая ошибка MySQL, поэтому пустой список
                // заменяем на заведомо ложное/истинное условие
                return $this->filter($not === true ? '1 = 1' : '1 = 0');
            }

            foreach ($value as $k => $v) {
                $value[$k] = is_null($v) ? 'NULL' : "'" . $this->db->escape($v) . "'";
            }

            $value = implode(',', $value);
        } else {
            $value = is_null($value) ? 'NULL' : "'" . $this->db->escape($value) . "'";
        }

        return $this->filter($this->prepareFieldName($field) . ($not === true ? " NOT" : "") . " IN (" . $value . ")");
    }

    /**
     * Добавляет условие NOT IN.
     *
     * @param string $field Название поля
     * @param mixed $value Значение или массив значений
     * @return $this
     */
    public function filterNotIn($field, $value) {
        return $this->filterIn($field, $value, true);
    }

    /**
     * Добавляет поиск по указанному полю.
     *
     * Для коротких строк используется LIKE, для остальных — MATCH ... AGAINST
     * в режиме BOOLEAN. Результат сортируется по релевантности.
     *
     * @param string $field Название поля
     * @param string $value Поисковая строка
     * @return $this
     */
    public function filterRelated($field, $value) {
        if (mb_strlen($value) <= 3) {
            return $this->filterLike($field, $value . '%');
        }

        $query = array();

        $words = explode(' ', str_replace(array('"', '\'', '+', '*', '-', '%', ')', '(', '.', ',', '!', '?'), ' ', $value));

        foreach ($words as $word) {
            $word = mb_strtolower(trim($word));

            if (mb_strlen($word) < 3 || is_numeric($word)) {
                continue;
            }

            if (mb_strlen($word) == 3) {
                $query[] = $this->db->escape($word);
                continue;
            }

            if (mb_strlen($word) >= 12) {
                $word = mb_substr($word, 0, mb_strlen($word) - 3) . '*';
            } else if (mb_strlen($word) >= 10) {
                $word = mb_substr($word, 0, mb_strlen($word) - 2) . '*';
            } else if (mb_strlen($word) >= 6) {
                $word = mb_substr($word, 0, mb_strlen($word) - 1) . '*';
            }

            $query[] = $this->db->escape($word);
        }

        if (empty($query)) {
            $ft_query = '"' . $this->db->escape($value) . '"';
        } else {
            usort($query, function ($a, $b) {
                return mb_strlen($b) - mb_strlen($a);
            });

            $query = array_slice($query, 0, 5);

            $ft_query = '>"' . $this->db->escape($value) . '" <(';
            $ft_query .= implode(' ', $query) . ')';
        }

        $search_param = "MATCH(" . $this->prepareFieldName($field) . ") AGAINST ('{$ft_query}' IN BOOLEAN MODE)";

        $this->select($search_param, 'fsort');

        $this->order_by = 'fsort desc';

        return $this->filter($search_param);
    }

    //========================================================================//

    /**
     * Добавляет в запрос подсказку STRAIGHT_JOIN.
     *
     * @return $this
     */
    public function setStraightJoin() {
        $this->straight_join = self::STRAIGHT_JOIN;
        return $this;
    }

    /**
     * Добавляет JOIN-выражение к запросу.
     *
     * @param string $join Тип JOIN (одна из констант класса)
     * @param string $table_name Название присоединяемой таблицы
     * @param string $as Псевдоним таблицы
     * @param string $on Условие соединения
     * @return $this
     */
    public function _join($join, $table_name, $as, $on) {
        $this->join .= $join . ' {#}' . $table_name . ' as ' . $as . ' ON ' . $on . PHP_EOL;

        return $this;
    }

    /**
     * Добавляет INNER JOIN.
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $on Условие соединения
     * @return $this
     */
    public function join($table_name, $as, $on) {
        return $this->joinInner($table_name, $as, $on);
    }

    /**
     * Добавляет INNER JOIN.
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $on Условие соединения
     * @return $this
     */
    public function joinInner($table_name, $as, $on) {
        return $this->_join(self::INNER_JOIN, $table_name, $as, $on);
    }

    /**
     * Добавляет LEFT JOIN.
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $on Условие соединения
     * @return $this
     */
    public function joinLeft($table_name, $as, $on) {
        return $this->_join(self::LEFT_JOIN, $table_name, $as, $on);
    }

    /**
     * Добавляет LEFT JOIN с условием «в правой таблице нет совпадений».
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $right_key Поле правой таблицы
     * @param string $left_key Поле левой таблицы
     * @param string $join_where Дополнительное условие соединения
     * @return $this
     */
    public function joinExcludingLeft($table_name, $as, $right_key, $left_key, $join_where = '') {
        $this->filter($right_key . ' IS NULL');
        return $this->_join(self::LEFT_JOIN, $table_name, $as, $left_key . '=' . $right_key . ($join_where ? ' AND ' . $join_where : ''));
    }

    /**
     * Добавляет RIGHT JOIN.
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $on Условие соединения
     * @return $this
     */
    public function joinRight($table_name, $as, $on) {
        return $this->_join(self::RIGHT_JOIN, $table_name, $as, $on);
    }

    /**
     * Добавляет RIGHT JOIN с условием «в левой таблице нет совпадений».
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $right_key Поле правой таблицы
     * @param string $left_key Поле левой таблицы
     * @param string $join_where Дополнительное условие соединения
     * @return $this
     */
    public function joinExcludingRight($table_name, $as, $right_key, $left_key, $join_where = '') {
        $this->filter($left_key . ' IS NULL');
        return $this->_join(self::RIGHT_JOIN, $table_name, $as, $left_key . '=' . $right_key . ($join_where ? ' AND ' . $join_where : ''));
    }

    /**
     * Добавляет LEFT OUTER JOIN.
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $on Условие соединения
     * @return $this
     */
    public function joinLeftOuter($table_name, $as, $on) {
        return $this->_join(self::LEFT_OUTER_JOIN, $table_name, $as, $on);
    }

    /**
     * Добавляет RIGHT OUTER JOIN.
     *
     * @param string $table_name Название таблицы
     * @param string $as Псевдоним таблицы
     * @param string $on Условие соединения
     * @return $this
     */
    public function joinRightOuter($table_name, $as, $on) {
        return $this->_join(self::RIGHT_OUTER_JOIN, $table_name, $as, $on);
    }

    //========================================================================//

    /**
     * Задаёт группировку GROUP BY.
     *
     * @param array|string $field Поле или массив полей
     * @return $this
     */
    public function groupBy($field) {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $field[$k] = $this->prepareFieldName($v);
            }

            $this->group_by = implode(", ", $field);
        } else {
            $this->group_by = $this->prepareFieldName($field);
        }

        return $this;
    }

    /**
     * Управляет индексами в запросе
     * @param string $index_name Название индекса в БД
     * @param string $action FORCE | IGNORE | USE
     * @param int $for 1 - FOR JOIN, 2 - FOR ORDER BY, 3 - FOR GROUP BY
     * @return $this
     */
    protected function indexHint($index_name, $action, $for = '') {
        switch ($for) {
            case 1:
                $for_action = 'FOR JOIN';
                break;
            case 2:
                $for_action = 'FOR ORDER BY';
                break;
            case 3:
                $for_action = 'FOR GROUP BY';
                break;
            default:
                $for_action = '';
                break;
        }

        $this->index_action .= "{$action} INDEX {$for_action} ({$index_name})\n";

        return $this;
    }

    /**
     * Добавляет подсказку FORCE INDEX.
     *
     * @param string $index_name Название индекса
     * @param integer|string $for Область применения: 1 — FOR JOIN, 2 — FOR ORDER BY, 3 — FOR GROUP BY
     * @return $this
     */
    public function forceIndex($index_name, $for = '') {
        return $this->indexHint($index_name, 'FORCE', $for);
    }

    /**
     * Добавляет подсказку IGNORE INDEX.
     *
     * @param string $index_name Название индекса
     * @param integer|string $for Область применения: 1 — FOR JOIN, 2 — FOR ORDER BY, 3 — FOR GROUP BY
     * @return $this
     */
    public function ignoreIndex($index_name, $for = '') {
        return $this->indexHint($index_name, 'IGNORE', $for);
    }

    /**
     * Добавляет подсказку USE INDEX.
     *
     * @param string $index_name Название индекса
     * @param integer|string $for Область применения: 1 — FOR JOIN, 2 — FOR ORDER BY, 3 — FOR GROUP BY
     * @return $this
     */
    public function useIndex($index_name, $for = '') {
        return $this->indexHint($index_name, 'USE', $for);
    }

    /**
     * Задаёт сортировку ORDER BY.
     *
     * @param string $field Поле или SQL-выражение
     * @param string $direction Направление: asc или desc
     * @return $this
     */
    public function orderBy($field, $direction = '') {
        // сортировка по выражению (RAND(), COUNT(*) и т.п.) не должна теряться
        if (strpos($field, '(') === false) {
            $field = $this->prepareFieldName($field);
        }

        $this->order_by = $field . ' ' . (strtolower($direction) === 'desc' ? 'desc' : 'asc');

        return $this;
    }

    /**
     * Задаёт тип блокировки чтения.
     *
     * @param string $type FOR UPDATE или LOCK IN SHARE MODE
     * @return $this
     */
    public function setReadType($type) {
        $this->read_type = $type;

        return $this;
    }

    //========================================================================//
    //============================== Транзакции ==================================//

    /**
     * Проверяет, включён ли автокоммит.
     *
     * @return boolean
     */
    public function isAutocommitOn() {
        $result = $this->query('SELECT @@autocommit');

        if ($result === false || !$result->rowsCount()) {
            return false;
        }

        $row = $result->fetchRow();

        return isset($row[0]) && $row[0] == 1;
    }

    /**
     * Выполняет callback внутри транзакции.
     *
     * Ошибки запросов внутри транзакции не прерывают выполнение, а по её
     * завершении выполняется COMMIT или ROLLBACK.
     *
     * @param callable $payload_callback Функция, получающая объект DB
     * @param boolean $after_autocommit_on Завершить транзакцию, даже если автокоммит был выключен
     * @return boolean TRUE при успешном выполнении
     */
    public function processTransaction($payload_callback, $after_autocommit_on = false) {
        // запоминаем предыдущее значение: транзакции могут быть вложенными
        $prev_query_quiet = $this->query_quiet;

        // нам не нужно, чтобы внутри транзакции при ошибке запроса
        // всё умирало
        $this->query_quiet = true;

        // флаг результата выполнения
        $success = true;

        // мы внутри транзакции?
        $is_autocommit_on = $this->isAutocommitOn();

        // выключаем автокоммит, чтобы все запросы были в транзакции
        // если автокоммит выключен, то мы уже в транзакции
        if ($is_autocommit_on && $this->db instanceof DB\Driver\DBInterface) {
            $this->db->autocommitOff();
        }

        try {
            $success = call_user_func_array($payload_callback, array($this));
        } catch (\Exception $e) {
            // метод self::error() отсутствует, логируем через объект отладки
            $this->_debug->error('TRANSACTION', array('msg' => $e->getMessage()));
            $success = false;
        } catch (\Throwable $e) {
            // PHP 7+: Error не является Exception, но транзакцию всё равно нужно откатить
            $this->_debug->error('TRANSACTION', array('msg' => $e->getMessage()));
            $success = false;
        }

        $this->query_quiet = $prev_query_quiet;

        if ($is_autocommit_on || $after_autocommit_on) {
            $this->endTransaction($success);
        }

        return $success;
    }

    /**
     * Начинает транзакцию.
     *
     * @return $this
     */
    public function startTransaction() {
        $this->is_transaction_started = true;

        if ($this->db instanceof DB\Driver\DBInterface) {
            $this->db->autocommitOff();
        }

        return $this;
    }

    /**
     * Завершает транзакцию.
     *
     * @param boolean $success TRUE — COMMIT, FALSE — ROLLBACK
     * @return $this
     */
    public function endTransaction($success) {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            $this->is_transaction_started = false;
            return $this;
        }

        if ($success) {
            $this->db->commit();
        } else {
            $this->db->rollback();
        }

        $this->db->autocommitOn();

        $this->is_transaction_started = false;

        return $this;
    }

    /**
     * Добавляет к запросу блокировку FOR UPDATE.
     *
     * @return $this
     */
    public function forUpdate() {
        return $this->setReadType('FOR UPDATE');
    }

    /**
     * Добавляет к запросу блокировку LOCK IN SHARE MODE.
     *
     * @return $this
     */
    public function lockInShareMode() {
        return $this->setReadType('LOCK IN SHARE MODE');
    }

    /**
     * Устанавливает уровень изоляции транзакции.
     *
     * @param string $level Уровень изоляции (одна из констант класса)
     * @return $this
     */
    public function setTransactionIsolationLevel($level) {
        $this->query("SET TRANSACTION ISOLATION LEVEL " . $level);
        return $this;
    }

    //========================================================================//

    /**
     * Возвращает готовый SQL-запрос SELECT по накопленным параметрам.
     *
     * @return string
     */
    public function getSQL() {
        $select = implode(', ', $this->select);

        $sql = "SELECT {$this->distinct} {$this->straight_join} {$select}
                FROM {#}{$this->table} i
                {$this->index_action}";

        if ($this->join) {
            $sql .= $this->join;
        }

        if ($this->where) {
            $sql .= 'WHERE ' . $this->where . PHP_EOL;
        }

        if ($this->group_by) {
            $sql .= 'GROUP BY ' . $this->group_by . PHP_EOL;
        }

        if ($this->order_by) {
            $sql .= 'ORDER BY ' . $this->order_by . PHP_EOL;
        }

        if ($this->limit) {
            $sql .= 'LIMIT ' . $this->limit . PHP_EOL;
        }

        if ($this->read_type) {
            $sql .= $this->read_type . PHP_EOL;
        }

        // getSQL() публичный и должен возвращать готовый к выполнению запрос
        return $this->replacePrefix($sql);
    }

    //========================================================================//

    /**
     * Приводит название поля к виду `поле` или i.`поле`.
     *
     * Названия с точкой считаются уже подготовленными и не меняются.
     *
     * @param string $field Название поля
     * @param boolean $i Добавлять префикс алиаса i.
     * @return string
     */
    protected function prepareFieldName($field, $i = true) {
        $field = preg_replace('/[^a-zA-Z0-9_\$#@.]/u', '', (string) $field);

        if (strpos($field, '.') === false) {
            $field = "`" . $field . "`";
            if ($i) {
                $field = "i." . $field;
            }
        }

        return $field;
    }

    /**
     * Формирует префиксы таблиц в SQL запросе
     * @param string $sql
     * @return string
     */
    public function replacePrefix($sql, $prefix = null) {
        $_prefix = ['{#}'];
        if (!empty($prefix)) {
            $_prefix[] = (string) $prefix;
        }
        return str_replace($_prefix, [$this->options['table_prefix']], $sql);
    }

    /**
     * Подготавливает значение $value поля $field для вставки в запрос
     *
     * @param string $field
     * @param mixed $value
     * @param string $array_as Преобразовать массив в строку с помошью json, yaml, serialize
     *
     * @return string
     */
    public function prepareValue($field, $value, $array_as = 'json') {
        $is_date_field = strpos($field, 'date_') === 0;

        if (is_array($value)) {
            switch ($array_as) {
                case 'yaml':
                    if (function_exists('yaml_emit')) {
                        $value = $this->db->escape(yaml_emit($value, YAML_UTF8_ENCODING));
                    } else if (class_exists('\Symfony\Component\Yaml\Yaml')) {
                        $value = $this->db->escape(\Symfony\Component\Yaml\Yaml::dump($value));
                    } else {
                        // нет ни расширения yaml, ни Symfony/Yaml — не теряем данные
                        $value = $this->db->escape(json_encode($value));
                    }
                    break;
                case 'serialize':
                    $value = $this->db->escape(serialize($value));
                    break;
                default:
                    $value = $this->db->escape(json_encode($value));
                    break;
            }

            $value = "'" . $value . "'";
        } else if ($is_date_field && ($value === false)) {
            $value = 'NULL';
        } else if ($is_date_field && ($value === '' || $value === 0 || $value === '0' || is_null($value))) {
            $value = 'CURRENT_TIMESTAMP';
        } else if (is_bool($value)) {
            $value = (int) $value;
        } else if ($value === '' || is_null($value)) {
            $value = 'NULL';
        } else if ($value instanceof \Closure) {
            $value = $value($this);
        } else {
            $value = $this->db->escape(trim((string) $value));
            $value = "'" . $value . "'";
        }

        return $value;
    }

    //========================================================================//

    /**
     * Создаёт базу данных, если её нет
     *
     * @param string $name
     *
     * @return bool
     */
    public function createDatabase($name) {
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $name)) {
            $this->_debug->error("CREATE_DATABASE", ['msg' => "Invalid database name"]);
            return false;
        }

        $sql = 'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $name) . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';

        return $this->query($sql);
    }

    //========================================================================//

    /**
     * Удаляет таблицу, если она существует.
     *
     * @param string $table_name Название таблицы
     * @return boolean|DB\Result\ResultInterface
     */
    public function dropTable($table_name) {
        return $this->query("DROP TABLE IF EXISTS {#}" . $table_name);
    }

    /**
     * Удаляет поле из таблицы.
     *
     * @param string $table_name Название таблицы
     * @param string $field_name Название поля
     * @return boolean|DB\Result\ResultInterface
     */
    public function dropTableField($table_name, $field_name) {
        return $this->query("ALTER TABLE `{#}" . $table_name . "` DROP `" . $field_name . "`");
    }

    /**
     * Добавляет поле в таблицу, если его ещё нет.
     *
     * @param string $table_name Название таблицы
     * @param string $field_name Название поля
     * @param string $sql SQL-описание поля (тип, значение по умолчанию и т.п.)
     * @return boolean|DB\Result\ResultInterface
     */
    public function addTableField($table_name, $field_name, $sql) {

        if ($this->isFieldExists($table_name, $field_name)) {
            return false;
        }

        return $this->query("ALTER TABLE `{#}" . $table_name . "` ADD `" . $field_name . "` " . $sql);
    }

    /**
     * Проверяет, существует ли таблица в текущей базе данных.
     *
     * @param string $table_name Название таблицы без префикса
     * @return boolean
     */
    public function isTableExists($table_name) {
        $result = $this->query('SHOW TABLES');

        if (!$result) {
            return false;
        }

        $table_name = $this->replacePrefix('{#}' . $table_name);

        while ($data = $result->fetchRow()) {
            if ($data[0] === $table_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Создаёт таблицу по описанию структуры.
     *
     * @param string $table_name Название таблицы без префикса
     * @param array $structure Описание полей: имя => параметры (type, size, default, index и т.п.)
     * @param string $engine Движок таблицы
     * @return boolean|DB\Result\ResultInterface
     */
    public function createTable($table_name, $structure, $engine = 'MYISAM') {
        $sql = "CREATE TABLE IF NOT EXISTS `{#}" . $table_name . "` (" . PHP_EOL;

        $fcount = 0;
        $ftotal = sizeof($structure);

        $indexes = $fulltext = $unique = $indexes_created = [];

        foreach ($structure as $name => $field) {
            $fcount++;
            $sep = ($fcount == $ftotal) ? PHP_EOL : "," . PHP_EOL;

            $default = (!isset($field['default']) ? 'NULL' : "NOT NULL DEFAULT '" . $this->db->escape($field['default']) . "'");
            $unsigned = (!isset($field['unsigned']) ? '' : 'UNSIGNED');

            // обычный индекс
            if (isset($field['index'])) {
                if ($field['index'] === true) {
                    $indexes[$name] = array($name);
                } else if (is_string($field['index'])) {
                    $position = isset($field['composite_index']) ? $field['composite_index'] : 0;
                    $indexes[$field['index']][$position] = $name;
                } else if (is_array($field['index'])) {
                    $composite_index = isset($field['composite_index']) ? $field['composite_index'] : array();
                    foreach ($field['index'] as $k => $i_name) {
                        $position = isset($composite_index[$k]) ? $composite_index[$k] : $k;
                        $indexes[$i_name][$position] = $name;
                    }
                }
            }

            // уникальный индекс
            if (isset($field['unique'])) {
                $unique[] = $name;
            }

            // полнотекстовый индекс
            if (isset($field['fulltext'])) {
                if ($field['fulltext'] === true) {
                    $fulltext[$name] = array($name);
                }
            }

            if (!isset($field['type'])) {
                continue;
            }

            switch ($field['type']) {
                case 'primary':
                    $sql .= "\t`" . $name . "` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY" . $sep;
                    break;
                case 'bool':
                    $sql .= "\t`" . $name . "` tinyint(1) UNSIGNED " . $default . "" . $sep;
                    break;
                case 'timestamp':
                    $current = (isset($field['default_current']) && $field['default_current'] == true) ? "NOT NULL DEFAULT CURRENT_TIMESTAMP" : 'NULL';
                    $sql .= "\t`" . $name . "` TIMESTAMP " . $current . "" . $sep;
                    break;
                case 'tinyint':
                    $sql .= "\t`" . $name . "` TINYINT " . $unsigned . " " . $default . "" . $sep;
                    break;
                case 'int':
                    if (!isset($field['size'])) {
                        $field['size'] = 11;
                    }
                    $sql .= "\t`" . $name . "` INT( " . $field['size'] . " ) " . $unsigned . " " . $default . "" . $sep;
                    break;
                case 'varchar':
                    if (!isset($field['size'])) {
                        $field['size'] = 255;
                    }
                    $sql .= "\t`" . $name . "` VARCHAR( " . $field['size'] . " ) " . $default . "" . $sep;
                    break;
                case 'text':
                    $sql .= "\t`" . $name . "` TEXT NULL" . $sep;
                    break;
                case 'set':
                    $values = array_map(function ($item) {
                        return '"' . $item . '"';
                    }, $field['items']);
                    $values = implode(',', $values);
                    $sql .= "\t`" . $name . "` SET (" . $values . ") " . $default . "" . $sep;
                    break;
                default: break;
            }
        }

        $charset = !empty($this->options['db_charset']) ? $this->options['db_charset'] : 'utf8';

        $sql .= ") ENGINE=" . $engine . " DEFAULT CHARSET=" . $charset;

        $result = $this->query($sql);

        foreach ($indexes as $index_name => $fields) {
            if (in_array($index_name, $indexes_created)) {
                continue;
            }

            $this->addIndex($table_name, $fields, $index_name);

            $indexes_created[] = $index_name;
        }

        foreach ($unique as $field) {
            if (in_array($field, $indexes_created)) {
                continue;
            }

            $this->addIndex($table_name, $field, $field, 'UNIQUE');

            $indexes_created[] = $field;
        }

        foreach ($fulltext as $index_name => $fields) {
            if (in_array($index_name, $indexes_created)) {
                continue;
            }

            $this->addIndex($table_name, $fields, $index_name, 'FULLTEXT');

            $indexes_created[] = $index_name;
        }

        return $result;
    }

    /**
     * Проверяет, что значение поля уникально.
     *
     * @param string $table_name Название таблицы
     * @param string $field_name Название поля
     * @param mixed $value Проверяемое значение
     * @param integer|string|false $exclude_row_id Идентификатор записи, которую нужно исключить
     * @return boolean
     */
    public function isFieldUnique($table_name, $field_name, $value, $exclude_row_id = false) {
        $this->filterEqual($field_name, $value);

        if ($exclude_row_id) {
            $this->filter($this->prepareFieldName('id') . " <> '" . $this->db->escape($exclude_row_id) . "'");
        }

        return $this->getCount($table_name, 'id', true) == 0;
    }

    /**
     * Проверяет, существует ли поле в таблице.
     *
     * @param string $table_name Название таблицы
     * @param string $field Название поля
     * @return boolean
     */
    public function isFieldExists($table_name, $field) {

        $table_fields = $this->getTableFields($table_name);

        return in_array($field, $table_fields, true);
    }

    /**
     * Возвращает поля, участвующие в индексе или false, если индекса нет
     * если индекс составной, то поля будут упорядочены в массиве как в индексе
     * @param string $table Название таблицы без префикса
     * @param string $index_name Название индекса
     * @return array|boolean
     */
    public function getIndex($table, $index_name) {
        $result = $this->query("SHOW INDEX FROM `{#}" . $table . "` WHERE `Key_name` = '" . $this->db->escape($index_name) . "'");

        if ($result === false || !is_object($result) || !$result->rowsCount()) {
            return false;
        }

        $fields = array();

        while ($i = $result->fetchAssoc()) {
            $fields[(int) $i['Seq_in_index']] = $i['Column_name'];
        }

        ksort($fields);

        return array_values($fields);
    }

    /**
     * Возвращает все индексы таблицы
     * @param string $table Название таблицы без префикса
     * @param string $index_type Тип индекса
     * @return boolean|array
     */
    public function getTableIndexes($table, $index_type = null) {
        $sql = "SHOW INDEX FROM `{#}" . $table . "`";
        if ($index_type) {
            $sql .= " WHERE `Index_type` = '" . $this->db->escape($index_type) . "'";
        }

        $result = $this->query($sql);

        if ($result === false || !is_object($result) || !$result->rowsCount()) {
            return false;
        }

        $indexes = array();

        while ($i = $result->fetchAssoc()) {
            $indexes[$i['Key_name']][(int) $i['Seq_in_index']] = $i['Column_name'];
        }

        foreach ($indexes as $key => $fields) {
            ksort($fields);
            $indexes[$key] = array_values($fields);
        }

        return $indexes;
    }

    /**
     * Проверяет, есть ли указанный индекс в таблице
     * @param string $table Название таблицы без префикса
     * @param string $index_name Название индекса
     * @return boolean
     */
    public function isIndexExists($table, $index_name) {
        return $this->getIndex($table, $index_name) !== false;
    }

    /**
     * Удаляет индекс из таблицы, если он там есть
     * @param string $table Название таблицы без префикса
     * @param string $index_name Название индекса
     * @return boolean
     */
    public function dropIndex($table, $index_name) {
        if ($this->isIndexExists($table, $index_name)) {
            $this->query("ALTER TABLE `{#}" . $table . "` DROP INDEX `" . $this->db->escape($index_name) . "`");
            return true;
        }

        return false;
    }

    /**
     * Добавляет индекс к таблице
     * @param string $table Название таблицы без префикса
     * @param array|string $fields Поле или поля, участвующие в индексе
     * @param string $index_name Название индекса. Если не передано, название будет по последнему элементу
     * @param string $index_type Тип индекса
     * @return boolean FALSE если индекс с таким названием уже есть
     */
    public function addIndex($table, $fields, $index_name = '', $index_type = 'INDEX') {

        if (is_string($fields)) {
            $fields = array($fields);
        }

        if (!$index_name) {
            $index_name = end($fields);
        }

        if ($this->isIndexExists($table, $index_name)) {
            return false;
        }

        ksort($fields);

        $fields_str = '`' . implode('`, `', $fields) . '`';

        $this->query("ALTER TABLE `{#}" . $table . "` ADD " . $index_type . " `" . $index_name . "` (" . $fields_str . ")");

        return true;
    }

    //========================================================================//

    /**
     * Перенаправляет вызовы неизвестных методов драйверу базы данных.
     *
     * @param string $name Название метода
     * @param array $arguments Аргументы метода
     * @return mixed
     */
    public function __call($name, $arguments) {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return false;
        }

        return call_user_func_array(array($this->db, $name), $arguments);
    }

    /**
     * Экранирует строку для использования в SQL запросе
     * @param string $string
     * @return string
     */
    public function escape($string) {
        if (!$this->db instanceof DB\Driver\DBInterface) {
            return false;
        }

        return $this->db->escape($string);
    }

    /**
     * Возвращает объект отладки.
     *
     * @return DB\Debug
     */
    public function getDebug() {
        return $this->_debug;
    }
}