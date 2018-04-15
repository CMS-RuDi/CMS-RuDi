<br/>
<a href="#debug_block" class="ajaxlink debug_block_show">Debug:</a>
<span class="uittip" title="{$lang->debug_time_gen_page|escape:'string'}">{$time}{$lang->debug_sec}</span>
 — <span class="uittip" title="{$lang->debug_memory|escape:'string'}">{$memory}{$lang->size_mb}</span>

<div style="display:none;">
    <div id="debug_block">
        <div class="debug_tabs uitabs">
            <ul>
                {foreach from=$debug_tabs key=name item=tab}
                    {if $tab.count > 0}
                        <li><a href="#debug_tab_{$name}">{$tab.title} {$tab.count}</a></li>
                    {/if}
                {/foreach}
            </ul>
            {foreach from=$debug key=name item=data}
                {if !empty($data)}
                    <div id="debug_tab_{$name}" class="debug_info">
                        {foreach from=$data item=dump}
                            <div class="query">
                                {if !empty($dump.src)}
                                    <div class="src">{$dump.src}</div>
                                {/if}

                                {if !empty($dump.text)}
                                    {$dump.text|nl2br}
                                {/if}

                                {if !empty($dump.time)}
                                    <div class="query_time">{$lang->debug_query_time} <span class="{if $dump.time >= 0.1}red_query{else}green_query{/if}">{$dump.time}</span> {$lang->debug_sec}</div>
                                {/if}
                            </div>
                        {/foreach}

                        {if !empty($debug_times[$name])}
                            <div class="query">
                                <b>{$lang->debug_query_time}: </b> <span class="{if $debug_times[$name] >= 0.1}red_query{else}green_query{/if}">{$debug_times[$name]}</span> {$lang->debug_sec}
                            </div>
                        {/if}
                    </div>
                {/if}
            {/foreach}
        </div>
    </div>
</div>

<script>
    $(function () {
        $('.debug_block_show').colorbox({ inline: true, width: "70%", maxHeight: "100%", transition: "none" });
    });
</script>