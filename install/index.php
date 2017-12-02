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

session_start();
header('Content-Type: text/html; charset=utf-8');

define('VALID_CMS', 1);
define('PATH', $_SERVER['DOCUMENT_ROOT']);

require(PATH . '/core/classes/autoload.php');

$request      = \cms\request::getInstance();
$inConf       = cmsConfig::getInstance();
$inConf->lang = isset($_SESSION['inst_lang']) ? $_SESSION['inst_lang'] : $inConf->lang;

cmsCore::includeFile('install/function.php');

// Мультиязычная установка
$langs = cmsCore::getDirsList('/languages');

// запрос на смену языка
if ( $request->has('lang') ) {
    $inst_lang = $request->get('lang', 'html', 'ru');

    if ( in_array($inst_lang, $langs) ) {
        $_SESSION['inst_lang'] = $inst_lang;
        $inConf->lang          = $inst_lang;
    }
}

$l = \cms\lang::getInstance();
$l->setLocale();
$l->load('install');

$installed = false;

// Можно делать мультиязычные дампы
$sqldumpdemo  = 'sqldumpdemo.sql';
$sqldumpempty = 'sqldumpempty.sql';

if ( $inConf->lang != 'ru' ) {
    $sqldumpempty = (file_exists(PATH . '/install/sqldumpempty_' . $inConf->lang . '.sql')) ?
            'sqldumpempty_' . $inConf->lang . '.sql' : 'sqldumpempty.sql';
    $sqldumpdemo  = (file_exists(PATH . '/install/sqldumpdemo_' . $inConf->lang . '.sql')) ?
            'sqldumpdemo_' . $inConf->lang . '.sql' : $sqldumpempty;
}

//============================ процесс установки =============================//

if ( $request->has('install') ) {
    $errors = false;

    $_CFG['offtext']  = $l->cfg_offtext;
    $_CFG['keywords'] = $l->cfg_keywords;
    $_CFG['metadesc'] = $l->cfg_metadesc;

    $_CFG['sitename']  = $request->get('sitename', 'html', $l->cfg_sitename);
    $_CFG['db_host']   = $request->get('db_server', 'html', '');
    $_CFG['db_base']   = $request->get('db_base', 'html', '');
    $_CFG['db_user']   = $request->get('db_user', 'html', '');
    $_CFG['db_pass']   = $request->get('db_password', 'html', '');
    $_CFG['db_prefix'] = $request->get('db_prefix', 'html', '');
    $_CFG['lang']      = $inConf->lang; // Какой язык выбрали при установке, тот и будет сохранен в конфигурации
    $sql_file          = PATH . '/install/' . ($request->get('demodata', 'int') ? $sqldumpdemo : $sqldumpempty);

    $admin_login    = $request->get('admin_login', 'html', '');
    $admin_password = $request->get('admin_password', 'html', '');

    if ( !$_CFG['db_host'] ) {
        cmsCore::addSessionMessage($l->ins_db_host_empty, 'error');
        $errors = true;
    }

    if ( !$_CFG['db_base'] ) {
        cmsCore::addSessionMessage($l->ins_db_base_empty, 'error');
        $errors = true;
    }

    if ( !$_CFG['db_user'] ) {
        cmsCore::addSessionMessage($l->ins_db_user_empty, 'error');
        $errors = true;
    }

    if ( !$_CFG['db_prefix'] ) {
        cmsCore::addSessionMessage($l->ins_db_prefix_empty, 'error');
        $errors = true;
    }

    if ( mb_strlen($admin_login) < 3 ) {
        cmsCore::addSessionMessage($l->ins_admin_login_empty, 'error');
        $errors = true;
    }

    if ( mb_strlen($admin_password) < 6 ) {
        cmsCore::addSessionMessage($l->ins_admin_pass_empty, 'error');
        $errors = true;
    }

    if ( $errors ) {
        cmsCore::redirect('/install/');
    }

    $inConf->db_host   = $_CFG['db_host'];
    $inConf->db_user   = $_CFG['db_user'];
    $inConf->db_pass   = $_CFG['db_pass'];
    $inConf->db_base   = $_CFG['db_base'];
    $inConf->db_prefix = $_CFG['db_prefix'];

    $inDB = cmsDatabase::getInstance();

    $inDB->importFromFile($sql_file);

    $d_cfg = $inConf->getConfig();
    $_CFG  = array_merge($d_cfg, $_CFG);
    $inConf->saveToFile($_CFG);

    $sql = "UPDATE cms_users SET password = md5('" . $admin_password . "'), login = '" . $admin_login . "' WHERE id = 1";
    $inDB->query($sql);
    $sql = "UPDATE cms_users SET password = md5('" . $admin_password . "') WHERE id > 1";
    $inDB->query($sql);

    $installed = true;

    cmsCore::getInstance();
    $inUser = cmsUser::getInstance();
    $inUser->update();
    $inUser->signInUser($admin_login, $admin_password, true);
}

$info        = check_requirements();
$permissions = check_permissions();
$php_path    = get_program_path('php');
?>

<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $l->ins_header . ' ' . CORE_VERSION; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <script src='/includes/jquery/jquery.js' type='text/javascript'></script>
        <script src='/install/js/jquery.wizard.js' type='text/javascript'></script>
        <script src='/install/js/install.js' type='text/javascript'></script>
        <link type='text/css' href='/install/css/styles.css' rel='stylesheet' media='screen' />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>

    <body>
        <div class="wrap">
            <?php if ( sizeof($langs) > 1 ) { ?>
                <div title="<?php echo $l->template_interface_lang; ?>" id="langs" style="background-image:  url(/templates/_default_/images/icons/langs/<?php echo $inConf->lang; ?>.png);">
                    <span><?php echo $inConf->lang; ?></span>
                    <ul id="langs-select">
                        <?php
                        foreach ( $langs as $lng ) {
                            if ( $lng == $inConf->lang ) {
                                continue;
                            }
                            ?>
                            <li data-lang="<?php echo $lng; ?>" style="background-image:  url(/templates/_default_/images/icons/langs/<?php echo $lng; ?>.png);"><?php echo $lng; ?></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
            <h1 id="header">
                <?php echo $l->ins_header . ' ' . CORE_VERSION; ?>
            </h1>
            <?php if ( !$installed ) { ?>
                <!-- ================================================================ -->
                <form class="wizard" action="#" method="post" >
                    <div class="wizard-nav">
                        <a href="#start"><?php echo $l->ins_start; ?></a>
                        <a href="#php"><?php echo $l->ins_check_php_title; ?></a>
                        <a href="#folders"><?php echo $l->ins_check_folder_title; ?></a>
                        <a href="#install"><?php echo $l->ins_install; ?></a>
                    </div>
                    <?php $messages = cmsCore::getSessionMessages(); ?>
                    <?php if ( $messages ) { ?>
                        <div class="sess_messages">
                            <?php foreach ( $messages as $message ) { ?>
                                <?php echo $message; ?>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div id="start" class="wizardpage">
                        <h2><?php echo $l->ins_welcome; ?></h2>
                        <?php echo $l->ins_welcome_notes; ?>
                        <p>
                            <label><input type="checkbox" id="license_agree" onClick="checkAgree()" /><?php echo $l->ins_accept_license; ?></label>
                        </p>
                    </div>
                    <!-- ================================================================ -->
                    <div id="php" class="wizardpage">
                        <h2><?php echo $l->ins_check_php; ?></h2>
                        <p><?php echo $l->ins_checkphp_hint; ?></p>
                        <h3><?php echo $l->ins_php_version; ?></h3>
                        <table class="grid">
                            <tr>
                                <td><?php echo $l->ins_install_version; ?></td>
                                <td class="value">
                                    <?php echo html_bool_span($info['php']['version'], $info['php']['valid']); ?>
                                </td>
                            </tr>
                        </table>
                        <h3><?php echo $l->ins_need_extention; ?></h3>
                        <table class="grid">
                            <?php foreach ( $info['ext'] as $name => $valid ) { ?>
                                <tr>
                                    <td><a href="http://ru2.php.net/manual/ru/book.<?php echo str_replace('math', '', $name); ?>.php" target="_blank" title="<?php echo $l->ins_phpnet_hint; ?>"><?php echo $name; ?></a></td>
                                    <td class="value">
                                        <?php if ( $valid ) { ?>
                                            <?php echo html_bool_span($l->ins_install_ok, $valid); ?>
                                            <?php
                                        }
                                        else {
                                            ?>
                                            <?php echo html_bool_span($l->ins_install_notfound, $valid); ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <!-- ================================================================ -->
                    <div id="folders" class="wizardpage">
                        <h2><?php echo $l->ins_check_folder; ?></h2>
                        <?php echo $l->ins_folders_notes; ?>
                        <table class="grid">
                            <?php foreach ( $permissions as $name => $permission ) { ?>
                                <tr>
                                    <td>/<?php
                                        echo $name;
                                        echo $permission['perm'] ? ' | ' . $l->ins_permission . ' ' . $permission['perm'] : '';
                                        ?></td>
                                    <td class="value">
                                        <?php if ( $permission['valid'] ) { ?>
                                            <?php echo html_bool_span($l->ins_permission_ok, $permission['valid']); ?>
                                            <?php
                                        }
                                        else {
                                            ?>
                                            <?php echo html_bool_span($l->ins_permission_no, $permission['valid']); ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <!-- ================================================================ -->
                    <div id="install" class="wizardpage">
                        <h2><?php echo $l->ins_install; ?></h2>
                        <p><?php echo $l->ins_form_insert; ?></p>
                        <table class="instal_data">
                            <tr>
                                <td><?php echo $l->ins_form_site; ?></td>
                                <td><input name="sitename" type="text" class="txt" value="<?php echo $l->cfg_sitename; ?>"></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_form_login; ?></td>
                                <td><input name="admin_login" type="text" class="txt" value="admin"></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_form_pass; ?></td>
                                <td><input name="admin_password" type="password" placeholder="<?php echo $l->ins_admin_pass_6; ?>" class="txt"></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_form_mysql; ?></td>
                                <td align="center"><input name="db_server" type="text" class="txt" value="localhost"></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_form_bdname; ?></td>
                                <td><input name="db_base" type="text" class="txt"></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_form_bduser; ?></td>
                                <td><input name="db_user" type="text" class="txt" value=""></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_bdpass; ?> </td>
                                <td><input name="db_password" type="password" class="txt"></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_form_prefix; ?></td>
                                <td><input name="db_prefix" type="text" class="txt" value="cms"></td>
                            </tr>
                            <tr>
                                <td><?php echo $l->ins_form_demo; ?></td>
                                <td align="center" valign="top">
                                    <?php if ( $sqldumpdemo == $sqldumpempty ) { ?>
                                        <label><input disabled="true" name="demodata" type="radio" value="1" /><?php echo $l->yes; ?></label>
                                        <label><input disabled="true" name="demodata" type="radio" value="0" checked="true" /> <?php echo $l->no; ?></label>
                                        <?php
                                    }
                                    else {
                                        ?>
                                        <label><input name="demodata" type="radio" value="1" checked /><?php echo $l->yes; ?></label>
                                        <label><input name="demodata" type="radio" value="0" /> <?php echo $l->no; ?></label>
                                    <?php } ?>
                                </td>
                            </tr>
                        </table>
                        <div class="hint_text"><?php echo $l->ins_form_notes; ?></div>
                    </div>
                </form>
                <?php
            }
            else {
                ?>
                <div class="result_link">
                    <a href="/"><?php echo $l->ins_go_site; ?></a>
                    <a href="/admin"><?php echo $l->ins_go_cp; ?></a>
                    <a id="tutorial" target="_blank" href="http://www.instantcms.ru/wiki/doku.php"><?php echo $l->ins_go_handbook; ?></a>
                    <a id="tutorial" target="_blank" href="http://addons.instantcms.ru/"><?php echo $l->ins_go_addons; ?></a>
                    <a id="tutorial" target="_blank" href="https://github.com/instantsoft/icms1">GitHub</a>
                </div>
                <div class="sess_messages">
                    <div class="message_success"><?php echo $l->ins_form_success; ?></div>
                </div>
                <div class="wizardpage">
                    <h2><?php echo $l->ins_cron_todo; ?></h2>
                    <p>
                        <?php echo $l->ins_cron_notes; ?>
                    </p>
                    <pre><?php
                        if ( $php_path ) {
                            echo $php_path;
                        }
                        else {
                            ?>php<?php } ?> -f <?php echo PATH; ?>/cron.php <?php echo $_SERVER['HTTP_HOST']; ?> > /dev/null</pre>
                    <p>
                        <?php echo $l->ins_feedback_support; ?>
                    </p>
                    <h2><?php echo $l->ins_attention; ?></h2>
                    <p><?php echo $l->ins_delete_todo; ?></p>
                </div>
            <?php } ?>
        </div>
        <div id="footer">
            <div>
                <a href="http://www.instantcms.ru/" target="_blank">InstantCMS</a>, <a href="http://instantsoft.ru/" target="_blank">InstantSoft</a> &copy; 2007-<?php echo date('Y'); ?>
            </div>
        </div>
        <script>
<?php echo cmsPage::getLangJS('INS_DO_INSTALL'); ?>
<?php echo cmsPage::getLangJS('INS_NEXT'); ?>
<?php echo cmsPage::getLangJS('INS_BACK'); ?>
        </script>
    </body>
</html>