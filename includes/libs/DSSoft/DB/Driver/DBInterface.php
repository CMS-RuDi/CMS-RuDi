<?php

namespace DSSoft\DB\Driver;

/**
 * Интерфейс драйвера базы данных.
 */
interface DBInterface {

    /**
     * Открывает новое соединение с сервером БД.
     *
     * @param string $host Адрес сервера
     * @param string $user Пользователь
     * @param string $password Пароль
     * @param string $dbase База данных
     * @param array $options Опции
     * @return boolean TRUE при успешном подключении
     */
    public function connect($host, $user, $password, $dbase, $options = []);

    /**
     * Выполняет запрос в базе.
     *
     * @param string $sql Строка запроса
     * @return boolean|\DSSoft\DB\Result\ResultInterface
     */
    public function query($sql);

    /**
     * Возвращает значение, созданное для столбца AUTO_INCREMENT последним запросом.
     *
     * @return integer|string
     */
    public function lastId();

    /**
     * Возвращает число строк, затронутых предыдущей операцией.
     *
     * @return integer|string
     */
    public function affectedRows();

    /**
     * Закрывает текущее соединение и открывает новое.
     *
     * @see connect
     * @return boolean
     */
    public function reconnect();

    /**
     * Экранирует строку для использования в SQL-запросе.
     *
     * @param string $string Строка для экранирования
     * @return string
     */
    public function escape($string);

    /**
     * Проверяет, работает ли подключение.
     *
     * @return boolean TRUE, если подключение работает
     */
    public function status();

    /**
     * Закрывает текущее соединение.
     *
     * @return void
     */
    public function close();

    /**
     * Возвращает описание последней ошибки.
     *
     * @return string|null
     */
    public function Error();

    /**
     * Возвращает числовой код последней ошибки.
     *
     * @return integer 0, если ошибки не было
     */
    public function ErrorNo();

    /**
     * Возвращает текст ошибки подключения.
     *
     * @return string|null NULL, если ошибки не было
     */
    public function ConError();
}
