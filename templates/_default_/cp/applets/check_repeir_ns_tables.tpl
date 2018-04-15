<h3>{$lang->ad_tree_full}</h3>

<div style="margin:20px; margin-top:0px;">';
    <form method="post" action="" id="repairform">
        <input id="go_repair" type="hidden" name="go_repair" value="0">
        <input id="go_repair_tree" type="hidden" name="go_repair_tree" value="0">

        <table cellpadding="2">
            {foreach from=$tables key=key item=table}
                <tr>
                    <td width="15">
                        {if $table.error}<input type="checkbox" name="tables[]" value="' . $id . '" checked="checked"/>{/if}
                    </td>
                    <td>
                        <div>
                            <span>{$table.title}</span> &mdash;
                            {if $table.error}
                                <span style="color:red">{$lang->ad_error_found}</span>
                            {else}
                                <span style="color:green">{$lang->ad_no_error_found}</span>
                            {/if}
                        </div>
                    </td>
                </tr>

                {if $table.error}{assign var='errors_found' value=1}{/if}
            {/foreach}
        </table>
        
        {if !empty($errors_found)}
            <div style="margin-bottom:20px">
                <input type="button" onclick="repairTreesRoot();" value="{$lang->ad_repair}" />
                <input type="button" onclick="repairTrees();" value="{$lang->ad_repair_totree}" />
            </div>
        {/if}
    </form>
</div>