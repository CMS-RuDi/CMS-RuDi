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

cmsCore::includeFile('admin/includes/cp.php');

cmsCore::loadLanguage('admin/lang');
cmsCore::loadLanguage('admin/applets/applet_modules');

if (!$inUser->is_admin) {
    cmsCore::halt($_LANG['ACCESS_DENIED']);
}

$adminAccess = cmsUser::getAdminAccess();

if (!cmsUser::isAdminCan('admin/modules', $adminAccess)) {
    cmsCore::halt($_LANG['ACCESS_DENIED']);
}

$inDB = \cms\db::getInstance();
$inPut = \cms\request::getInstance();

$mod = $inDB->getItemById('modules', $inPut->get('id', 'int', 0));

if (!$mod) {
    cmsCore::halt();
}

$mod_name = $mod['user'] ? '' : preg_replace('/[^a-z0-9_\-]/iu', '', $mod['content']);

$xml_file = PATH . '/admin/modules/' . $mod_name . '/backend.xml';
$php_file = PATH . '/admin/modules/' . $mod_name . '/backend.php';

$mode = 'none';
$cfg_form = '';

if (file_exists($xml_file)) {
    $cfg = $inCore->loadModuleConfig($module_id);
    $formGen = new cmsFormGen($xml_file, $cfg);
    $cfg_form = $formGen->getHTML();
    $mode = 'xml';
} else if (file_exists($php_file)) {
    $mode = 'php';
} else if ($mod['user']) {
    $mode = 'custom';
}

cmsPage::includeTemplateFile('admin/modconfig.php');
