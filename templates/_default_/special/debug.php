<?php
if ( $inConf->debug && $inUser->is_admin ) {
    $time = \cms\debug::getTime('cms');

    $debug       = \cms\debug::getDebugInfo();
    $debug_tabs  = \cms\debug::getDebagTargets();
    $debug_times = \cms\debug::getTotalRunTime();
    ?>
    <div class="debug_info">
        <div class="debug_time">
            <?php echo $_LANG['DEBUG_TIME_GEN_PAGE'] . ' ' . $time . ' ' . $_LANG['DEBUG_SEC']; ?>
        </div>

        <div class="debug_memory">
            <?php echo $_LANG['DEBUG_MEMORY'] . ' ' . round(@memory_get_usage() / 1024 / 1024, 2) . ' ' . $_LANG['SIZE_MB']; ?>
        </div>

        <?php foreach ( $debug_tabs as $name => $tab ) {
            if ( $tab['count'] > 0 ) {
                ?>
                <div class="debug_query_count">
                    <a href="#debug_show_<?php echo $name; ?>" class="ajaxlink debug_dump"><?php echo $tab['title'] . ' ' . $tab['count']; ?></a>
                </div>
            <?php }
        }
        ?>

    <?php foreach ( $debug as $name => $data ) {
        if ( !empty($data) ) {
            ?>
                <div class="debug_dump_block">
                    <div id="debug_show_<?php echo $name; ?>" class="debug_show_block">
                            <?php foreach ( $data as $dump ) { ?>
                            <div class="query">
                                <?php if ( !empty($dump['src']) ) { ?>
                                    <div class="src"><?php echo $dump['src']; ?></div>
                                <?php } ?>

                                <?php if ( !empty($dump['text']) ) { ?>
                                    <?php echo nl2br($dump['text']); ?>
                                <?php } ?>

                            <?php if ( !empty($dump['time']) ) { ?>
                                    <div class="query_time"><?php echo $_LANG['DEBUG_QUERY_TIME']; ?> <span class="<?php echo (($dump['time'] >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo $dump['time'] . '</span> ' . $_LANG['DEBUG_SEC'] ?></div>
                            <?php } ?>
                            </div>
            <?php } ?>

                        <?php if ( !empty($debug_times[$name]) ) { ?>
                            <div class="query">
                                <b><?php echo $_LANG['DEBUG_QUERY_TIME']; ?>: </b> <span class="<?php echo (($debug_times[$name] >= 0.1) ? 'red_query' : 'green_query'); ?>"><?php echo $debug_times[$name] . '</span> ' . $_LANG['DEBUG_SEC'] ?>
                            </div>
                <?php } ?>
                    </div>
                </div>
        <?php }
    }
    ?>
    </div>
    <script>
        $(function () {
            $('.debug_dump').colorbox({inline: true, width: "70%", maxHeight: "100%", transition: "none"});
        });
    </script>
    <?php
}