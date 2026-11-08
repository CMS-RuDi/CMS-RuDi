<?php

namespace DSSoft;

/**
 * Синглтон для доступа к объекту DB.
 */
class DBSingeltone {
    /**
     * Экземпляр синглтона.
     *
     * @var DBSingeltone|null
     */
    protected static $_instance;
    /**
     * Объект для работы с базой данных.
     *
     * @var DB|null
     */
    protected static $_db;

    /**
     * Создаёт объект DB.
     *
     * @param string $host Хост сервера БД
     * @param string $user Имя пользователя
     * @param string $password Пароль
     * @param string $dbase Название базы данных
     * @param string $type Тип драйвера
     * @param array $options Дополнительные опции подключения
     */
    protected function __construct($host, $user, $password, $dbase, $type, $options) {
        self::$_db = new DB($host, $user, $password, $dbase, $type, $options);
    }

    /**
     * Возвращает единственный экземпляр синглтона, создавая его при первом вызове.
     *
     * @param mixed ...$arguments Аргументы конструктора: host, user, password, database [, type [, options]]
     * @return DBSingeltone
     * @throws \InvalidArgumentException Если экземпляр ещё не создан и аргументы не переданы
     */
    public static function getInstance(...$arguments) {
        if (self::$_instance === null) {
            if (count($arguments) < 4) {
                throw new \InvalidArgumentException('DBSingeltone::getInstance() requires 4 arguments: host, user, password, database');
            }

            self::$_instance = new self(
                $arguments[0],
                $arguments[1],
                $arguments[2],
                $arguments[3],
                isset($arguments[4]) ? $arguments[4] : 'MySQL',
                isset($arguments[5]) ? $arguments[5] : []
            );
        }

        return self::$_instance;
    }

    /**
     * Перенаправляет вызов метода объекту DB.
     *
     * @param string $name Название метода
     * @param array $arguments Аргументы метода
     * @return mixed
     */
    public function __call($name, $arguments) {
        if (!self::$_db instanceof DB) {
            return false;
        }

        return call_user_func_array(array(self::$_db, $name), $arguments);
    }

    /**
     * Запрещает клонирование синглтона.
     */
    protected function __clone() {

    }

    /**
     * Запрещает сериализацию синглтона: соединение с БД не переносится между процессами.
     *
     * @throws \LogicException Всегда
     */
    public function __sleep() {
        throw new \LogicException('Serialization of ' . __CLASS__ . ' is not allowed');
    }

    /**
     * Запрещает десериализацию синглтона.
     *
     * @throws \LogicException Всегда
     */
    public function __wakeup() {
        throw new \LogicException('Unserialization of ' . __CLASS__ . ' is not allowed');
    }
}