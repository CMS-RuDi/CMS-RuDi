<form id="addform" name="addform" method="post" action="{$submit_uri}">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />

    <table class="proptable" width="100%" cellpadding="5" cellspacing="2">
        <tr>
            <!-- главная ячейка -->
            <td valign="top">
                <table border="0" cellpadding="0" cellspacing="5" width="100%">
                    <tbody>
                        <tr>
                            <td>
                                <strong>{$lang->ad_title_partition}</strong> {$title_lang_panel}
                            </td>
                            <td width="190" style="padding-left:6px">
                                <strong>{$lang->ad_template_partition}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input name="title" type="text" id="title" style="width:100%" value="{$item.title|escape:'html'}" />
                            </td>
                            <td style="padding-left:6px">
                                <input name="tpl" type="text" style="width:98%" value="{$item.tpl}" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div><strong>{$lang->ad_parent_partition}</strong></div>
                <div>
                    <div class="parent_notice" style="color:red;margin:4px 0px;display:none">{$lang->ad_another_parent}</div>
                    
                    <select name="parent_id" size="12" id="parent_id" style="width:100%" onchange="if ($('option:selected', this).data('nsleft') >= '{$item.NSLeft}' && $('option:selected', this).data('nsright') <= '{$item.NSRight}') { $('.parent_notice').show(); $('#add_mod').prop('disabled', true); } else { $('.parent_notice').hide(); $('#add_mod').prop('disabled', false); }">
                        <option value="{$root_id}"{if !isset($item.parent_id) || $item.parent_id == $rootid} selected="selected"{/if}>{$lang->ad_section}</option>
                        {$cats_list}
                    </select>
                </div>

                <div><strong>{$lang->ad_section_descript}</strong> {$description_lang_panel}</div>
                <div>
                    {wysiwyg name='description' value=$item.description height=250 width='100%'}
                </div>

            </td>

            <!-- боковая -->
            <td valign="top" width="350" style="background:#ECECEC;">
                {literal}{tab={/literal}{$lang->ad_tab_publish}{literal}}{/literal}

                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                    <tr>
                        <td width="20"><input type="checkbox" name="published" id="published"{if $do == 'add' || $item.published} checked="checked"{/if} value="1" /></td>
                        <td><label for="published"><strong>{$lang->ad_public_section}</strong></label></td>
                    </tr>
                </table>

                <div {if $do == 'edit'}style="display:none;"{/if} class="url_cat">
                    <div style="margin-top:15px">
                        <strong>{$lang->ad_section_url}</strong><br/>
                        <div style="color:gray">{$lang->ad_from_title}</div>
                    </div>
                    <div>
                        <input type="text" name="url" value="{$item.url}" style="width:99%" />
                    </div>
                </div>

                {if $do == 'edit'}
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:15px">
                        <tr>
                            <td width="20">
                                <input type="checkbox" name="update_seolink" id="update_seolink" value="1" onclick="$('.url_cat').slideToggle('fast');" />
                            </td>
                            <td>
                                <label for="update_seolink"><strong>{$lang->ad_new_link}</strong></label>
                            </td>
                        </tr>
                    </table>
                    <div class="url_cat" style="display:none;">
                        <strong style="color:#F00;">{$lang->attention}:</strong> {$lang->ad_no_links}
                    </div>
                {/if}

                <div style="margin-top:20px"><strong>{$lang->ad_sort_articles}</strong></div>
                <div>
                    <select name="orderby" id="orderby" style="width:100%">
                        <option value="pubdate"{if $item.orderby == 'pubdate'} selected="selected"{/if}>{$lang->ad_by_calendar}</option>
                        <option value="title"{if $item.orderby == 'title'} selected="selected"{/if}>{$lang->ad_by_title}</option>
                        <option value="ordering"{if $item.orderby == 'ordering'} selected="selected"{/if}>{$lang->ad_by_order}</option>
                        <option value="hits"{if $item.orderby == 'hits'} selected="selected"{/if}>{$lang->ad_by_views}</option>
                    </select>
                    <select name="orderto" id="orderto" style="width:100%">
                        <option value="ASC"{if $item.orderto == 'ASC'} selected="selected"{/if}>{$lang->ad_by_increment}</option>
                        <option value="DESC"{if $item.orderto == 'DESC'} selected="selected"{/if}>{$lang->ad_by_decrement}</option>
                    </select>
                </div>

                <div style="margin-top:20px"><strong>{$lang->ad_how_many_columns}</strong></div>
                <div>
                    <input class="uispin" name="maxcols" type="text" id="maxcols" style="width:99%" value="{$item.maxcols}" />
                </div>

                <div style="margin-top:20px"><strong>{$lang->ad_how_publish_set}</strong></div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                    <tr>
                        <td width="20"><input type="checkbox" name="showdesc" id="showdesc"{if $do == 'add' || !empty($item.showdesc)} checked="checked"{/if} value="1" /></td>
                        <td><label for="showdesc">{$lang->ad_preview}</label></td>
                    </tr>
                    <tr>
                        <td width="20"><input type="checkbox" name="showdate" id="showdate"{if $do == 'add' || !empty($item.showdate)} checked="checked"{/if} value="1" /></td>
                        <td><label for="showdate">{$lang->ad_calendar_view}</label></td>
                    </tr>
                    <tr>
                        <td width="20"><input type="checkbox" name="showcomm" id="showcomm"{if $do == 'add' || !empty($item.showcomm)} checked="checked"{/if} value="1" /></td>
                        <td><label for="showcomm">{$lang->ad_how_many_coments}</label></td>
                    </tr>
                    <tr>
                        <td width="20"><input type="checkbox" name="showtags" id="showtags"{if $do == 'add' || !empty($item.showtags)} checked="checked"{/if} value="1" /></td>
                        <td><label for="showtags">{$lang->ad_how_many_tags}</label></td>
                    </tr>
                    <tr>
                        <td width="20"><input type="checkbox" name="showrss" id="showrss"{if $do == 'add' || !empty($item.showrss)} checked="checked"{/if} value="1" /></td>
                        <td><label for="showrss">{$lang->ad_rss_view}</label></td>
                    </tr>
                </table>

                {if $do == 'add'}
                    <div style="margin-top:25px">
                        <strong>{$lang->ad_create_link}</strong>
                    </div>
                    <div>
                        <select name="createmenu" id="createmenu" style="width:99%">
                            <option value="0" selected="selected">{$lang->ad_dont_create}</option>
                            {foreach from=$menu_list item=menu}
                                <option value="{$menu.id}">{$menu.title}</option>
                            {/foreach}
                        </select>
                    </div>
                {/if}

                {literal}{tab={/literal}{$lang->ad_editors}{literal}}{/literal}

                <div style="margin-top:10px">
                    <strong>{$lang->ad_users_articles}</strong><br/>
                    <span class="hinttext">{$lang->ad_if_switch}</span>
                </div>
                <div>
                    <select name="is_public" style="width:100%">
                        <option value="0"{if empty($item.is_public)} selected="selected"{/if}>{$lang->no}</option>
                        <option value="1"{if !empty($item.is_public)} selected="selected"{/if}>{$lang->yes}</option>
                    </select>
                </div>

                {if $billing_installed}
                    <div style="margin-top:15px">
                        <strong>{$lang->ad_cost_articles_add}</strong><br/>
                        <div style="color:gray">{$lang->ad_cost_articles_by_default}</div>
                    </div>
                    <div>
                        <input type="text" name="cost" value="{$item.cost}" style="width:50px" />{$lang->billing_point10}
                    </div>
                {/if}

                <div style="margin-top:20px">
                    <strong>{$lang->ad_editors_section}</strong><br/>
                    <span class="hinttext">{$lang->ad_users_can_admin}</span>
                </div>
                <div>
                    <select name="modgrp_id" id="modgrp_id" style="width:100%">
                        <option value="0" {if empty($item.modgrp_id)} selected="selected"{/if}>{$lang->ad_only_admins}</option>
                        {$groups_list}
                    </select>
                </div>

                {literal}{tab={/literal}{$lang->ad_foto}{literal}}{/literal}

                <div style="margin-top:10px">
                    <strong>{$lang->ad_photoalbum_connect}</strong><br/>
                    <span class="hinttext">{$lang->ad_photo_by_articles}</span>
                </div>
                <div>
                    <select name="album_id" id="album_id" style="width:100%" onchange="choosePhotoAlbum();">
                        <option value="0"{if empty($item.photoalbum.id)} selected="selected"{/if}>{$lang->ad_dont_connect}</option>
                        {$albums_list}
                    </select>
                </div>
                <div id="con_photoalbum"{if empty($item.photoalbum.id)} style="display:none;"{/if}>
                    <div style="margin-top:20px">
                        <strong>{$lang->ad_title}</strong><br/>
                        <span class="hinttext">{$lang->ad_over_photos}</span>
                    </div>
                    <div>
                        <input name="album_header" type="text" id="album_header" style="width:99%" value="{if !empty($item.photoalbum.header)}{$item.photoalbum.header}{/if}" />
                    </div>

                    <div style="margin-top:20px">
                        <strong>{$lang->ad_photos_sort}</strong>
                    </div>
                    <div>
                        <select name="album_orderby" id="album_orderby" style="width:100%">
                            <option value="title"{if empty($item.photoalbum.orderby) || $item.photoalbum.orderby == 'title'} selected="selected"{/if}>{$lang->ad_by_alphabet}</option>
                            <option value="pubdate"{if !empty($item.photoalbum.orderby) && $item.photoalbum.orderby == 'pubdate'} selected="selected"{/if}>{$lang->ad_by_calendar}</option>
                            <option value="rating"{if !empty($item.photoalbum.orderby) && $item.photoalbum.orderby == 'rating'} selected="selected"{/if}>{$lang->ad_by_rating}</option>
                            <option value="hits"{if !empty($item.photoalbum.orderby) && $item.photoalbum.orderby == 'hits'} selected="selected"{/if}>{$lang->ad_by_views}</option>
                        </select>
                        <select name="album_orderto" id="album_orderto" style="width:100%">
                            <option value="desc"{if empty($item.photoalbum.orderto) || $item.photoalbum.orderto == 'desc'} selected="selected"{/if}>{$lang->ad_by_decrement}</option>
                            <option value="asc"{if !empty($item.photoalbum.orderto) && $item.photoalbum.orderto == 'asc'} selected="selected"{/if}>{$lang->ad_by_increment}</option>
                        </select>
                    </div>

                    <div style="margin-top:20px">
                        <strong>{$lang->ad_how_many_columns}</strong>
                    </div>
                    <div>
                        <input name="album_maxcols" type="text" id="album_maxcols" style="width:99%" value="{if empty($item.photoalbum.maxcols)}2{else}{$item.photoalbum.maxcols}{/if}"/>
                    </div>

                    <div style="margin-top:20px">
                        <strong>{$lang->ad_how_many_photo}</strong>
                    </div>
                    <div>
                        <input name="album_max" type="text" id="album_max" style="width:99%" value="{if empty($item.photoalbum.max)}8{else}{$item.photoalbum.max}{/if}"/>
                    </div>
                </div>
                    
                {literal}{tab=SEO}{/literal}

                <div style="margin-top:5px">
                    <strong>{$lang->ad_page_title}</strong> {$pagetitle_lang_panel}<br/>
                    <span class="hinttext">{$lang->ad_if_unknown_pagetitlE}</span>
                </div>
                <div>
                    <input name="pagetitle" type="text" id="pagetitle" style="width:99%" value="{if !empty($item.pagetitle)}{$item.pagetitle|escape:'html'}{/if}" />
                </div>

                <div style="margin-top:20px">
                    <strong>{$lang->keywords}</strong> {$meta_keys_lang_panel}<br/>
                    <span class="hinttext">{$lang->ad_from_comma}</span>
                </div>
                <div>
                    <textarea name="meta_keys" style="width:97%" rows="4" id="meta_keys">{if !empty($item.meta_keys)}{$item.meta_keys|escape:'html'}{/if}</textarea>
                </div>

                <div style="margin-top:20px">
                    <strong>{$lang->description}</strong> {$meta_desc_lang_panel}<br/>
                    <span class="hinttext">{$lang->ad_less_than}</span>
                </div>
                <div>
                    <textarea name="meta_desc" style="width:97%" rows="6" id="meta_desc">{if !empty($item.meta_desc)}{$item.meta_desc|escape:'html'}{/if}</textarea>
                </div>
                
                {literal}{tab={/literal}{$lang->AD_TAB_ACCESS}{literal}}{/literal}

                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist" style="margin-top:5px">
                    <tr>
                        <td width="20">
                            {if $do == 'edit' && !empty($access)}
                                {assign var=style value=''}
                                {assign var=public value=''}
                            {else}
                                {assign var=style value='disabled="disabled"'}
                                {assign var=public value='checked="checked"'}
                            {/if}

                            <input name="is_access" type="checkbox" id="is_public" onclick="checkGroupList();" value="1" {$public} />
                        </td>
                        <td><label for="is_public"><strong>{$lang->ad_share}</strong></label></td>
                    </tr>
                </table>
                <div style="padding:5px">
                    <span class="hinttext">
                        {$lang->ad_if_noted}
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
                        <select style="width: 99%" name="showfor[]" id="showin" size="6" multiple="multiple" {$style}>
                            {foreach from=$groups item=group}
                                <option value="{$group.id}"{if !empty($access) && isset($access[$group.id])} selected="selected"{/if}>{$group.title}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                {literal}{/tabs}{/literal}
            </td>

        </tr>
    </table>
    <p>
        <input name="add_mod" type="submit" id="add_mod" value="{if $do == 'add'}{$lang->ad_save_section}{else}{$lang->ad_save_section}{/if}" />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.history.back();"/>

        {if $do == 'edit'}<input name="item_id" type="hidden" value="{$item.id}" />{/if}
    </p>
</form>
<script type="text/javascript">
    function choosePhotoAlbum() {
        id = $('select[name=album_id]').val();

        if (id != 0) {
            $('#con_photoalbum').fadeIn();
        } else {
            $('#con_photoalbum').hide();
        }
    }
</script>