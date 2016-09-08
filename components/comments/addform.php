<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.6                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2015                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/
define('PATH', __DIR__ .'/../..');

include(PATH .'/core/ajax/ajax_core.php');

$do         = cmsCore::request('action', 'str', 'add');
$target     = cmsCore::request('target', 'str', '');
$target_id  = cmsCore::request('target_id', 'int', 0);
$parent_id  = cmsCore::request('parent_id', 'int', 0);
$comment_id = cmsCore::request('id', 'int', 0);

if ($do == 'add' && (!$target || !$target_id)) {
    cmsCore::halt();
}

if ($do == 'edit' && !$comment_id) {
    cmsCore::halt();
}

$model = new cms_model_comments();

// Проверяем включен ли компонент
if (!$inCore->isComponentEnable('comments')) {
    cmsCore::halt();
}

// Инициализируем права доступа для группы текущего пользователя
$model->initAccess();

// Подключаем аякс сабмит формы
$inPage->addHeadJS('includes/jquery/jquery.form.js');

if ($do == 'edit') {
    // получаем комментарий
    $comment = $model->getComment($comment_id);
    
    if (!$comment) {
        cmsCore::halt();
    }

    $is_author = $comment['user_id'] == $inUser->id;

    // редактировать могут авторы (если время редактирования есть)
    // модераторы и администраторы
    if (!$model->is_can_moderate &&
                    !$inUser->is_admin &&
                    !($is_author && $comment['is_editable'])) {
        cmsCore::halt();
    }

    // Для авторов показываем сколько осталось для редактирования
    if ($is_author && $comment['is_editable'] && !$inUser->is_admin && !$model->is_can_moderate) {
            $notice = str_replace('{min}', cmsCore::spellCount($comment['is_editable'], $_LANG['MINUTE1'], $_LANG['MINUTE2'], $_LANG['MINUTE10']), $_LANG['EDIT_INFO']);
    }

}

if ($model->is_can_bbcode) {
    $bb_toolbar = cmsPage::getBBCodeToolbar('content', true, 'comments', 'comment', $comment_id);
    $smilies    = cmsPage::getSmilesPanel('content');
}

$karma_need   = $model->config['min_karma_add'];
$can_by_karma = (($model->config['min_karma'] && $inUser->karma>=$karma_need) || $inUser->is_admin);
$need_captcha = $model->config['regcap'] ? true : ($inUser->id ? false : true);

cmsPage::initTemplate('components', 'com_comments_add', array(
    'user_can_add'  => $model->is_can_add,
    'is_can_bbcode' => $model->is_can_bbcode,
    'do'            => $do,
    'comment'       => isset($comment) ? $comment : array(),
    'is_user'       => $inUser->id,
    'cfg'           => $model->config,
    'target'        => $target,
    'target_id'     => $target_id,
    'parent_id'     => $parent_id,
    'user_subscribed' => cmsUser::isSubscribed($inUser->id, $target, $target_id),
    'can_by_karma'  => $can_by_karma,
    'karma_need'    => $karma_need,
    'karma_has'     => $inUser->karma,
    'need_captcha'  => $need_captcha,
    'bb_toolbar'    => isset($bb_toolbar) ? $bb_toolbar :'',
    'smilies'       => isset($smilies) ? $smilies : '',
    'notice'        => isset($notice) ? $notice : ''
))->display();

cmsCore::halt();