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

class cmsCron
{

    private static $instance;

    private function __construct()
    {

    }

    private function __clone()
    {

    }

    public static function getInstance()
    {
        if ( self::$instance === null ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Регистрирует новую задачу СRON
     * @param str $job_name
     * @param array $job (interval, component, model_method, custom_file, comment)
     * @return bool
     */
    public static function registerJob($job_name, $job)
    {
        $db = \cms\db::getInstance();

        if ( !isset($job['enabled']) ) {
            $job['enabled'] = 1;
        }

        if ( !isset($job['class_name']) ) {
            $job['class_name'] = '';
        }

        if ( !isset($job['class_method']) ) {
            $job['class_method'] = '';
        }

        $db->insert('cron_jobs', array(
            'job_name'     => $job_name,
            'job_interval' => $job['interval'],
            'job_run_date' => date('Y-m-d H:i:s'),
            'component'    => $job['component'],
            'model_method' => $job['model_method'],
            'custom_file'  => $job['custom_file'],
            'is_enabled'   => $job['enabled'],
            'is_new'       => 1,
            'comment'      => $job['comment'],
            'class_name'   => $job['class_name'],
            'class_method' => $job['class_method']
        ));

        return true;
    }

    /**
     * Обновляет описание задачи СRON
     * @param int $job_id
     * @param array $job (interval, component, model_method, custom_file, comment, enabled)
     * @return bool
     */
    public static function updateJob($job_id, $job)
    {
        unset($job['id']);

        return \cms\db::getInstance()->update('cron_jobs', (int) $job_id, $job);
    }

    /**
     * Находит описание задачи CRON по названию
     * @param str $job_name
     * @param bool $only_enabled
     * @return array | false
     */
    public static function getJob($job_name, $only_enabled = true)
    {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $db->filterEqual('job_name', $job_name);

        if ( $only_enabled ) {
            $db->filterEqual('is_enabled', 1);
        }

        $job = $db->getItem('cron_jobs');

        $db->resetFilters();

        return $job;
    }

    /**
     * Находит описание задачи CRON по id
     * @param int $job_id
     * @return array | false
     */
    public static function getJobById($job_id)
    {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $job = $db->getItemById('cron_jobs', (int) $job_id);

        $db->resetFilters();

        return $job;
    }

    /**
     * Возвращает список задач CRON
     * @param bool $only_enabled Только активные
     * @param bool $only_custom Только задачи выполнения скрипта
     * @return array
     */
    public static function getJobs($only_enabled = true, $only_custom = false)
    {
        $db = \cms\db::getInstance();

        $db->resetFilters();

        $db->select('i.id')
                ->select('i.job_name', 'name')
                ->select('i.job_interval')
                ->select('i.job_run_date', 'run_date')
                ->select('i.component')
                ->select('i.model_method')
                ->select('i.custom_file')
                ->select('i.is_enabled')
                ->select('i.is_new')
                ->select('i.comment')
                ->select('i.class_name')
                ->select('i.class_method');

        if ( $only_enabled ) {
            $db->filterEqual('is_enabled', 1);
        }

        if ( $only_custom ) {
            $db->filterEqual('component', '')
                    ->filterEqual('model_method', '')
                    ->filterEqual('class_name', '')
                    ->filterEqual('class_method', '');
        }

        $db->orderBy('job_run_date', 'ASC');

        $jobs = $db->get('cron_jobs', false, '');

        if ( !$jobs ) {
            return false;
        }

        foreach ( $jobs as $key => $job ) {
            $jobs[$key]['hours_ago'] = round((time() - strtotime($job['run_date'])) / 3600, 2);
        }

        return $jobs;
    }

    /**
     * Удаляет задачу CRON
     * @param string $job_name
     * @return bool
     */
    public static function removeJob($job_name)
    {
        return \cms\db::getInstance()->filterEqual('job_name', $job_name)->deleteFiltered('cron_jobs');
    }

    /**
     * Удаляет задачу CRON по id
     * @param int $job_id
     * @return bool
     */
    public static function removeJobById($job_id)
    {
        return \cms\db::getInstance()->delete('cron_jobs', (int) $job_id);
    }

    /**
     * Изменяет активность задачи
     * @param int $job_id ID задачи
     * @param bool $is_enabled Активность
     * @return bool
     */
    public static function jobEnabled($job_id, $is_enabled)
    {
        $is_enabled = (int) $is_enabled;

        \cms\db::getInstance()->update('cron_jobs', (int) $job_id, array(
            'is_enabled' => $is_enabled
        ));

        return true;
    }

    /**
     * Отмечает задачу как успешно выполненную
     * @param int $job_id ID задачи
     * @return bool
     */
    public static function jobSuccess($job_id)
    {
        \cms\db::getInstance()->update('cron_jobs', (int) $job_id, array(
            'job_run_date' => date('Y-m-d H:i:s'),
            'is_new'       => 0
        ));

        return true;
    }

    /**
     * Выполняет задачу с указанным именем
     * @param str $job_name
     * @return bool
     */
    public static function executeJobByName($job_name)
    {
        $job = self::getJob($job_name);
        return self::executeJob($job);
    }

    /**
     * Выполняет задачу с указанным ID
     * @param int $job_id
     * @return bool
     */
    public static function executeJobById($job_id)
    {
        $job = self::getJobById($job_id);
        return self::executeJob($job);
    }

    /**
     * Выполняет переданную задачу
     * @param array $job
     * @return bool
     */
    public static function executeJob($job)
    {
        $job_result = true;


        //========================  внешний php-файл  ========================//

        if ( $job['custom_file'] ) {
            cmsCore::includeFile(ltrim($job['custom_file'], '/'));
        }


        //============================  метод модели =========================//

        if ( $job['component'] && $job['model_method'] ) {
            $classname = "cms_model_" . $job['component'];

            if ( class_exists($classname) ) {
                $model = new $classname();

                if ( method_exists($model, $job['model_method']) ) {
                    $job_result = call_user_func(array( $model, $job['model_method'] ));
                }
            }
        }

        //===========================  метод класса ==========================//

        if ( $job['class_name'] && $job['class_method'] ) {
            $classfile = '';

            if ( !mb_strstr($job['class_name'], '|') ) {
                $classname = $job['class_name'];
            }
            else {
                $job['class_name'] = explode('|', $job['class_name']);
                $classfile         = $job['class_name'][0];
                $classname         = $job['class_name'][1];
            }

            if ( class_exists($classname) ) {
                if ( method_exists($classname, $job['class_method']) ) {
                    $job_result = $job_result && call_user_func(array( $classname, $job['class_method'] ));
                }
            }
        }

        if ( $job_result ) {
            self::jobSuccess($job['id']);
        }

        return $job_result;
    }

}
