<div style="margin-top:2px;padding:10px;border:dotted 1px silver; width:508px;background:#FFFFCC">
    <div style="font-weight:bold">{$lang->attention}!</div>
    <div>{$lang->ad_caution_info_0}</div>
    <div>{$lang->ad_caution_info_1}</div>
</div>
<form id="addform" name="addform" method="post" action="{$submit_uri}">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    <table width="530" border="0" cellspacing="5" class="proptable">
        <tr>
            <td width="150" valign="top"><div><strong>{$lang->ad_banlist_user}: </strong></div></td>
            <td valign="top">
                <select name="user_id" id="user_id" onchange="loadUserIp()" style="width: 250px;">
                    <option value="0"{if empty($item.user_id)} selected="selected"{/if}>{$lang->ad_whithout_user}</option>
                    {$users_list}
                </select>
            </td>
        </tr>
        <tr>
            <td valign="top"><strong>{$lang->ad_banlist_ip}:</strong></td>
            <td valign="top"><input name="ip" type="text" id="ip" style="width: 244px;" value="{$item.ip}"/></td>
        </tr>
        <tr>
            <td valign="top"><strong>{$lang->ad_banlist_cause}:</strong></td>
            <td valign="top">
                <textarea name="cause" style="width:240px" rows="5">{$item.cause}</textarea>
            </td>
        </tr>
        <tr>
            <td valign="top"><strong>{$lang->ad_ban_forever}</strong></td>
            <td valign="top"><input type="checkbox" name="forever" value="1"{if empty($item.int_num)} checked="checked"{/if}> onclick="$('tr.bantime').toggle();"/></td>
        </tr>
        <tr class="bantime">
            <td valign="top"><strong>{$lang->ad_ban_for_time}</strong> </td>

            <td valign="top"><p>
                    <input name="int_num" type="text" id="int_num" size="5" value="{$item.int_num}" />
                    
                    <select name="int_period" id="int_period">
                        <option value="MINUTE"{if $item.int_period == 'MINUTE'} selected="selected"{/if}>{$lang->minute10}</option>]
                        <option value="HOUR"{if $item.int_period == 'HOUR'} selected="selected"{/if}>{$lang->hour10}</option>
                        <option value="DAY"{if $item.int_period == 'DAY'} selected="selected"{/if}>{$lang->day10}</option>
                        <option value="MONTH"{if $item.int_period == 'MONTH'} selected="selected"{/if}>{$lang->month10}</option>
                    </select>
                </p>
                <p><label><input name="autodelete" type="checkbox" id="autodelete" value="1"{if !empty($item.autodelete)} checked="checked"{/if} /> {$lang->ad_remove_ban}</label></p>
            </td>
        </tr>
        {if empty($item.int_num)}<script type="text/javascript">$('tr.bantime').hide();</script>{/if}
    </table>
    <p>
        <label>
            <input name="add_mod" type="submit" id="add_mod" value="{if $do == 'add'}{$lang->ad_to_banlist_add}{else}{$lang->save}{/if}" />
        </label>
        <label>
            <span style="margin-top:15px">
                <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.history.back();"/>
            </span>
        </label>

        {if $do == 'edit'}<input name="item_id" type="hidden" value="{$item.id}" />{/if}
    </p>
</form>