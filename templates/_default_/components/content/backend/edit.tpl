<form id="addform" name="addform" method="post" action="{$submit_uri}" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />

    <table class="proptable" width="100%" cellpadding="5" cellspacing="2">
        <tr>
            <!-- главная ячейка -->
            <td valign="top">

                <table width="100%" cellpadding="0" cellspacing="4" border="0">
                    <tr>
                        <td valign="top">
                            <div><strong>{$lang->ad_article_name}</strong> {$title_lang_panel}</div>
                            <div>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td>
                                            <input name="title" type="text" id="title" style="width:100%" value="{$item.title}" />
                                        </td>
                                        <td style="width:15px;padding-left:10px;padding-right:10px;">
                                            <input type="checkbox" title="{$lang->ad_view_title}" name="showtitle"{if !empty($item.showtitle) || $do == 'add'} checked="checked"{/if} value="1" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                        
                        <td width="130" valign="top">
                            <div><strong>{$lang->ad_public_date}</strong></div>
                            <div>
                                <input name="pubdate" type="text" id="pubdate" style="width:100px" value="{$item.pubdate}" />
                                <input type="hidden" name="olddate" value="{$item.pubdate}" />
                            </div>
                        </td>
                        
                        <td width="16" valign="bottom" style="padding-bottom:10px">
                            <input type="checkbox" name="showdate" id="showdate" title="{$lang->ad_view_date_and_author}" value="1"{if !empty($item.showdate) || $do == 'add'} checked="checked"{/if}/>
                        </td>
                        
                        <td width="160" valign="top">
                            <div><strong>{$lang->ad_article_template}</strong></div>
                            <div><input name="tpl" type="text" style="width:160px" value="{$item.tpl}" /></div>
                        </td>
                    </tr>
                </table>

                <div><strong>{$lang->ad_article_notice}</strong> {$description_lang_panel}</div>
                <div>{wysiwyg name='description' value=$item.description height=200 width='100%'}</div>

                <div><strong>{$lang->ad_article_text}</strong> {$content_lang_panel}</div>
                {$panel}
                <div>{wysiwyg name='content' value=$item.content height=400 width='100%'}</div>

                <div><strong>{$lang->ad_article_tags}</strong></div>
                <div><input name="tags" type="text" id="tags" style="width:99%" value="{$tag_line}" /></div>

                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                    <tr>
                        <td width="20">
                            <input type="radio" name="autokeys" id="autokeys1"{if $do == 'add' && !empty($options.autokeys)} checked="checked"{/if} value="1" />
                        </td>
                        <td>
                            <label for="autokeys1"><strong>{$lang->ad_auto_gen_key}</strong></label>
                        </td>
                    </tr>
                    <tr>
                        <td width="20">
                            <input type="radio" name="autokeys" id="autokeys2" value="2" />
                        </td>
                        <td>
                            <label for="autokeys2"><strong>{$lang->ad_tags_as_key}</strong></label>
                        </td>
                    </tr>
                    <tr>
                        <td width="20">
                            <input type="radio" name="autokeys" id="autokeys3"{if $do == 'edit' || empty($options.autokeys)} checked="checked"{/if} value="3" />
                        </td>
                        <td>
                            <label for="autokeys3"><strong>{$lang->ad_manual_key}</strong></label>
                        </td>
                    </tr>
                    {if $options.af_on && $do == 'add'}
                        <tr>
                            <td width="20"><input type="checkbox" name="noforum" id="noforum" value="1" /></td>
                            <td><label for="noforum"><strong>{$lang->ad_no_create_theme}</strong></label></td>
                        </tr>
                    {/if}
                </table>

            </td>

            <!-- боковая ячейка -->
            <td width="300" valign="top" style="background:#ECECEC;">
                {literal}{tab={/literal}{$lang->ad_tab_publish}{literal}}{/literal}

                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                    <tr>
                        <td width="20"><input type="checkbox" name="published" id="published"{if $do == 'add' || !empty($item.published)} checked="checked"{/if} value="1" /></td>
                        <td><label for="published"><strong>{$lang->ad_public_article}</strong></label></td>
                    </tr>
                </table>

                <div style="margin-top:7px">
                    <select name="category_id" size="10" id="category_id" style="width:99%;height:200px">
                        <option value="1"{if !isset($item.category_id) || $item.category_id == 1} selected="selected"{/if}>{$lang->ad_root_category}</option>
                        {$cats_list}
                    </select>
                </div>

                <div style="margin-bottom:10px">
                    <select name="showpath" id="showpath" style="width:99%">
                        <option value="0" {if empty($item.showpath)}selected="selected"{/if}>{$lang->ad_pathway_name_only}</option>
                        <option value="1" {if !empty($item.showpath)}selected="selected"{/if}>{$lang->ad_pathway_full}</option>
                    </select>
                </div>

                <div style="margin-top:15px">
                    <strong>{$lang->ad_article_url}</strong><br/>
                    <div style="color:gray">{$lang->ad_if_unknown}</div>
                </div>
                <div>
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td><input type="text" name="url" value="{if !empty($item.url)}{$item.url}{/if}" style="width:100%"/></td>
                            <td width="40" align="center">.html</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-top:10px">
                    <strong>{$lang->ad_article_author}</strong>
                </div>
                <div>
                    <select name="user_id" id="user_id" style="width:99%">
                        {$users_list}
                    </select>
                </div>

                <div style="margin-top:12px"><strong>{$lang->ad_photo}</strong></div>
                <div style="margin-bottom:10px">
                    {if $do == 'edit'}
                        {if $photo_exist}
                            <div style="margin-top:3px;margin-bottom:3px;padding:10px;border:solid 1px gray;text-align:center">
                                <img src="/images/photos/small/article{$item.id}.jpg" border="0" />
                            </div>
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="16"><input type="checkbox" id="delete_image" name="delete_image" value="1" /></td>
                                    <td><label for="delete_image">{$lang->ad_photo_remove}</label></td>
                                </tr>
                            </table>
                        {/if}
                    {/if}
                    <input type="file" name="picture" style="width:100%" />
                </div>

                <div style="margin-top:25px"><strong>{$lang->ad_public_parametrs}</strong></div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                    <tr>
                        <td width="20">
                            <input type="checkbox" name="showlatest" id="showlatest"{if $do == 'add' || !empty($item.showlatest)} checked="checked"{/if} value="1" />
                        </td>
                        <td><label for="showlatest">{$lang->ad_view_new_articles}</label></td>
                    </tr>
                    <tr>
                        <td width="20">
                            <input type="checkbox" name="comments" id="comments"{if $do == 'add' || !empty($item.comments)} checked="checked"{/if} value="1" />
                        </td>
                        <td><label for="comments">{$lang->ad_enable_comments}</label></td>
                    </tr>
                    <tr>
                        <td width="20">
                            <input type="checkbox" name="canrate" id="canrate"{if !empty($item.canrate)} checked="checked"{/if} value="1" />
                        </td>
                        <td><label for="canrate">{$lang->ad_enable_rating}</label></td>
                    </tr>
                </table>
                
                {if $do == 'add'}
                    <div style="margin-top:25px">
                        <strong>{$lang->ad_create_link}</strong>
                    </div>
                    <div>
                        <select name="createmenu" id="createmenu" style="width:99%">
                            <option value="0" selected="selected">{$lang->ad_dont_create_link}</option>
                            {foreach from=$menu_list item=$menu}
                                <option value="{$menu.id}">{$menu.title}</option>
                            {/foreach}
                        </select>
                    </div>
                {/if}

                {literal}{tab={/literal}{$lang->ad_date}{literal}}{/literal}

                <div style="margin-top:5px">
                    <strong>{$lang->ad_article_time}</strong>
                </div>
                <div>
                    <select name="is_end" id="is_end" style="width:99%" onchange="if ($(this).val() == 1) { $('#final_time').show(); } else { $('#final_time').hide(); }">
                        <option value="0"{if empty($item.is_end)} selected="selected"{/if}>{$lang->ad_unlimited}</option>
                        <option value="1"{if !empty($item.is_end)} selected="selected"{/if}>{$lang->ad_to_final_time}</option>
                    </select>
                </div>

                <div id="final_time"{if empty($item.is_end)}style="display: none"{/if}>
                    <div style="margin-top:20px">
                        <strong>{$lang->ad_final_time}</strong><br/>
                        <span class="hinttext">{$lang->ad_calendar_format}</span>
                    </div>
                    <div><input name="enddate" type="text" style="width:80%" value="{$item.enddate}" id="enddate" /></div>
                </div>

                {literal}{tab=SEO}{/literal}

                <div style="margin-top:5px">
                    <strong>{$lang->ad_page_title}</strong> {$pagetitle_lang_panel}<br/>
                    <span class="hinttext">{$lang->ad_if_unknown_pagetitle}</span>
                </div>
                <div>
                    <input name="pagetitle" type="text" id="pagetitle" style="width:99%" value="{if isset($item.pagetitle)}{$item.pagetitle|escape:'html'}{/if}" />
                </div>

                <div style="margin-top:20px">
                    <strong>{$lang->keywords}</strong> {$meta_keys_lang_panel}<br/>
                    <span class="hinttext">{$lang->ad_from_comma}</span>
                </div>
                <div>
                    <textarea name="meta_keys" style="width:97%" rows="4" id="meta_keys">{$item.meta_keys|escape:'html'}</textarea>
                </div>

                <div style="margin-top:20px">
                    <strong>{$lang->description}</strong> {$meta_desc_lang_panel}<br/>
                    <span class="hinttext">{$lang->ad_less_than}</span>
                </div>
                <div>
                    <textarea name="meta_desc" style="width:97%" rows="6" id="meta_desc">{$item.meta_desc|escape:'html'}</textarea>
                </div>

                {literal}{tab={/literal}{$lang->ad_tab_access}{literal}}{/literal}

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
                            
                            <input name="is_public" type="checkbox" id="is_public" onclick="checkGroupList();" value="1" {$public} />
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
        <input name="add_mod" type="submit" id="add_mod" value="{if $do == 'add'}{$lang->ad_create_content}{else}{$lang->ad_save_content}{/if}" />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.history.back();"/>
        
        {if $do == 'edit'}
            <input name="item_id" type="hidden" value="{$item.id}" />
        {/if}
    </p>
</form>