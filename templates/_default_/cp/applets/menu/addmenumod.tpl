<form id="addform" name="addform" action="/cp/menu/submitmenu" method="post">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    
    <table class="proptable" width="650" cellspacing="10" cellpadding="10">
        <tr>
            <td width="300" valign="top">
                <strong>{$lang->ad_module_menu_title}</strong>
            </td>
            <td valign="top">
                <input name="title" type="text" id="title2" style="width:99%" value=""/>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <strong>{$lang->ad_menu_to_view}</strong><br/>
                <span class="hinttext">{$lang->ad_to_create_new_point}</span>
            </td>
            <td valign="top">
                <select name="menu" id="menu" style="width:99%">
                    {foreach from=$menu_list item=menu }
                        <option value="{$menu.id}">
                            {$menu.title}
                        </option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <strong>{$lang->ad_position_to_view}</strong><br />
                <span class="hinttext">{$lang->ad_position_must_be}</span>
            </td>
            <td valign="top">
                <select name="position" id="position" style="width:99%">
                    {if $pos}
                        {foreach from=$pos key=key item=position}
                            <option value="{$position}">{$position}</option>
                        {/foreach}
                    {/if}
                </select>
                <input name="is_external" type="hidden" id="is_external" value="0" />
            </td>
        </tr>
        <tr>
            <td valign="top"><strong>{$lang->ad_menu_public}</strong></td>
            <td valign="top">
                <label><input name="published" type="radio" value="1" checked="checked" /> {$lang->yes}</label>
                <label><input name="published" type="radio" value="0" checked="checked" /> {$lang->no}</label>
            </td>
        </tr>
        <tr>
            <td valign="top"><strong>{$lang->ad_prefix_css}</strong></td>
            <td valign="top">
                <input name="css_prefix" type="text" id="css_prefix" value="" style="width:99%" />
            </td>
        </tr>
        <tr>
            <td valign="top">
                <strong>{$lang->ad_tab_access}:</strong><br />
                <span class="hinttext">{$lang->ad_group_access}</span>
            </td>
            <td valign="top">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist" style="margin-top:5px">
                    <tr>
                        <td width="20">
                            <input name="is_public" type="checkbox" id="is_public" onclick="checkAccesList();" value="1" checked="checked" />
                        </td>
                        <td><label for="is_public"><strong>{$lang->ad_share}</strong></label></td>
                    </tr>
                </table>
                <div style="padding:5px">
                    <span class="hinttext">
                        {$lang->ad_view_if_check}
                    </span>
                </div>

                <div style="margin-top:10px;padding:5px;padding-right:0px;" id="grp">
                    <div>
                        <strong>{$lang->ad_groups_view}</strong><br />
                        <span class="hinttext">
                            {$lang->ad_select_multiple_ctrl}
                        </span>
                    </div>
                    <div>
                        <select style="width: 99%" name="allow_group[]" id="allow_group" size="6" multiple="multiple" disabled="disabled">
                            {foreach from=$groups item=group}
                                <option value="{$group.id}">{$group.title}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top">
                <div style="padding:10px;margin:4px;background-color:#EBEBEB;border:solid 1px gray">
                    {$lang->ad_new_menu_new_module}
                </div>
            </td>
        </tr>
    </table>
    <div style="margin-top:5px">
        <input name="save" type="submit" id="save" value="{$lang->ad_menu_add}" />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.location.href = '/cp/menu';" />
    </div>
</form>