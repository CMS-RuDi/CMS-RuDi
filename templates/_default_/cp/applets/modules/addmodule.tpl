<form id="addform" name="addform" method="post" action="/cp/modules/submit">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />

    <table class="proptable" width="100%" cellpadding="15" cellspacing="2">
        <tr>
            <!-- главная ячейка -->
            <td valign="top">

                <div><strong>{$lang->ad_module_title}</strong> <span class="hinttext">&mdash; {$lang->ad_view_in_site}</span></div>
                <div>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td>
                                <input name="title" type="text" id="title" style="width:100%" value="{if !empty($mod.title)}{$mod.title|escape:'html'}{/if}" />
                            </td>
                            <td style="width:15px;padding-left:10px;padding-right:0px;">
                                <input type="checkbox" title="{$lang->ad_view_title}" name="showtitle" {if !empty($mod.showtitle) || $do == 'add'}checked="checked"{/if} value="1" />
                            </td>
                        </tr>
                    </table>
                </div>
                {if $langs_count > 1 }
                    <div><strong>{$lang->ad_lang_titles}</strong> <span class="hinttext">&mdash; {$lang->ad_lang_titles_hint}</span></div>
                    {foreach from=$langs item=lang}
                        <div><strong>{$lang}:</strong> <input name="titles[{$lang}]" type="text" style="width:97%" value="{if !empty($mod.titles.$lang)}{$mod.titles.$lang|escape:'html'}{/if}" placeholder="{$lang->ad_hint_default}" /></div>
                    {/foreach}
                {/if}
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px;">
                    <tr>
                        <td valign="top">
                            <div>
                                <strong>{$lang->ad_module_name}</strong> <span class="hinttext">&mdash; {$lang->ad_show_admin}</span>
                            </div>
                            <div>
                                {if !isset($mod.user) || $mod.user == 1}
                                    <input name="name" type="text" id="name" style="width:99%" value="{if !empty($mod.name)}{$mod.name|escape:'html'}{/if}" />
                                {else}
                                    <input name="" type="text" id="name" style="width:99%" value="{if !empty($mod.name)}{$mod.name|escape:'html'}{/if}" disabled="disabled" />
                                    <input name="name" type="hidden" value="{if !empty($mod.name)}{$mod.name|escape:'html'}{/if}" />
                                {/if}
                            </div>
                        </td>
                        <td valign="top" width="160" style="padding-left:10px;">
                            <div>
                                <strong>{$lang->ad_prefix_css}</strong>
                            </div>
                            <div>
                                <input name="css_prefix" type="text" id="css_prefix" value="{if !empty($mod.css_prefix)}{$mod.css_prefix}{/if}" style="width:154px" />
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:8px">
                    <strong>{$lang->ad_defolt_view}</strong> <span class="hinttext">&mdash; {$lang->ad_position_must_be}</span>
                </div>
                <div>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:5px;">
                        <tr>
                            <td valign="top">
                                <select name="position" id="position" style="width:100%">
                                    {if $pos}
                                        {foreach from=$pos key=key item=position}
                                            <option value="{$position}"{if isset($mod.position) && $mod.position == $position} selected="selected"{/if}>{$position}</option>
                                        {/foreach}
                                    {/if}
                                </select>
                            </td>
                            {if !empty($positions_image)}
                                <td valign="top" width="160" style="padding-left:10px;">
                                    <script>
                                        $(function () {
                                            $('#pos').dialog({ modal: true, autoOpen: false, closeText: LANG_CLOSE, width: 'auto' });
                                        });
                                    </script>
                                    <a onclick="$('#pos').dialog('open');return false;" href="#" class="ajaxlink">{$lang->ad_see_visually}</a>
                                    <div id="pos" title="{$lang->ad_tpl_pos}"><img src="{$positions_image}" alt="{$lang->ad_tpl_pos}" /></div>
                                </td>
                            {/if}
                        </tr>
                    </table>
                </div>

                <div style="margin-top:15px">
                    <strong>{$lang->ad_module_template}</strong> <span class="hinttext">&mdash; {$lang->ad_folder_modules}</span>
                </div>
                <div>
                    <select name="template" id="template" style="width:100%">
                        {foreach from=$tpls item=tpl}
                            <option value="{$tpl}"{if (empty($mod.template) && $tpl == 'module.tpl') || ($mod.template == $tpl)} selected="selected"{/if}>{$tpl}</option>
                        {/foreach}
                    </select>
                </div>

                {if $do == 'add'}
                    <div style="margin-top:15px">
                        <strong>{$lang->ad_module_type}</strong>
                    </div>
                    <div>
                        <select name="operate" id="operate" onchange="checkDiv();" style="width:100%">
                            <option value="user" selected="selected">{$lang->ad_module_type_new}</option>
                            <option value="clone">{$lang->ad_module_type_copy}</option>
                        </select>
                    </div>
                {/if}

                {if ($do == 'add' || !isset($mod.user)) || $mod.user == 1}
                    <div id="user_div">
                        <div style="margin-top:15px">
                            <strong>{$lang->ad_module_content}</strong>
                        </div>
                        <div>
                            {$panel}
                        </div>
                        <div>
                            {wysiwyg name='content' value=$mod.content height=250 width='100%'}
                        </div>
                    </div>
                {/if}

                <div id="clone_div" style="display:none;">
                    <div style="margin-top:15px">
                        <strong>{$lang->ad_module_copy}</strong>
                    </div>
                    <div>
                        <select name="clone_id" id="clone_id" style="width:100%">
                            {$modules_list}
                        </select>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist" style="margin-top:6px">
                            <tr>
                                <td width="20"><input type="checkbox" name="del_orig" id="del_orig" value="1" /></td>
                                <td><label for="del_orig">{$lang->ad_original_module_delete}</label></td>
                            </tr>
                        </table>
                    </div>
                </div>

            </td>

            <!-- боковая ячейка -->
            <td width="300" valign="top" style="background:#ECECEC;">
                {literal}{tab={/literal}{$lang->AD_TAB_PUBLISH}{literal}}{/literal}

                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                    <tr>
                        <td width="20"><input type="checkbox" name="published" id="published" value="1"{if !empty($mod.published) || $do == 'add'} checked="checked"{/if}/></td>
                        <td><label for="published"><strong>{$lang->ad_module_public}</strong></label></td>
                    </tr>
                    <tr>
                        <td width="20"><input name="show_all" id="show_all" type="checkbox" value="1" onclick="checkGroupList();"{if $show_all} checked="checked"{/if}/></td>
                        <td><label for="show_all"><strong>{$lang->ad_view_all_pages}</strong></label></td>
                    </tr>
                </table>

                <div id="grp">
                    <div style="margin-top:13px">
                        <strong class="show_list">{$lang->ad_where_module_view}</strong>
                        <strong class="hide_list">{$lang->ad_where_module_not_view}</strong>
                    </div>

                    <div style="height:300px;overflow: auto;border: solid 1px #999; padding:5px 10px; background: #FFF;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" align="center">
                            <tr>
                                <td colspan="2" height="25"><strong>{$lang->ad_menu}</strong></td>
                                <td class="show_list" align="center" width="50"><strong>{$lang->ad_position}</strong></td>
                            </tr>
                            {foreach from=$menu_items item=i}
                                <tr class="show_list">
                                    <td width="20" height="25">
                                        <input type="checkbox" name="showin[]" id="mid{$i.id}" value="{$i.id}" {if $i.selected}checked="checked"{/if} onclick="$('#p{$i.id}').toggle();"/>
                                    </td>
                                    <td style="padding-left:{math equation="z * 6 - 6" z=$i.NSLevel}px"><label for="mid{$i.id}">{$i.title}</label></td>
                                    <td align="center">
                                        <select id="p{$i.id}" name="showpos[{$i.id}]"{if !$i.selected} style="display:none;"{/if}>
                                            {foreach from=$pos item=position}
                                                <option value="{$position}"{if $i.position == $position} selected="selected"{/if}>{$position}</option>
                                            {/foreach}
                                        </select>
                                    </td>
                                </tr>
                            {/foreach}
                            
                            {foreach from=$menu_items item=i}
                                <tr class="hide_list">
                                    <td width="20" height="25">
                                        <input type="checkbox" name="hidden_menu_ids[]" id="hmid{$i.id}" value="{$i.id}"{if in_array($i.id, $mod.hidden_menu_ids)} checked="checked"{/if} />
                                    </td>
                                    <td style="padding-left:{math equation="z * 6 - 6" z=$i.NSLevel}px"><label for="hmid{$i.id}">{$i.title}</label></td>
                                </tr>
                            {/foreach}
                        </table>
                    </div>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist show_list">
                        <tr>
                            <td width="20"><input type="checkbox" name="is_strict_bind" id="is_strict_bind" value="1"{if !empty($mod.is_strict_bind)} checked="checked"{/if}/></td>
                            <td><label for="is_strict_bind"><strong>{$lang->ad_dont_view}</strong></label></td>
                        </tr>
                    </table>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist hide_list">
                        <tr>
                            <td width="20"><input type="checkbox" name="is_strict_bind_hidden" id="is_strict_bind_hidden" value="1"{if !empty($mod.is_strict_bind_hiddend)} checked="checked"{/if}/></td>
                            <td><label for="is_strict_bind_hidden"><strong>{$lang->ad_except_nested}</strong></label></td>
                        </tr>
                    </table>

                </div>

                {if $do == 'add' || (!empty($mod.is_external) && $do == 'edit')}
                    {literal}{tab={/literal}{$lang->ad_module_cache}{literal}}{/literal}

                    <div style="margin-top:4px">
                        <strong>{$lang->ad_do_module_cache}</strong>
                    </div>
                    <div>
                        <select name="cache" id="cache" style="width:100%">
                            <option value="0" {if empty($mod.cache)}selected="selected"{/if}>{$lang->no}</option>
                            <option value="1" {if !empty($mod.cache)}selected="selected"{/if}>{$lang->yes}</option>
                        </select>
                    </div>

                    <div style="margin-top:15px">
                        <strong>{$lang->ad_module_cache_period}</strong>
                    </div>
                    <div>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:5px;">
                            <tr>
                                <td valign="top"  width="100">
                                    <input name="cachetime" type="text" id="int_1" style="width:99%" value="{$mod.cachetime}"/>
                                </td>
                                <td valign="top" style="padding-left:5px">
                                    <select name="cacheint" id="int_2" style="width:100%">
                                        <option value="MINUTE"{if !empty($mod.cacheint) && $mod.cacheint == 'MINUTE'} selected="selected"{/if}>{$mod.cachetime|spellcount:$lang->minute1:$lang->minute2:$lang->minute10:0}</option>
                                        <option value="HOUR"{if !empty($mod.cacheint) && $mod.cacheint == 'HOUR'} selected="selected"{/if}>{$mod.cachetime|spellcount:$lang->hour1:$lang->hour2:$lang->hour10:0}</option>
                                        <option value="DAY"{if !empty($mod.cacheint) && $mod.cacheint == 'DAY'} selected="selected"{/if}>{$mod.cachetime|spellcount:$lang->day1:$lang->day2:$lang->day10:0}</option>
                                        <option value="MONTH"{if !empty($mod.cacheint) && $mod.cacheint == 'MONTH'} selected="selected"{/if}>{$mod.cachetime|spellcount:$lang->month1:$lang->month2:$lang->month10:0}</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div style="margin-top:15px">
                        {if $do == 'edit'}
                            {if !empty($cfkb)}
                                <a href="/cp/cache/delete/module/{$mod.id}">{$lang->ad_module_cache_delete}</a> ({$cfkb}{$lang->size_kb})
                            {else}
                                <span style="color:gray">{$lang->ad_no_cache}</span>
                            {/if}
                        {/if}
                    </div>
                {/if}

                {literal}{tab={/literal}{$lang->ad_tab_access}{literal}}{/literal}
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist" style="margin-top:5px">
                    <tr>
                        <td width="20">
                            {assign var="style" value='disabled="disabled"'}
                            {assign var="public" value='checked="checked"'}
                            
                            {if $do == 'edit' && !empty($mod.access_list)}
                                {assign var="style" value=''}
                                {assign var="public" value=''}
                            {/if}
                            <input name="is_public" type="checkbox" id="is_public" onclick="checkAccesList();" value="1" {$public} />
                        </td>
                        <td><label for="is_public"><strong>{$lang->ad_share}</strong></label></td>
                    </tr>
                </table>
                <div style="padding:5px">
                    <span class="hinttext">
                        {$lang->ad_if_checked}
                    </span>
                </div>

                <div style="margin-top:10px;padding:5px;padding-right:0px;">
                    <div>
                        <strong>{$lang->ad_groups_view}</strong><br />
                        <span class="hinttext">
                            {$lang->ad_select_multiple_ctrl}
                        </span>
                    </div>
                    <div>
                        <select style="width: 99%" name="allow_group[]" id="allow_group" size="6" multiple="multiple" {$style}>
                            {foreach from=$groups item=group}
                                <option value="{$group.id}" {if $do == 'edit' && !empty($mod.access_list) && in_array($group.id, $access_list)}selected="selected"{/if}>{$group.title}</option>';
                            {/foreach}
                        </select>
                    </div>
                </div>

                {literal}{/tabs}{/literal}
            </td>
        </tr>
    </table>
    <p>
        <input name="add_mod" type="submit" id="add_mod" value="{$lang->save}" />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.history.back();" />
        {if $do == 'edit'}
            <input name="item_id" type="hidden" value="{$mod.id}" />
        {/if}
    </p>
</form>