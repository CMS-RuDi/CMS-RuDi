<?php

namespace DSSoft\DB\Result;

/**
 * Обёртка над результатом запроса mysqli.
 */
class MySQL_Result implements ResultInterface {
    /**
     * Результат запроса.
     *
     * @var \mysqli_result
     */
    private $_result;

    /**
     * Сохраняет результат запроса.
     *
     * @param \mysqli_result $result Результат запроса mysqli
     */
    public function __construct($result) {
        $this->_result = $result;
    }

    /**
     * Возвращает все строки результата.
     *
     * @param boolean $assoc TRUE — ассоциативные массивы, FALSE — обычные
     * @return array
     */
    public function fetchAll($assoc = true) {
        return $this->_result->fetch_all($assoc === true ? MYSQLI_ASSOC : MYSQLI_NUM);
    }

    /**
     * Возвращает следующую строку в виде ассоциативного массива.
     *
     * @return array|null
     */
    public function fetchAssoc() {
        return $this->_result->fetch_assoc();
    }

    /**
     * Возвращает следующую строку в виде обычного массива.
     *
     * @return array|null
     */
    public function fetchRow() {
        return $this->_result->fetch_row();
    }

    /**
     * Возвращает количество строк в результате.
     *
     * @return integer
     */
    public function rowsCount() {
        return $this->_result->num_rows;
    }

    /**
     * Освобождает память, занятую результатом запроса.
     */
    public function __destruct() {
        if ($this->_result instanceof \mysqli_result) {
            $this->_result->free();
        }
    }

    /**
     * Возвращает оригинальный результат mysqli.
     *
     * @return \mysqli_result
     */
    public function getOriginalResult() {
        return $this->_result;
    }
}