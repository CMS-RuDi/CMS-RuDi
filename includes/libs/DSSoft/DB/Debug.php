<?php

namespace DSSoft\DB;

/**
 * Сборщик времени выполнения и журнала событий при работе с базой данных.
 */
class Debug {

    /**
     * Время запуска таймеров: имя => microtime(true).
     *
     * @var array
     */
    private $_timer = [];
    /**
     * Журнал событий.
     *
     * @var array
     */
    private $_log = [];

    /**
     * Запускает (перезапускает) таймер с указанным именем.
     *
     * @param string $name Название таймера
     * @return void
     */
    public function startTimer($name) {
        $this->_timer[$name] = microtime(true);
    }

    /**
     * Добавляет запись в журнал.
     *
     * @param string $name Название таймера или события
     * @param mixed $data Данные для журнала
     * @param boolean $error Признак ошибки
     * @return void
     */
    public function log($name, $data, $error = false) {
        // таймер мог быть не запущен (например, ошибка возникла до его запуска)
        $start_time = isset($this->_timer[$name]) ? $this->_timer[$name] : microtime(true);

        $this->_log[] = array(
            'start_time' => $start_time,
            'execution_time' => number_format(microtime(true) - $start_time, 5),
            'name' => $name,
            'data' => $data,
            'error' => $error
        );
    }

    /**
     * Добавляет в журнал запись об ошибке.
     *
     * @param string $name Название события
     * @param mixed $data Данные для журнала
     * @return void
     */
    public function error($name, $data) {
        $this->log($name, $data, true);
    }

    /**
     * Возвращает весь журнал.
     *
     * @return array
     */
    public function getLog() {
        return $this->_log;
    }

    /**
     * Возвращает только записи об ошибках.
     *
     * @return array
     */
    public function getErrorLog() {
        $error = [];

        foreach ($this->_log as $log) {
            if ($log['error'] == true) {
                $error[] = $log;
            }
        }

        return $error;
    }
}
