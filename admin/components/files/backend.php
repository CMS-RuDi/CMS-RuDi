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

if (!defined('VALID_CMS_ADMIN')) {
    die('ACCESS DENIED');
}

$opt = $inPut->get('opt', 'str', 'config');

$toolmenu[] = array('icon' => 'save.gif', 'title' => $_LANG['SAVE'], 'link' => 'javascript:document.optform.submit();');
$toolmenu[] = array('icon' => 'cancel.gif', 'title' => $_LANG['CANCEL'], 'link' => '/cp/components');

cpToolMenu($toolmenu);

$cfg = $inCore->loadComponentConfig('files');

if ($opt == 'saveconfig') {
    if (!cmsUser::checkCsrfToken()) {
        cmsCore::error404();
    }

    $cfg = array(
        'check_link' => $inPut->get('check_link', 'int', 0),
        'redirect_time' => $inPut->get('redirect_time', 'int', 0),
        'file_time' => $inPut->get('file_time', 'int', 0),
        'white_list' => $inPut->get('white_list', 'str', '')
    );

    $inCore->saveComponentConfig('files', $cfg);

    cmsCore::addSessionMessage($_LANG['AD_CONFIG_SAVE_SUCCESS'], 'success');

    cmsCore::redirectBack();
}
?>
<form action="/cp/components/files" method="post" name="optform" id="form1">
    <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
    <table width="650" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong><?php echo $_LANG['AD_FILES_CHECK_LINK']; ?>:</strong></td>
            <td width="300" valign="top">
                <label><input name="check_link" type="radio" value="1" <?php
                    if ($cfg['check_link']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?></label>
                <label><input name="check_link" type="radio" value="0" <?php
                    if (!$cfg['check_link']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?></label>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_FILES_WHITE_LIST']; ?>: </strong><br><span class="hinttext"><?php echo $_LANG['AD_FILES_WHITE_LIST_HINT']; ?></span></td>
            <td valign="top">
                <input name="white_list" type="text" value="<?php echo htmlspecialchars($cfg['white_list']); ?>" style="width: 99%;"/>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_FILES_REDIRECT_TIME']; ?>:</strong></td>
            <td valign="top">
                <input name="redirect_time" size="5" class="uispin" type="text" value="<?php echo htmlspecialchars($cfg['redirect_time']); ?>"/>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_FILES_FILE_TIME']; ?>:</strong></td>
            <td valign="top">
                <input name="file_time" size="5" class="uispin" type="text" value="<?php echo htmlspecialchars($cfg['file_time']); ?>"/>
            </td>
        </tr>
    </table>
    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="<?php echo $_LANG['SAVE']; ?>" />
        <input name="back" type="button" id="back" value="<?php echo $_LANG['CANCEL']; ?>" onclick="window.location.href = '/cp/components';"/>
    </p>
</form>