{if $ostatok}<h3>{$lang->ad_edit_group} {$ostatok}</h3>{/if}

<form id="addform" name="addform" method="post" action="{$submit_uri}">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    <table width="660" border="0" cellspacing="5" class="proptable">
        <tr>
            <td width="198" valign="top">
                <div><strong>{$lang->ad_group_name}: </strong></div>
                <span class="hinttext">{$lang->ad_view_site}</span>
            </td>
            <td width="475" valign="top">
                <input name="title" type="text" id="title" size="30" value="{$item.title|escape:'html'}"/>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <div><strong>{$lang->ad_alias}:</strong></div>
                {if $item.id > 0}
                <span class="hinttext">{$lang->ad_dont_change}</span>
                {/if}
            </td>
            <td valign="top">
                <input name="alias" type="text" id="title3" {if $item.alias == 'guest'}readonly{/if} size="30" value="{$item.alias}"/>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_if_admin}</strong></td>
            <td>
                <label><input name="is_admin" type="radio" value="1" {if !empty($item.is_admin)}checked{/if} onclick="$('#accesstable').hide();$('#admin_accesstable').show();"/> {$lang->yes} </label>
                <label><input name="is_admin" type="radio" value="0" {if empty($item.is_admin)}checked{/if} onclick="$('#accesstable').show();$('#admin_accesstable').hide();"/> {$lang->no}</label>
            </td>
        </tr>
    </table>

<!---------------------------------------------------------------->

    <table width="660" border="0" cellspacing="5" class="proptable" id="admin_accesstable" style="{if empty($item.is_admin)}display:none;{/if}">
        <tr>
            <td width="191" valign="top">
                <div><strong>{$lang->ad_available_sections} </strong></div>
                <span class="hinttext">{$lang->ad_all_sections}</span>
            </td>
            <td width="475" valign="top">
                <table width="100%" border="0" cellspacing="2" cellpadding="0">
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_menu" value="admin/menu" {if $item.id > 0 and 'admin/menu' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_menu">{$lang->ad_menu_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_modules" value="admin/modules" {if $item.id > 0 and 'admin/modules' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_modules">{$lang->ad_modules_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_content" value="admin/content" {if $item.id > 0 and 'admin/content' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_content">{$lang->ad_contents_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_plugins" value="admin/plugins" {if $item.id > 0 and 'admin/plugins' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_plugins">{$lang->ad_plugins_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_filters" value="admin/filters" {if $item.id > 0 and 'admin/filters' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_filters">{$lang->ad_filters_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_components" value="admin/components" {if $item.id > 0 and 'admin/components' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_components">{$lang->ad_components_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_users" value="admin/users" {if $item.id > 0 and 'admin/users' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_users">{$lang->ad_users_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_config" value="admin/config" {if $item.id > 0 and 'admin/config' is in $item.access}checked{/if}/>
                        </td>
                        <td><label for="admin_config">{$lang->ad_settings_control}</label></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <div><strong>{$lang->ad_components_settings_free} </strong></div>
                <span class="hinttext">{$lang->ad_components_settings_on}</span>
            </td>
            <td valign="top">
                <table width="100%" border="0" cellspacing="2" cellpadding="0">
                    {foreach $components as $com}
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_com_<?php echo $com['link']; ?>" value="admin/com_{$com.link}" {if $item.id > 0 and "admin/com_{$com.link}" is in $item.access}checked{/if} />
                        </td>
                        <td><label for="admin_com_{$com.link}">{$com.title}</label></td>
                    </tr>
                    {/foreach}
                </table>
            </td>
        </tr>
    </table>

<!---------------------------------------------------------------->

    <table width="660" border="0" cellspacing="5" class="proptable" id="accesstable" style="{if !empty($item.is_admin)}display:none;{/if}">
        <tr>
            <td width="191" valign="top"><strong>{$lang->ad_group_rule} </strong></td>
            <td width="475" valign="top">
                <table width="100%" border="0" cellspacing="2" cellpadding="0">
                    {foreach $group_access as $ga}
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="{$ga.access_type|replace:'/':'_'}" value="{$ga.access_type}" {if $item.id > 0 and $ga.access_type is in $item.access}checked{/if}/>
                        </td>
                        <td>
                            <label for="{$ga.access_type|replace:'/':'_'}">{$ga.access_name}</label>
                        </td>
                    </tr>
                    {/foreach}
                </table>
            </td>
        </tr>
    </table>

<!---------------------------------------------------------------->

    <p>
        <input name="add_mod" type="submit" id="add_mod" value="{if $item.id > 0}{$lang->save}{else}{$lang->ad_create_group}{/if}"/>
        <span style="margin-top:15px">
            <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.history.back();"/>
        </span>
    </p>
</form>
