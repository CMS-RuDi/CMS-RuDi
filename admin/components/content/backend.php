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

$opt = $inPut->get('opt', 'str', 'list');

echo '<h3>' . $_LANG['AD_SETTINGS'] . '</h3>';

$cfg = $inCore->loadComponentConfig('content');

if ($opt == 'saveconfig') {
    if (!cmsUser::checkCsrfToken()) {
        cmsCore::error404();
    }

    $cfg = array();
    $cfg['readdesc'] = $inPut->get('readdesc', 'int', 0);
    $cfg['is_url_cyrillic'] = $inPut->get('is_url_cyrillic', 'int', 0);
    $cfg['rating'] = $inPut->get('rating', 'int', 0);
    $cfg['perpage'] = $inPut->get('perpage', 'int', 0);
    $cfg['pt_show'] = $inPut->get('pt_show', 'int', 0);
    $cfg['pt_disp'] = $inPut->get('pt_disp', 'int', 0);
    $cfg['pt_hide'] = $inPut->get('pt_hide', 'int', 0);
    $cfg['autokeys'] = $inPut->get('autokeys', 'int', 0);
    $cfg['hide_root'] = $inPut->get('hide_root', 'int', 0);
    $cfg['img_small_w'] = $inPut->get('img_small_w', 'int', 100);
    $cfg['img_big_w'] = $inPut->get('img_big_w', 'int', 200);
    $cfg['img_sqr'] = $inPut->get('img_sqr', 'int', 1);
    $cfg['img_users'] = $inPut->get('img_users', 'int', 1);
    $cfg['watermark'] = $inPut->get('watermark', 'int', 0);
    $cfg['watermark_only_big'] = $inPut->get('watermark_only_big', 'int', 0);

    $inCore->saveComponentConfig('content', $cfg);

    cmsCore::addSessionMessage($_LANG['AD_CONFIG_SAVE_SUCCESS'], 'success');

    cmsCore::redirect('/cp/components/content?opt=config');
}

require('../includes/jwtabs.php');
$GLOBALS['cp_page_head'][] = jwHeader();
?>

<form action="/cp/components/content" method="post" name="optform" target="_self" id="form1">
    <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
    <?php ob_start(); ?>
    {tab=<?php echo $_LANG['AD_OVERALL']; ?>}
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong><?php echo $_LANG['AD_GENERATE_CYRYLLIC_URL']; ?>: </strong></td>
            <td width="110">
                <label><input name="is_url_cyrillic" type="radio" value="1" <?php
                    if ($cfg['is_url_cyrillic']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="is_url_cyrillic" type="radio" value="0" <?php
                    if (!$cfg['is_url_cyrillic']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_OUTPUT_ANNOUNCEMENTS']; ?>: </strong></td>
            <td width="110">
                <label><input name="readdesc" type="radio" value="1" <?php
                    if ($cfg['readdesc']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="readdesc" type="radio" value="0" <?php
                    if (!$cfg['readdesc']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['ARTICLES_RATING']; ?>: </strong></td>
            <td>
                <label><input name="rating" type="radio" value="1" <?php
                    if ($cfg['rating']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="rating" type="radio" value="0" <?php
                    if (!$cfg['rating']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?></label>
            </td>
        </tr>
        <tr>
            <td>
                <strong><?php echo $_LANG['AD_GENERATE_KEY_DESCR']; ?>:</strong>
            </td>
            <td valign="top">
                <label><input name="autokeys" type="radio" value="1" <?php
                    if ($cfg['autokeys']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="autokeys" type="radio" value="0" <?php
                    if (!$cfg['autokeys']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td>
                <strong><?php echo $_LANG['AD_HIDE_ROOT_CATS']; ?>:</strong>
            </td>
            <td valign="top">
                <label><input name="hide_root" type="radio" value="1" <?php
                    if ($cfg['hide_root']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="hide_root" type="radio" value="0" <?php
                    if (!$cfg['hide_root']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_NUMBER_PER_PAGE']; ?>: </strong></td>
            <td><input class="uispin" name="perpage" type="text" id="perpage" value="<?php echo $cfg['perpage']; ?>" size="5" /></td>
        </tr>
    </table>
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong><?php echo $_LANG['AD_SHOW_CONTENT']; ?>: </strong></td>
            <td width="110">
                <label><input name="pt_show" type="radio" value="1" <?php
                    if ($cfg['pt_show']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="pt_show" type="radio" value="0" <?php
                    if (!$cfg['pt_show']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_DEPLOY_CONTENT']; ?>: </strong></td>
            <td>
                <label><input name="pt_disp" type="radio" value="1" <?php
                    if ($cfg['pt_disp']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="pt_disp" type="radio" value="0" <?php
                    if (!$cfg['pt_disp']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_HIDE CONTENT']; ?>: </strong></td>
            <td>
                <label><input name="pt_hide" type="radio" value="1" <?php
                    if ($cfg['pt_hide']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="pt_hide" type="radio" value="0" <?php
                    if (!$cfg['pt_hide']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
    </table>
    {tab=<?php echo $_LANG['AD_PHOTO_ART']; ?>}
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong><?php echo $_LANG['AD_PHOTO_SMALL']; ?>:</strong></td>
            <td width="120">
                <input class="uispin" name="img_small_w" type="text" id="img_small_w" value="<?php echo $cfg['img_small_w']; ?>" size="5" />
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_PHOTO_BIG']; ?>:</strong></td>
            <td>
                <input class="uispin" name="img_big_w" type="text" id="img_big_w" value="<?php echo $cfg['img_big_w']; ?>" size="5" />
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_PHOTO_SQUARE']; ?>:</strong></td>
            <td>
                <label><input name="img_sqr" type="radio" value="1" <?php
                    if ($cfg['img_sqr']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="img_sqr" type="radio" value="0" <?php
                    if (!$cfg['img_sqr']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td>
                <strong><?php echo $_LANG['AD_ALLOW_USERS_TO']; ?>:</strong><br/>
                <span class="hinttext"><?php echo $_LANG['AD_ALLOW_USERS_TO_HINT']; ?></span>
            </td>
            <td>
                <label><input name="img_users" type="radio" value="1" <?php
                    if ($cfg['img_users']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="img_users" type="radio" value="0" <?php
                    if (!$cfg['img_users']) {
                        echo 'checked="checked"';
                    }
                    ?>/> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo $_LANG['AD_ENABLE_WATERMARK']; ?></strong><br />
                <span class="hinttext"><?php echo $_LANG['AD_WATERMARK_HINT']; ?> "<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>"</span></td>
            <td width="260">
                <label><input name="watermark" type="radio" value="1" <?php
                    if ($cfg['watermark']) {
                        echo 'checked="checked"';
                    }
                    ?> /> <?php echo $_LANG['YES']; ?> </label>
                <label><input name="watermark" type="radio" value="0"  <?php
                    if (!$cfg['watermark']) {
                        echo 'checked="checked"';
                    }
                    ?> /> <?php echo $_LANG['NO']; ?> </label>
            </td>
        </tr>
    </table>
    {/tabs}
    <?php echo jwTabs(ob_get_clean()); ?>
    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="<?php echo $_LANG['SAVE']; ?>" />
        <input name="back" type="button" id="back" value="<?php echo $_LANG['CANCEL']; ?>" onclick="window.location.href = '/cp/components';"/>
    </p>
</form>