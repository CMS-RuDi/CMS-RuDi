<?php

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Based on InstantCMS v1.10.7 by InstantSoft
 *          (Original code © InstantCMS Team, 2007-2016)
 *          (Modifications © CMS RuDi Team / DagIT, 2026)
 *
 *                      LICENSED BY GNU/GPL v2
 */

define('PATH', __DIR__ . '/../..');
define('VALID_CMS_ADMIN', 1);

include(PATH . '/core/ajax/ajax_core.php');

cmsCore::loadLanguage('admin/lang');

if (!$inUser->is_admin) {
    cmsCore::halt($_LANG['ACCESS_DENIED']);
}

$inPut = \cms\request::getInstance();

$lang = $inPut->get('lang', 'str', '');
$target_id = $inPut->get('target_id', 'int', 0);
$target = preg_replace('/[^a-z0-9_\-]/i', '', $inPut->get('target', 'str', ''));
$field = preg_replace('/[^a-z0-9_\-]/i', '', $inPut->get('field', 'str', ''));

$langs = \cms\helper\files::getDirsList(PATH . '/languages');

if (!in_array($lang, $langs) || !$target_id || !$target || !$field) {
    cmsCore::halt(1);
}

$inDB = \cms\db::getInstance();

$fields = translations::getFields($target);

if (!isset($fields[$field])) {
    cmsCore::halt(2);
}

$type = $fields[$field];

// получаем все данные по $target_id и $target
$translation = translations::getTranslation($lang, $target, $target_id);

// получаем текущее значение поля
if ($translation && isset($translation['data'][$field])) {
    $value = $translation['data'][$field];
} else {
    $value = '';
}

if ($inPut->has('save')) {
    if (!cmsUser::checkCsrfToken()) {
        cmsCore::halt();
    }

    $field_data = $inPut->get('field_data', $type, '');

    // если есть запись, обновляем
    if ($translation) {
        if ($field_data) {
            $translation['data'][$field] = $field_data;
        }

        if (!$field_data && isset($translation['data'][$field])) {
            unset($translation['data'][$field]);
        }

        $inDB->update('translations', $translation['id'], array(
            'data' => cmsCore::arrayToYaml($translation['data'])
        ));
    }
    // нет - добавляем
    else {
        $inDB->insert('translations', array(
            'data' => cmsCore::arrayToYaml(array($field => $field_data)),
            'lang' => $lang,
            'fieldsset_id' => translations::getFieldsetId($target),
            'target_id' => $target_id,
        ));
    }

    cmsCore::halt();
} else {
    cmsPage::includeTemplateFile('admin/translations.php', array(
        'type' => $type,
        'value' => $value,
        'action' => $_SERVER['REQUEST_URI']
    ));
}