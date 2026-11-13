{if $has_filters}
    <div class="filter">
        <form name="filterform" action="{$uri}" method="POST">
            <table width="250">
                <tr>
                    {foreach $fields as $key => $field}
                        {if $field.filter}
                            {$filter_name = $field.filter_name|default:$field.field}
                            <td width="">{$field['title']}: </td>
                            {$initval = $filter.$filter_name|default:''}
                            <td width="">
                            {if !$field.filterlist}
                                <input name="filter[{$filter_name|escape:'html'}]" type="text" size="{$filter_name|escape:'html'}" class="filter_input" value="{$initval}"/>
                            {else}
                                <select name="filter[{$filter_name|escape:'html'}]" id="filter[{$filter_name|escape:'html'}]">
                                    <option value="-100">-- {$lang->ad_all} --</option>
                                    {foreach $field.filterlist as $k => $v}
                                    <option value="{$v.id|escape:'html'}" {if $initval == $v['id']}selected{/if}>{$v.title}</option>
                                    {/foreach}
                                </select>
                            {/if}
                            </td>
                        {/if}
                    {/foreach}
                    <td width="80"><input type="submit" class="filter_submit" value="{$lang->ad_filter}" /></td>
                    {if $fcount > 0}
                        <td width="80"><input type="button" onclick="window.location.href='{$query_uri}&nofilter'" class="filter_submit" value="{$lang->ad_all|escape:'html'}" /></td>
                    {/if}
                </tr>
            </table>
        </form>
    </div>
{/if}

{if $items}
    <form name="selform" action="{$uri}?do=saveorder" method="post">
        <table id="listTable" border="0" class="tablesorter" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th width="20" class="lt_header" align="center"><a class="lt_header_link" href="javascript:invert();" title="{$lang->ad_invert_selection}">#</a></th>
                {foreach $fields as $key => $field}
                    <th width="{$field.width}" class="lt_header">
                        {$field.title}
                    </th>
                {/foreach}
                {if $actions}
                    <th width="80" class="lt_header" align="center">{$lang->ad_actions}</th>
                {/if}
                </tr>
            </thead>
            <tbody>

            {foreach $items as $item}
                {assign var="row_class" value=($item@iteration % 2) ? 'lt_row1' : 'lt_row2'}
                <tr id="lt_row2">
                    <td class="{$row_class}" align="center" valign="middle">
                        <input type="checkbox" name="item[]" value="{$item.id}" />
                    </td>
                {foreach $fields as $key => $field}
                    {if $field.link}
                        <td class="{$row_class}" valign="middle">{$item._f_otstup_.$key}<a class="lt_link" href="{$item._f_links_.$key}">{$item[$field.field]}</a></td>
                    {else}
                        {if $field.field != 'ordering'}
                            {if $field.field == 'published'}
                                <td class="{$row_class}" valign="middle"><a title="{$lang->hide}" class="uittip" id="publink{$item.id}" href="javascript:pub({$item.id},'{$item._f_links_.qs}', '{$item._f_links_.qs2}', {if $item.published}'off', 'on'{else}'on', 'off'{/if});"><img id="pub{$item.id}" src="/admin/images/actions/{if $item.published}on{else}off{/if}.gif" border="0"/></a></td>
                            {else}
                                <td class="{$row_class}" valign="middle">{$item._f_otstup_.$key} {$item[$field.field]}</td>
                            {/if}
                        {else}
                            {if $field.do_suffix}
                                {$dos = $field.do_suffix}
                                {$ids = 'item_id'}
                            {else}
                                {$dos = ''}
                                {$ids = 'id'}
                            {/if}
                            <td class="{$row_class}" valign="middle">
                                <a title="{$lang->ad_down}" href="{$uri}?{if $field.do}{$field.do}{else}do{/if}{$dos}=move_down&co={$item[$field.field]}&{$ids}={$item.id}"><img src="/admin/images/actions/down.gif" border="0"/></a>
                            {if $table != 'menu' && $table != 'category'}
                                <input class="lt_input" type="text" size="4" name="ordering[]" value="{$item.ordering}" />
                                <input name="ids[]" type="hidden" value="{$item.id}" />
                            {else}
                                <input class="lt_input" type="text" size="4" name="ordering[]" value="{$item.ordering}" disabled/>
                            {/if}
                                <a title="{$this->ad_up}" href="{$uri}?{if $field.do}{$field.do}{else}do{/if}{$dos}=move_up&co={$item[$field.field]}&{$ids}={$item.id}"><img src="/admin/images/actions/top.gif" border="0"/></a>
                            </td>
                        {/if}
                    {/if}
                {/foreach}
                {if $actions}
                    <td width="110" class="' . $row_class . '" align="right" valign="middle">
                        <div style="padding-right:8px">
                        {foreach $actions as $key => $action}
                            {if $item._a_print_.$key}
                                {if !isset($action['confirm'])}
                                    <a href="{$item._a_links_.$key}" class="uittip" title="{$action.title}"><img hspace="2" src="/admin/images/actions/{$action.icon}" border="0" alt="{$action.title}"/></a>
                                {else}
                                    <a href="#" class="uittip" onclick="jsmsg('{$action.confirm}', '{$item._a_links_.$key}')" title="{$action.title}"><img hspace="2" src="/admin/images/actions/{$action.icon}" border="0" alt="{$action.title}"/></a>
                                {/if}
                            {/if}
                        {/foreach}
                        </div>
                    </td>
                {/if}
                </tr>
            {/foreach}
            </tbody>
        </table>
    </form>

    <script type="text/javascript">highlightTableRows("listTable","hoverRow","clickedRow");</script>
    <script type="text/javascript">activateListTable("listTable");</script>

    {$pagebar}
{else}
    <p class="cp_message">{$lang->objects_not_found}</p>
{/if}