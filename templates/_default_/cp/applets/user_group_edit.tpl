<form id="addform" name="addform" method="post" action="{$submit_uri}">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    
    <table width="660" border="0" cellspacing="5" class="proptable">
        <tr>
            <td width="198" valign="top">
                <div><strong>{$lang->ad_group_name}: </strong></div><span class="hinttext">{$lang->ad_view_site}</span>
            </td>
            <td width="475" valign="top">
                <input name="title" type="text" id="title" size="30" value="{$item.title|escape:'html'}" />
            </td>
        </tr>
        <tr>
            <td valign="top">
                <div><strong>{$lang->ad_alias}:</strong></div>{if $do == 'edit'}<span class="hinttext">{$lang->ad_dont_change}</span>{/if}
            </td>
            <td valign="top">
                <input name="alias" type="text" id="title3"{if $item.alias == 'guest'} readonly="readonly"{/if} size="30" value="{$item.alias}"/>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_if_admin}</strong></td>
            <td>
                <label><input name="is_admin" type="radio" value="1"{if !empty($item.is_admin)} checked="checked"{/if} onclick="$('#accesstable').hide();$('#admin_accesstable').show();"/> {$lang->yes} </label>
                <label><input name="is_admin" type="radio" value="0"{if empty($item.is_admin)} checked="checked"{/if} onclick="$('#accesstable').show();$('#admin_accesstable').hide();"/> {$lang->no}</label>
            </td>
        </tr>
    </table>

    <!------------------------------------------------------------------------->

    <table width="660" border="0" cellspacing="5" class="proptable" id="admin_accesstable"{if empty($item.is_admin)} style="display:none;"{/if}>
        <tr>
            <td width="191" valign="top">
                <div><strong>{$lang->ad_available_sections} </strong></div>
                <span class="hinttext">{$lang->ad_all_sections}</span>
            </td>
            <td width="475" valign="top">
                <table width="100%" border="0" cellspacing="2" cellpadding="0">
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_menu" value="admin/menu"{if in_array('admin/menu', $item.access)} checked="checked"{/if} />
                        </td>
                        <td><label for="admin_menu">{$lang->ad_menu_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_modules" value="admin/modules"{if in_array('admin/modules', $item.access)} checked="checked"{/if} />
                        </td>
                        <td><label for="admin_modules">{$lang->ad_modules_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_content" value="admin/content"{if in_array('admin/content', $item.access)} checked="checked"{/if} />
                        </td>
                        <td><label for="admin_content">{$lang->ad_contents_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_plugins" value="admin/plugins"{if in_array('admin/plugins', $item.access)} checked="checked"{/if} />
                        </td>
                        <td><label for="admin_plugins">{$lang->ad_plugins_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_filters" value="admin/filters"{if in_array('admin/filters', $item.access)} checked="checked"{/if} />
                        </td>
                        <td><label for="admin_filters">{$lang->ad_filters_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_components" value="admin/components"{if in_array('admin/components', $item.access)} checked="checked"{/if} />
                        </td>
                        <td><label for="admin_components">{$lang->ad_components_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_users" value="admin/users"{if in_array('admin/users', $item.access)} checked="checked"{/if} />
                        </td>
                        <td><label for="admin_users">{$lang->ad_users_control}</label></td>
                    </tr>
                    <tr>
                        <td width="16">
                            <input type="checkbox" name="access[]" id="admin_config" value="admin/config"{if in_array('admin/config', $item.access)} checked="checked"{/if} />
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
                    {foreach from=$components item=component}
                        {assign var=acces_name value="admin/com_{$com.link}"}
                        <tr>
                            <td width="16">
                                <input type="checkbox" name="access[]" id="admin_com_{$component.link}" value="{$acces_name}" {if in_array($acces_name, $item.access)} checked="checked"{/if} />
                            </td>
                            <td><label for="admin_com_{$component.link}">{$component.title}</label></td>
                        </tr>
                    {/foreach}
                </table>
            </td>
        </tr>
    </table>

    <!------------------------------------------------------------------------->

    <table width="660" border="0" cellspacing="5" class="proptable" id="accesstable"{if !empty($item.is_admin)} style="display:none;"{/if}>
        <tr>
            <td width="191" valign="top"><strong>{$lang->ad_group_rule} </strong></td>
            <td width="475" valign="top">
                <table width="100%" border="0" cellspacing="2" cellpadding="0">
                    {foreach from=$group_access item=ga}
                        {if $ga.alias != 'guest' && !$ga.hide_for_guest}
                            <tr>
                                <td width="16">
                                    <input type="checkbox" name="access[]" id="{$ga.access_type|replace:'/':'_'}" value="{$ga.access_type}"{if in_array($ga.access_type, $item.access)} checked="checked"{/if} />
                                </td>
                                <td><label for="{$ga.access_type|replace:'/':'_'}">{$ga.access_name}</label></td>
                            </tr>
                        {/if}
                    {/foreach}
                </table>
            </td>
        </tr>
    </table>

    <!------------------------------------------------------------------------->

    <p>
        <input name="add_mod" type="submit" id="add_mod" value="{if $do == 'add'}{$lang->ad_create_group}{else}{$lang->save}{/if}" />

        <span style="margin-top:15px"><input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.history.back();"/></span>
        
        {if $do == 'edit'}
            <input name="item_id" type="hidden" value="{$item.id}" />
        {/if}
    </p>
</form>