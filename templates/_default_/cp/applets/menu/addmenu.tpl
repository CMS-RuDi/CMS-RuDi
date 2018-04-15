<form id="addform" name="addform" method="post" action="/cp/menu/submit">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />

    <table class="proptable" width="100%" cellpadding="15" cellspacing="2">
        <tr>
            <td valign="top">
                <div>
                    <strong>{$lang->ad_menu_point_title}</strong>
                    <span class="hinttext">&mdash; {$lang->ad_view_in_site}</span>
                </div>
                
                <div>
                    <input name="title" type="text" id="title" style="width:100%" value="{$mod.title|escape:'html'}" />
                </div>
                
                {if $langs_count > 1 }
                    <div>
                        <strong>{$lang->ad_lang_titles}</strong>
                        <span class="hinttext">&mdash; {$lang->ad_lang_titles_hint}</span>
                    </div>
                    {foreach from=$langs item=lang}
                        <div><strong>{$lang}:</strong> <input name="titles[{$lang}]" type="text" style="width:97%" value="{if isset($mod.titles.$lang)}{$mod.titles.$lang|escape:'html'}{/if}" placeholder="{$lang->ad_hint_default}" /></div>
                    {/foreach}
                {/if}
                
                <div>
                    <strong>{$lang->ad_parent_point}</strong>
                </div>
                
                <div>
                    <select name="parent_id" size="10" id="parent_id" style="width:100%">
                        <option value="{$rootid}" {if (isset($mod.parent_id) && $mod.parent_id == $rootid) || !isset($mod.parent_id)}selected="selected"{/if}>{$lang->ad_menu_root}</option>
                        {$parents_list}
                    </select>
                    
                    <input type="hidden" name="oldparent" value="{if isset($mod.parent_id)}{$mod.parent_id}{/if}" />
                </div>

                <div>
                    <strong>{$lang->ad_menu_point_action}</strong>
                </div>
                
                <div>
                    <select name="mode" id="linktype" style="width:100%" onchange="showMenuTarget();">
                        <option value="link" {if (isset($mod.linktype) && $mod.linktype == 'link') || !isset($mod.mode)}selected="selected"{/if}>{$lang->ad_open_link}</option>
                        <option value="content" {if isset($mod.linktype) && $mod.linktype == 'content'}selected="selected"{/if}>{$lang->ad_open_article}</option>
                        <option value="category" {if isset($mod.linktype) && $mod.linktype == 'category'}selected="selected"{/if}>{$lang->ad_open_partition}</option>
                        {if  $com_video_installed}
                            <option value="video_cat" {if isset($mod.linktype) && $mod.linktype == 'video_cat'}selected="selected"{/if}>{$lang->ad_open_video_partition}</option>
                        {/if}
                        <option value="component" {if isset($mod.linktype) && $mod.linktype == 'component'}selected="selected"{/if}>{$lang->ad_open_component}</option>
                        <option value="blog" {if isset($mod.linktype) && $mod.linktype == 'blog'}selected="selected"{/if}>{$lang->ad_open_blog}</option>
                        <option value="uccat" {if isset($mod.linktype) && $mod.linktype == 'uccat'}selected="selected"{/if}>{$lang->ad_open_category}</option>
                        <option value="photoalbum" {if isset($mod.linktype) && $mod.linktype == 'photoalbum'}selected="selected"{/if}>{$lang->ad_open_album}</option>
                    </select>
                </div>

                <div id="t_link" class="menu_target" style="display:{if (isset($mod.linktype) && ($mod.linktype == 'link' || $mod.linktype == 'ext')) || !$mod.linktype}block{else}none{/if}">
                    <div>
                        <strong>{$lang->ad_link}</strong> <span class="hinttext">&mdash; {$lang->ad_link_hint} <b>http://</b></span>
                    </div>
                    <div>
                        <input name="link" type="text" id="link" size="50" style="width:100%" value="{if isset($mod.linktype) && ($mod.linktype == 'link' || $mod.linktype == 'ext')}{$mod.link}{/if}" />
                    </div>
                </div>

                <div id="t_content" class="menu_target" style="display:{if isset($mod.linktype) && $mod.linktype == 'content'}block{else}none{/if}">
                    <div>
                        <strong>{$lang->ad_check_article}</strong>
                    </div>
                    <div>
                        <select name="content" id="content" style="width:100%;">
                            {$content_list}
                        </select>
                    </div>
                </div>

                {if $com_video_installed}
                    <div id="t_video_cat" class="menu_target" style="display:{if isset($mod.linktype) && $mod.linktype == 'video_cat'}block{else}none{/if}">
                        <div>
                            <strong>{$lang->ad_check_partition}</strong>
                        </div>
                        <div>
                            <select name="video_cat" id="video_cat" style="width:100%">
                                {$video_cat_list}
                            </select>
                        </div>
                    </div>
                {/if}

                <div id="t_category" class="menu_target" style="display:{if isset($mod.linktype) && $mod.linktype == 'category'}block{else}none{/if}">
                    <div>
                        <strong>{$lang->ad_check_partition}</strong>
                    </div>
                    <div>
                        <select name="category" id="category" style="width:100%">
                            {$content_cat_list}
                        </select>
                    </div>
                </div>

                <div id="t_component" class="menu_target" style="display:{if isset($mod.linktype) && $mod.linktype == 'component'}block{else}none{/if}">
                    <div>
                        <strong>{$lang->ad_check_component}</strong>
                    </div>
                    <div>
                        <select name="component" id="component" style="width:100%">
                            {$components_list}
                        </select>
                    </div>
                </div>

                <div id="t_blog" class="menu_target" style="display:{if isset($mod.linktype) && $mod.linktype == 'blog'}block{else}none{/if}">
                    <div>
                        <strong>{$lang->ad_check_blog}</strong>
                    </div>
                    <div>
                        <select name="blog" id="blog" style="width:100%">
                            {$blogs_list}
                        </select>
                    </div>
                </div>

                <div id="t_uccat" class="menu_target" style="display:{if isset($mod.linktype) && $mod.linktype == 'uccat'}block{else}none{/if}">
                    <div>
                        <strong>{$lang->ad_check_category}</strong>
                    </div>
                    <div>
                        <select name="uccat" id="uccat" style="width:100%">
                            {$uccat_list}
                        </select>
                    </div>
                </div>

                <div id="t_photoalbum" class="menu_target" style="display:{if isset($mod.linktype) && $mod.linktype == 'photoalbum'}block{else}none{/if}">
                    <div>
                        <strong>{$lang->ad_check_album}</strong>
                    </div>
                    <div>
                        <select name="photoalbum" id="photoalbum" style="width:100%">
                            {$photoalbums_list}
                        </select>
                    </div>
                </div>

            </td>

            <td width="300" valign="top" style="background:#ECECEC;">
                {literal}{tab={/literal}{$lang->ad_tab_publish}{literal}}{/literal}

                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                    <tr>
                        <td width="20"><input type="checkbox" name="published" id="published" value="1" {if !empty($mod.published) || $do == 'add'}checked="checked"{/if} /></td>
                        <td><label for="published"><strong>{$lang->ad_menu_point_public}</strong></label></td>
                    </tr>
                </table>

                <div style="margin-top:15px">
                    <strong>{$lang->ad_open_point}</strong>
                </div>
                <div>
                    <select name="target" id="target" style="width:100%">
                        <option value="_self" {if isset($mod.target) && $mod.target == '_self'}selected="selected"{/if}>{$lang->ad_self}</option>
                        <option value="_parent" {if isset($mod.target) && $mod.target == '_parent'}selected="selected"{/if}>{$lang->ad_parent}</option>
                        <option value="_blank" {if isset($mod.target) && $mod.target == '_blank'}selected="selected"{/if}>{$lang->ad_blank}</option>
                        <option value="_top" {if isset($mod.target) && $mod.target == '_top'}selected="selected"{/if}>{$lang->ad_top}</option>
                    </select>
                </div>

                <div style="margin-top:15px">
                    <strong>{$lang->template}</strong><br/>
                    <span class="hinttext">{$lang->ad_design_change}</span>
                </div>
                <div>
                    <select name="template" id="template" style="width:100%">
                        <option value="0" {if empty($mod.template)}selected="selected"{/if}>{$lang->ad_by_default}</option>
                        {foreach from=$templates item=template}
                            <option value="{$template}" {if !empty($mod.template) && $mod.template == $template}selected="selected"{/if}>{$template}</option>
                        {/foreach}
                    </select>
                </div>

                <div style="margin-top:15px">
                    <strong>{$lang->ad_icon_picture}</strong><br/>
                    <span class="hinttext">{$lang->ad_icon_filename}</span>
                </div>
                <div>
                    <input name="iconurl" type="text" id="iconurl" size="30" value="{if !empty($mod.iconurl)}{$mod.iconurl}{/if}" style="width:100%" />
                    <div>
                        <a id="iconlink" style="display:block;" href="javascript:showIcons();">{$lang->ad_check_icon}</a>
                        <div id="icondiv" style="display:none; padding:6px;border:solid 1px gray;background:#FFF">
                            <div>{$icon_list}</div>
                        </div>
                    </div>
                </div>
                <div style="margin-top:15px">
                    <strong>{$lang->ad_css_class}</strong>
                </div>
                <div>
                    <input name="css_class" type="text" size="30" value="{if !empty($mod.css_class)}{$mod.css_class}{/if}" style="width:100%" />
                </div>

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

                            <input name="is_public" type="checkbox" id="is_public" onclick="checkAccesList()" value="1" {$public} />
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
                        <select style="width: 99%" name="allow_group[]" id="allow_group" size="6" multiple="multiple" {$style}>
                            {foreach from=$groups item=group}
                                <option value="{$group.id}" {if $do == 'edit' && !empty($mod.access_list) && in_array($group.id, $access_list)}selected="selected"{/if}>{$group.title}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist" style="margin-top:5px">
                    <tr>
                        <td width="20">
                            <input name="is_lax" type="checkbox" id="is_lax" value="1" {if !empty($mod.is_lax)}checked="checked"{/if} />
                        </td>
                        <td><label for="is_lax"><strong>{$lang->ad_only_child_item}</strong></label></td>
                    </tr>
                </table>
                {literal}{tab={/literal}{$lang->ad_menu}{literal}}{/literal}
                <div style="padding:5px;padding-right:0px;">
                    <div>
                        <strong>{$lang->ad_menu_to_view}</strong><br />
                        <span class="hinttext">
                            {$lang->ad_select_multiple_ctrl}
                        </span>
                    </div>
                    <div>
                        <select style="width: 99%" name="menu[]" size="9" multiple="multiple">
                            {foreach from=$menu_list item=menu}
                                <option value="{$menu.id}" {if in_array($menu.id, $mod.menu)}selected="selected"{/if}>{$menu.title}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                {literal}{/tabs}{/literal}
            </td>
        </tr>
    </table>

    <p>
        <input name="add_mod" type="button" onclick="submitItem();" id="add_mod" value="{$lang->save} " />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.location.href = '/cp/menu';" />
        {if $do == 'edit'}
            <input name="item_id" type="hidden" value="{$mod.id}" />
        {/if}
    </p>
</form>