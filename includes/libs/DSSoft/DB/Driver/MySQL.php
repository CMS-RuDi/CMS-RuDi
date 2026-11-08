<?php

namespace DSSoft\DB\Driver;

use DSSoft\DB\Result\MySQL_Result;

/**
 * Драйвер базы данных MySQL на основе расширения mysqli.
 */
class MySQL implements DBInterface {
    /**
     * Соединение с сервером MySQL.
     *
     * @var \mysqli
     */
    protected $mysqli;
    /**
     * Признак активного соединения.
     *
     * @var boolean
     */
    protected $connected = false;
    /**
     * Текст последней ошибки подключения.
     *
     * @var string|null
     */
    protected $connect_error = null;
    /**
     * Время установки соединения (UNIX timestamp).
     *
     * @var integer
     */
    protected $db_con_time = 0;
    /**
     * Хост сервера базы данных.
     *
     * @var string
     */
    protected $host;
    /**
     * Имя пользователя базы данных.
     *
     * @var string
     */
    protected $user;
    /**
     * Пароль пользователя базы данных.
     *
     * @var string
     */
    protected $password;
    /**
     * Название базы данных.
     *
     * @var string
     */
    protected $dbase;
    /**
     * Опции подключения.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Время, через которое при PHP_SAPI == 'cli' нужно сделать реконнект
     * для случаев, когда mysql.connect_timeout по дефолту (60 с) и переопределить это поведение нельзя
     * @var integer
     */
    private $reconnect_time = 60;

    /**
     * Создаёт объект mysqli и отключает автоматическое выбрасывание исключений.
     */
    public function __construct() {
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->mysqli = new \mysqli();
    }

    /**
     * Устанавливает соединение с сервером, если его ещё нет.
     *
     * @return boolean TRUE при успешном подключении
     */
    protected function _connect() {
        if ($this->status()) {
            return true;
        }

        // новое соединение создаём на чистом объекте mysqli,
        // чтобы не зависеть от состояния предыдущего (не)удачного подключения
        $this->mysqli = new \mysqli();
        $this->connected = false;

        $port = !empty($this->options['db_port']) ? (int) $this->options['db_port'] : null;
        $socket = !empty($this->options['db_socket']) ? $this->options['db_socket'] : null;

        @ $this->mysqli->connect($this->host, $this->user, $this->password, $this->dbase, $port, $socket);

        if ($this->mysqli->connect_error) {
            // текст ошибки доступен через ConError(), логирует его DSSoft\DB::connect()
            $this->connect_error = $this->mysqli->connect_error;
            return false;
        }

        $this->connected = true;
        $this->connect_error = null;
        $this->db_con_time = time();

        $this->setParams();

        return true;
    }

    /**
     * Запоминает параметры подключения и подключается к базе данных.
     *
     * @param string $host Хост сервера
     * @param string $user Имя пользователя
     * @param string $password Пароль
     * @param string|null $dbase Название базы данных
     * @param array $options Дополнительные опции (charset, port, socket и т.п.)
     * @return boolean
     */
    public function connect($host, $user, $password, $dbase = null, $options = []) {
        if ($host != $this->host || $user != $this->user || $password != $this->password || $dbase != $this->dbase || $options != $this->options) {
            $this->close();

            $this->host = $host;
            $this->user = $user;
            $this->password = $password;
            $this->dbase = $dbase;
            $this->options = $options;
        }

        return $this->_connect();
    }

    /**
     * Переподключается к серверу.
     *
     * @return boolean
     */
    public function reconnect() {
        $this->close();
        return $this->_connect();
    }

    /**
     * Проверяет доступность соединения.
     *
     * @return boolean
     */
    public function status() {
        // ping() нельзя вызывать на закрытом/неинициализированном объекте mysqli
        if (!$this->connected) {
            return false;
        }

        return @ $this->mysqli->ping();
    }

    /**
     * Закрывает соединение с сервером.
     *
     * @return void
     */
    public function close() {
        if (!$this->connected) {
            return;
        }

        @ $this->mysqli->close();

        $this->connected = false;
        $this->db_con_time = 0;
    }

    /**
     * Возвращает идентификатор последней вставленной записи.
     *
     * @return integer|string|false
     */
    public function lastId() {
        return $this->connected ? $this->mysqli->insert_id : false;
    }

    /**
     * Выполняет запрос и возвращает результат.
     *
     * Для запросов, возвращающих набор строк, возвращается объект результата.
     * При потере соединения (ошибки 2006 и 2013) выполняется переподключение
     * и повторная попытка.
     *
     * @param string $sql SQL-запрос
     * @return \DSSoft\DB\Result\ResultInterface|boolean FALSE при ошибке
     */
    public function query($sql) {
        if (!$this->connected) {
            $this->_connect();
        }

        if (PHP_SAPI == 'cli' && $this->db_con_time > 0 && (time() - $this->db_con_time) >= $this->reconnect_time) {
            $this->reconnect();
        }

        $result = @ $this->mysqli->query($sql);

        // 2006 — MySQL server has gone away, 2013 — lost connection during query
        if ($result === false && in_array($this->mysqli->errno, array(2006, 2013), true)) {
            $this->reconnect();

            $result = @ $this->mysqli->query($sql);
        }

        if ($this->mysqli->errno) {
            return false;
        }

        if ($result instanceof \mysqli_result) {
            return new MySQL_Result($result);
        }

        return $result;
    }

    /**
     * Возвращает количество строк, затронутых последним запросом.
     *
     * @return integer
     */
    public function affectedRows() {
        return $this->connected ? $this->mysqli->affected_rows : 0;
    }

    /**
     * Экранирует строку для использования в SQL-запросе.
     *
     * @param string $string Строка
     * @return string
     */
    public function escape($string) {
        if (!$this->connected) {
            // экранирование невозможно без соединения (и без знания кодировки)
            $this->_connect();
        }

        $escaped = @ $this->mysqli->real_escape_string((string) $string);

        if ($escaped === false || $escaped === null) {
            return addslashes((string) $string);
        }

        return $escaped;
    }

    /**
     * Применяет к соединению кодировку, режим SQL и часовой пояс.
     *
     * @return void
     */
    protected function setParams() {
        if ($this->options['db_charset']) {
            @ $this->mysqli->set_charset($this->options['db_charset']);
        }

        if (!empty($this->options['clear_sql_mode'])) {
            $this->mysqli->query("SET sql_mode=''");
        }

        $this->mysqli->query("SET `time_zone` = '" . date('P') . "'");

        if (defined('LC_LANGUAGE_TERRITORY')) {
            $this->mysqli->query("SET lc_messages = '" . LC_LANGUAGE_TERRITORY . "'");
        }
    }

    /**
     * Возвращает описание последней ошибки.
     *
     * @return string|null
     */
    public function Error() {
        if (!$this->connected) {
            return $this->connect_error;
        }

        if ($this->mysqli->errno) {
            return '#' . $this->ErrorNo() . ': ' . $this->mysqli->error;
        }

        return null;
    }

    /**
     * Возвращает код последней ошибки.
     *
     * @return integer 0, если ошибки не было
     */
    public function ErrorNo() {
        if (!$this->connected) {
            return 0;
        }

        return (int) $this->mysqli->errno;
    }

    /**
     * Возвращает текст ошибки подключения.
     *
     * @return string|null
     */
    public function ConError() {
        return $this->mysqli->connect_error;
    }

    //========================================================================//

    /**
     * Включает автокоммит.
     *
     * @return $this
     */
    public function autocommitOn() {
        if ($this->connected) {
            $this->mysqli->autocommit(true);
        }

        return $this;
    }

    /**
     * Выключает автокоммит.
     *
     * @return $this
     */
    public function autocommitOff() {
        if ($this->connected) {
            $this->mysqli->autocommit(false);
        }

        return $this;
    }

    /**
     * Откатывает текущую транзакцию.
     *
     * @return $this
     */
    public function rollback() {
        if ($this->connected) {
            $this->mysqli->rollback();
        }

        return $this;
    }

    /**
     * Фиксирует текущую транзакцию.
     *
     * @return $this
     */
    public function commit() {
        if ($this->connected) {
            $this->mysqli->commit();
        }

        return $this;
    }

    /**
     * Начинает транзакцию средствами mysqli.
     *
     * @return $this
     */
    public function beginTransaction() {
        if ($this->connected) {
            $this->mysqli->begin_transaction();
        }

        return $this;
    }

    //========================================================================//
}