<form name="selform" action="index.php?view=cron" method="post">
    <table id="listTable" class="tablesorter" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px">
        <thead>
            <tr>
                <th class="lt_header" width="25">id</th>
                <th class="lt_header" width="80">{$lang->title}</th>
                <th class="lt_header" width="">{$lang->description}</th>
                <th class="lt_header" width="30">{$lang->ad_mission_interval}</th>
                <th class="lt_header" width="100">{$lang->ad_last_start}</th>
                <th class="lt_header" width="50">{$lang->ad_is_active}</th>
                <th class="lt_header" align="center" width="65">{$lang->ad_actions}</th>
            </tr>
        </thead>
            {if !empty($items)}
            <tbody>
                {foreach from=$items key=num item=item}
                    <tr id="{$item.id}" class="item_tr">
                        <td>{$item.id}</td>
                        <td>
                            <a title="{$lang->ad_edit_mission}" href="{$edit_uri}/{$item.id}">{$item.name}</a>
                        </td>
                        <td>{$item.comment}</td>
                        <td>{$item.job_interval} {$lang->hour}</td>
                        <td>{$item.run_date}</td>
                        <td>
                        {if !empty($item.is_enabled)}
                            <a class="uittip" id="publink{$item.id}" href="javascript:pub({$item.id}, '{$hide_uri}/{$item.id}', '{$show_uri}/{$item.id}', 'off', 'on');" title="{$lang->ad_do_disable}">
                                <img id="pub{$item.id}" border="0" src="/admin/images/actions/on.gif"/>
                            </a>
    <?php }
    else { ?>
                            <a class="uittip" id="publink{$item.id}" href="javascript:pub({$item.id}, '{$show_uri}/{$item.id}', '{$hide_uri}/{$item.id}', 'on', 'off');" title="{$lang->ad_do_enable}">
                                <img id="pub{$item.id}" border="0" src="/admin/images/actions/off.gif"/>
                            </a>
                        {/if}
                        </td>
                        <td align="right">
                            <div style="padding-right: 8px;">
                                <a class="uittip" title="{$lang->ad_perform_task}" onclick="jsmsg('{$lang->ad_perform_task} {$item.name}?', '{$execute_uri}/{$item.id}')" href="#">
                                    <img border="0" hspace="2" alt="{$lang->ad_perform_task}" src="/admin/images/actions/play.gif"/>
                                </a>
                                <a class="uittip" title="{$lang->ad_edit_mission}" href="{$edit_uri}/{$item.id}">
                                    <img border="0" hspace="2" alt="{$lang->ad_edit_mission}" src="/admin/images/actions/edit.gif"/>
                                </a>
                                <a class="uittip" title="{$lang->delete}" onclick="jsmsg('{$lang->ad_delete_task} {$item.name}?', '{$delete_uri}/{$item.id})" href="#">
                                    <img border="0" hspace="2" alt="{$lang->delete}" src="/admin/images/actions/delete.gif"/>
                                </a>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        {else}
            <tbody>
                <tr><td colspan="7" style="padding-left:5px"><div style="padding:15px;padding-left:0px">{$lang->ad_tasks_notfound}</div></td></tr>
            </tbody>
        {/if}
    </table>

    <script type="text/javascript">highlightTableRows("listTable", "hoverRow", "clickedRow");</script>
</form>