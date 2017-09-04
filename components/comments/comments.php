<?php

/*
 *                           InstantCMS v1.10.6
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2015
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

define('PATH', __DIR__ . '/../..');

include(PATH . '/core/ajax/ajax_core.php');

$model = new cms_model_comments();

// Проверяем включен ли компонент
if ( !$inCore->isComponentEnable('comments') ) {
    cmsCore::error404();
}

// Инициализируем права доступа для группы текущего пользователя
$model->initAccess();

$target     = cmsCore::request('target', 'str');
$target_id  = cmsCore::request('target_id', 'int');
$can_delete = cmsCore::request('target_author_can_delete', 'int');

if ( !$target || !$target_id ) {
    cmsCore::halt();
}

$model->whereTargetIs($target, $target_id);

$inDB->orderBy('c.pubdate', 'ASC');

$comments = $model->getComments(!($inUser->is_admin || $model->is_can_moderate), true);

cmsPage::initTemplate('components', 'com_comments_list', array(
    'comments_count'           => count($comments),
    'comments'                 => $comments,
    'user_can_moderate'        => $model->is_can_moderate,
    'user_can_delete'          => $model->is_can_delete,
    'target_author_can_delete' => $can_delete,
    'user_can_add'             => $model->is_can_add,
    'is_admin'                 => $inUser->is_admin,
    'is_user'                  => $inUser->id,
    'cfg'                      => $model->config,
    'labels'                   => $model->labels,
    'target'                   => $target,
    'target_id'                => $target_id,
))->display();

cmsCore::halt();
