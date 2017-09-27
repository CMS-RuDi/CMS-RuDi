<?php
$time = $inCore->getGenTime();

$qdump       = cmsCore::getDebugInfo('queries');
$total_qtime = cmsCore::getTotalRunTime('queries');

$pdump       = cmsCore::getDebugInfo('plugins');
$total_ptime = cmsCore::getTotalRunTime('plugins');

$mdump       = cmsCore::getDebugInfo('modules');
$total_mtime = cmsCore::getTotalRunTime('modules');

$errors = cmsCore::getDebugInfo('_error_');
?>
<div class="debug_info">
    <div class="debug_time">
        <?php echo $_LANG['DEBUG_TIME_GEN_PAGE'] . ' ' . number_format($time, 4) . ' ' . $_LANG['DEBUG_SEC']; ?>
    </div>

    <div class="debug_memory">
        <?php echo $_LANG['DEBUG_MEMORY'] . ' ' . round(@memory_get_usage() / 1024 / 1024, 2) . ' ' . $_LANG['SIZE_MB']; ?>
    </div>

    <div class="debug_query_count">
        <a href="#debug_query_show" class="ajaxlink debug_query_dump"><?php echo $_LANG['DEBUG_QUERY_DB'] . ' ' . count($qdump); ?></a>
    </div>

    <div class="debug_events_count">
        <a href="#debug_events_show" class="ajaxlink debug_events_dump"><?php echo $_LANG['DEBUG_EVENTS'] . ' ' . count($pdump); ?></a>
    </div>

    <div class="debug_modules_count">
        <a href="#debug_modules_show" class="ajaxlink debug_modules_dump"><?php echo $_LANG['DEBUG_MODULES'] . ' ' . count($mdump); ?></a>
    </div>

    <?php if ( !empty($errors) ) { ?>
        <div class="debug_notices_count">
            <a href="#debug_notices_show" class="ajaxlink debug_notices_dump"><?php echo $_LANG['DEBUG_NOTICE'] . ' ' . count($errors); ?></a>
        </div>
    <?php } ?>

    <div id="debug_query_dump">
        <div id="debug_query_show">
            <?php foreach ( $qdump as $dump ) { ?>
                <div class="query">
                    <div class="src"><?php echo $dump['src']; ?></div>

                    <?php echo nl2br($dump['text']); ?>

                    <div class="query_time"><?php echo $_LANG['DEBUG_QUERY_TIME']; ?> <span class="<?php echo (($dump['time'] >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo number_format($dump['time'], 5) . '</span> ' . $_LANG['DEBUG_SEC'] ?></div>
                </div>
            <?php } ?>

            <div class="query">
                <b><?php echo $_LANG['DEBUG_QUERYS_TIME']; ?>: </b> <span class="<?php echo (($total_qtime >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo number_format($total_qtime, 5) . '</span> ' . $_LANG['DEBUG_SEC'] ?>
            </div>
        </div>
    </div>

    <div id="debug_events_dump">
        <div id="debug_events_show">
            <?php foreach ( $pdump as $dump ) { ?>
                <div class="query">
                    <div class="src"><?php echo $dump['src']; ?></div>

                    <?php echo $dump['text'] . ($dump['data']['active'] ? '<br><b>' . $_LANG['DEBUG_EVENT_ENABLED'] . '</b> ' . implode(', ', $dump['data']['active']) : ''); ?>

                    <?php if ( $dump['time'] !== false ) { ?>
                        <div class="query_time">
                            <?php echo $_LANG['DEBUG_EVENT_TIME']; ?> <span class="<?php echo (($dump['time'] >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo number_format($dump['time'], 5) . '</span> ' . $_LANG['DEBUG_SEC'] ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="query">
                <b><?php echo $_LANG['DEBUG_EVENT_TIME']; ?>: </b> <span class="<?php echo (($total_ptime >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo number_format($total_ptime, 5) . '</span> ' . $_LANG['DEBUG_SEC'] ?>
            </div>
        </div>
    </div>

    <div id="debug_modules_dump">
        <div id="debug_modules_show">
            <?php foreach ( $mdump as $dump ) { ?>
                <div class="query">
                    <div class="src"><?php echo $dump['src']; ?></div>

                    <b><?php echo $dump['data']['name']; ?></b> - <?php echo $dump['text'] . ' (' . $dump['data']['position'] . ')'; ?>

                    <?php if ( $dump['data']['empty_html'] ) { ?>
                        <div class="query_time">
                            <?php echo $_LANG['DEBUG_MODULE_NOT_DISPLAYED']; ?>
                        </div>
                    <?php } ?>

                    <?php if ( $dump['time'] !== false ) { ?>
                        <div class="query_time">
                            <?php echo $_LANG['DEBUG_MODULE_TIME']; ?> <span class="<?php echo (($dump['time'] >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo number_format($dump['time'], 5) . '</span> ' . $_LANG['DEBUG_SEC'] ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="query">
                <b><?php echo $_LANG['DEBUG_MODULES_TIME']; ?>: </b> <span class="<?php echo (($total_mtime >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo number_format($total_mtime, 5) . '</span> ' . $_LANG['DEBUG_SEC'] ?>
            </div>
        </div>
    </div>

    <?php if ( !empty($errors) ) { ?>
        <div id="debug_notices_dump">
            <div id="debug_notices_show">
                <?php foreach ( $errors as $error ) { ?>
                    <div class="query">
                        <div class="src"><?php echo $error['file'] . ' - LINE ' . $error['line']; ?></div>

                        <b><?php echo $error['type']; ?></b>

                        <?php if ( !empty($error['msg']) ) { ?>
                            <div class="query_time">
                                <?php echo $error['msg']; ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>
<script>
    $(function () {
        $('.debug_query_dump, .debug_events_dump, .debug_modules_dump, .debug_notices_dump').colorbox({inline: true, width: "70%", maxHeight: "100%", transition: "none"});
    });
</script>