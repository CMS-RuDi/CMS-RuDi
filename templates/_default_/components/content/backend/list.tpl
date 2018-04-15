<table cellpadding="0" cellspacing="0" border="0" width="100%" height="100%" style="margin-top:2px">
    <tr>
        <td valign="top" width="240" {if $hide_cats}style="display:none;"{/if} id="cats_cell">

            <div class="cat_add_link">
                <div>
                    <a href="{$add_cat_uri}" style="color:#09C">{$lang->ad_create_section}</a>
                </div>
            </div>
            <div class="cat_link">
                <div>
                    {if !$only_hidden}
                        <a href="{$hidden_uri}" style="font-weight:bold">{$lang->on_moderate}</a>
                    {else}
                        {assign var=current_cat value=$lang->on_moderate}
                        {$current_cat}
                    {/if}
                </div>
                <div>
                    {if $category_id || $only_hidden}
                        <a href="{$base_uri}" style="font-weight:bold">{$lang->ad_page_all}</a>
                    {else}
                        {assign var=current_cat value=$lang->ad_page_all}
                        {$current_cat}
                    {/if}
                </div>
            </div>
            <div class="cat_link">
                <div>
                    {if $category_id != $root_id}
                        <a href="{$base_uri}/{$root_id}" style="font-weight:bold">{$lang->ad_root_category}</a>
                    {else}
                        {assign var=current_cat value=$lang->ad_root_category}
                        {$current_cat}
                    {/if}
                </div>
            </div>
            {if !empty($cats)}
                {foreach from=$cats key=num item=cat}
                    <div style="padding-left:{math equation="x * 20" x=$cat.NSLevel}px" class="cat_link">
                        <div>
                            {if $category_id != $cat.id}
                                <a href="{$base_uri}/{$cat.id}" {if $cat.NSLevel == 1}style="font-weight:bold"{/if}>{$cat.title}</a>
                            {else}
                                {$cat.title}
                                {assign var=current_cat value=$cat.title}
                            {/if}
                        </div>
                    </div>
                {/foreach}
            {/if}
        </td>

        <td valign="top" id="slide_cell" {if $hide_cats}class="unslided"{/if} onclick="$('#cats_cell').toggle(); $(this).toggleClass('unslided'); $('#filter_form input[name=hide_cats]').val(1 - $('#cats_cell:visible').length);">&nbsp;</td>

        <td valign="top" style="padding-left:2px">
            <form action="{$base_uri}{if $category_id}/{$category_id}{/if}" method="GET" id="filter_form">
                <input type="hidden" name="hide_cats" value="{$hide_cats}" />
                
                <table class="toolmenu" cellpadding="5" border="0" width="100%" style="margin-bottom: 2px;">
                    <tr>
                        <td width="">
                            <span style="font-size:16px;color:#0099CC;font-weight:bold;">
                                {$current_cat} {if $category_id}[id={$category_id}]{/if}
                            </span>
                            <span style="padding-left: 15px;">
                                <a class="uittip" title="{$lang->add_article}" href="{$add_item_uri}">
                                    <img border="0" hspace="2" alt="{$lang->ad_add_article}" src="/admin/images/actions/add.gif"/>
                                </a>
                                {if $category_id > 1}
                                    <a class="uittip" title="{$lang->ad_edit_section}" href="{$edit_cat_uri}/{$category_id}">
                                        <img border="0" hspace="2" alt="{$lang->ad_edit_section}" src="/admin/images/actions/edit.gif"/>
                                    </a>
                                    <a class="uittip" title="{$lang->ad_category_delete}" onclick="deleteCat('{$current_cat}', {$category_id})" href="#">
                                        <img border="0" hspace="2" alt="{$lang->ad_category_delete}" src="/admin/images/actions/delete.gif"/>
                                    </a>
                                {/if}
                            </span>
                        </td>
                    </tr>
                </table>
                <table class="toolmenu" cellpadding="5" border="0" width="100%" style="margin-bottom: 2px;" id="filterpanel">
                    <tr>
                        <td width="130">
                            <select name="orderby" style="width:130px" onchange="$('#filter_form').submit()">
                                {if $category_id}
                                    <option value="ordering" {if $orderby == 'ordering'}selected="selected"{/if}>{$lang->ad_by_order}</option>
                                {/if}
                                <option value="title" {if $orderby == 'title'}selected="selected"{/if}>{$lang->ad_by_title}</option>
                                <option value="pubdate" {if $orderby == 'pubdate'}selected="selected"{/if}>{$lang->ad_by_calendar}</option>
                            </select>
                        </td>
                        <td width="150">
                            <select name="orderto" style="width:150px" onchange="$('#filter_form').submit();">
                                <option value="asc" {if $orderto == 'asc'}selected="selected"{/if}>{$lang->ad_by_increment}</option>
                                <option value="desc" {if $orderto == 'desc'}selected="selected"{/if}>{$lang->ad_by_decrement}</option>
                            </select>
                        </td>
                        <td width="60">{$lang->title}:</td>
                        <td width="">
                            <input type="text" name="title" value="{$title_part}" style="width:99%"/>
                        </td>
                        <td width="30">
                            <input type="submit" name="filter" value="&raquo;" style="width:30px"/>
                        </td>
                    </tr>
                </table>
            </form>

            <form name="selform" action="{$base_uri}" method="post">
                <table id="listTable" class="tablesorter" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:0px">
                    <thead>
                        <tr>
                            <th class="lt_header" align="center" width="20">
                                <a class="lt_header_link" title="{$lang->ad_invert_selection}" href="javascript:" onclick="javascript:invert()">#</a>
                            </th>
                            <th class="lt_header" width="25">id</th>
                            <th class="lt_header" width="" colspan="2">{$lang->title}</th>
                            <th class="lt_header" width="80">{$lang->date}</th>
                            <th class="lt_header" width="50">{$lang->ad_is_published}</th>
                            {if $category_id && count($items) > 1}
                                <th class="lt_header" width="50">{$lang->ad_order}</th>
                                <th class="lt_header" width="24">&darr;&uarr;</th>
                            {/if}
                            <th class="lt_header" align="center" width="90">{$lang->ad_actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $items}
                            {assign var=num value=0}
                            
                            {foreach from=$items item=item}
                                {$num=$num+1}
                                <tr id="{$item.id}" class="item_tr">
                                    <td><input type="checkbox" name="item[]" value="{$item.id}" /></td>
                                    <td>{$item.id}</td>
                                    <td width="16">
                                        <img src="/templates/{template}/images/icons/article.png" border="0"/>
                                    </td>
                                    <td><a href="{$edit_item_uri}/{$item.id}">{$item.title}</a></td>
                                    <td>{$item.fpubdate}</td>
                                    <td>
                                        {if $item.published}
                                            <a class="uittip" id="publink{$item.id}" href="javascript:pub({$item.id}, '{$hide_uri}/{$item.id}', '{$show_uri}/{$item.id}', 'off', 'on');" title="{$lang->hide}">
                                                <img id="pub{$item.id}" border="0" src="/admin/images/actions/on.gif"/>
                                            </a>
                                        {else}
                                            <a class="uittip" id="publink{$item.id}" href="javascript:pub({$item.id}, '{$show_uri}/{$item.id}', '{$hide_uri}/{$item.id}', 'on', 'off');" title="{$lang->show}">
                                                <img id="pub{$item.id}" border="0" src="/admin/images/actions/off.gif"/>
                                            </a>
                                        {/if}
                                    </td>
                                    {if $category_id && count($items) > 1}
                                        <td class="ordering">{$item.ordering}</td>
                                        <td>
                                            {if $num < count($items)}
                                                {assign var=display_move_down value='inline'}
                                            {else}
                                                {assign var=display_move_down value='none'}
                                            {/if}
                                            
                                            {if $num > 1}
                                                {assign var=display_move_up value='inline'}
                                            {else}
                                                {assign var=display_move_up value='none'}
                                            {/if}

                                            <a class="move_item_down" href="javascript:void(0)" onclick="moveItem({$item.id}, 1);" title="{$lang->ad_down}" style="float:left;display:{$display_move_down}"><img src="/admin/images/actions/down.gif" border="0"/></a>
                                            <a class="move_item_up" href="javascript:void(0)" onclick="moveItem({$item.id}, -1);" title="{$lang->ad_up}" style="float:right;display:{$display_move_up}"><img src="/admin/images/actions/top.gif" border="0"/></a>
                                        </td>
                                    {/if}
                                    <td align="right">
                                        <div style="padding-right: 8px;">
                                            <a class="uittip" title="{$lang->ad_view_online}" href="/{$item.seolink}.html">
                                                <img border="0" hspace="2" alt="{$lang->ad_view_online}" src="/admin/images/actions/search.gif"/>
                                            </a>
                                            <a class="uittip" title="{$lang->edit}" href="{$edit_item_uri}/{$item.id}">
                                                <img border="0" hspace="2" alt="{$lang->edit}" src="/admin/images/actions/edit.gif"/>
                                            </a>
                                            <a class="uittip" title="{$lang->ad_to_arhive}" href="{$arhive_on_uri}/{$item.id}">
                                                <img border="0" hspace="2" alt="{$lang->ad_to_arhive}" src="/admin/images/actions/arhive_on.gif">
                                            </a>
                                            <a class="uittip" title="{$lang->delete}" onclick="jsmsg('{$lang->delete} {$item.title}?', '{$delete_uri}/{$item.id}')" href="#">
                                                <img border="0" hspace="2" alt="{$lang->delete}" src="/admin/images/actions/delete.gif"/>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <td colspan="7" style="padding-left:5px"><div style="padding:15px;padding-left:0px">{$lang->ad_dont_find_articles}</div></td>
                        {/if}
                    </tbody>
                </table>
                {if $items}
                    <div style="margin-top:4px;padding-top:4px;">
                        <table cellpadding="5" border="0" height="40">
                            <tr>
                                <td width="">
                                    <strong style="color:#09C">{$lang->selected_items}:</strong>
                                </td>
                                <td width="" class="sel_pub">
                                    <input type="button" name="" value="{$lang->edit}" onclick="sendContentForm('edit');" />
                                    <input type="button" name="" value="{$lang->ad_move_to}" onclick="$('.sel_move').toggle();$('.sel_pub').toggle();" />
                                </td>
                                <td class="sel_move" style="display:none">{$lang->ad_move_to_category}</td>
                                <td class="sel_move" style="display:none">
                                    <select id="move_cat_id" style="width:250px">
                                        <option value="1">{$lang->ad_root_category}</option>
                                        {$cats_opt}
                                    </select>
                                </td>
                                <td class="sel_move" style="display:none">
                                    <input type="button" name="" value="{$lang->ad_okay}" onclick="sendContentForm('move_to_cat', $('select#move_cat_id').val());" />
                                    <input type="button" name="" value="{$lang->cancel}" onclick="$('td.sel_move').toggle();$('td.sel_pub').toggle();" /> {$lang->ad_change_url}
                                </td>
                                <td class="sel_pub">
                                    <input type="button" name="" value="{$lang->show}" onclick="sendContentForm('show');" />
                                    <input type="button" name="" value="{$lang->hide}" onclick="sendContentForm('hide');" />
                                </td>
                                <td class="sel_pub">
                                    <input type="button" name="" value="{$lang->delete}" onclick="sendContentForm('delete');" />
                                </td>
                            </tr>
                        </table>
                    </div>
                {/if}
                <script type="text/javascript">highlightTableRows("listTable", "hoverRow", "clickedRow");</script>
            </form>
                
            {if !empty($pagebar)}{$pagebar}{/if}
        </td>
    </tr>
</table>