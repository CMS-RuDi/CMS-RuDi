<?php

namespace DSSoft\DB\Result;

/**
 * Интерфейс результата запроса.
 */
interface ResultInterface {

    /**
     * Создаёт объект результата.
     *
     * @param mixed $result Оригинальный результат запроса
     */
    public function __construct($result);

    /**
     * Возвращает все строки в виде ассоциативного или обычного массива.
     *
     * @param boolean $assoc TRUE — ассоциативный массив, FALSE — обычный
     * @return array
     */
    public function fetchAll($assoc = true);

    /**
     * Возвращает следующую строку из набора результатов в виде ассоциативного массива.
     *
     * @return array|null
     */
    public function fetchAssoc();

    /**
     * Возвращает следующую строку из набора результатов в виде обычного массива.
     *
     * @return array|null
     */
    public function fetchRow();

    /**
     * Возвращает количество строк в результате.
     *
     * @return integer
     */
    public function rowsCount();

    /**
     * Освобождает память и выполняет другие необходимые операции при уничтожении объекта.
     *
     * @return void
     */
    public function __destruct();

    /**
     * Возвращает оригинальный результат запроса.
     *
     * Добавлен для поддержки устаревших компонентов.
     *
     * @return mixed
     */
    public function getOriginalResult();
}
