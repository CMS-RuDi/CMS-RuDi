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

// некоторые задачи требуют безлимитного времени выполнения, в cli это по умолчанию
// задача для CRON выглядит примерно так: php -f /path_to_site/cron.php site.ru
// где site.ru - имя вашего домена
// Если планируете запускать задачи CRON через curl или иные http запросы, закомментируйте строку ниже
if ( PHP_SAPI != 'cli' ) {
    die('Access denied');
}

define('PATH', __DIR__);
define('VALID_CMS', 1);

require(PATH . '/core/classes/autoload.php');

$inConf = cmsConfig::getInstance();

// Проверяем, что система установлена
if ( !$inConf->isReady() ) {
    die();
}

error_reporting(E_ALL);
set_error_handler(array( '\\cms\\debug', 'errorHandler' ));

cmsCore::getInstance();

\cms\events::call('start_cron');

$jobs = cmsCron::getJobs();

// если есть задачи
if ( is_array($jobs) ) {
    // выполняем их
    foreach ( $jobs as $job ) {
        // проверяем интервал запуска
        if ( !$job['job_interval'] || ($job['hours_ago'] > $job['job_interval']) || $job['is_new'] ) {
            // запускаем задачу
            cmsCron::executeJob($job);
        }
    }
}

\cms\events::call('end_cron');

cmsCore::halt();
