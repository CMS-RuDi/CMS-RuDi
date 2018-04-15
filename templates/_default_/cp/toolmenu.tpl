<table width="100%" cellpadding="2" border="0" class="toolmenu" style="margin:0px">
    <tr>
        <td>
            {foreach from=$toolmenu item=tm}
                {if !$tm }
                    <div class="toolmenuseparator"></div>
                {else}
                    <a class="{if $uri == $tm.link}toolmenuitem_sel{/if} toolmenuitem uittip" href="{$tm.link}" title="{$tm.title|escape:'html'}" {if $tm.target}target="{$tm.target}"{/if}>
                        <img src="/admin/images/toolmenu/{$tm.icon}" border="0" />
                    </a>
                {/if}
            {/foreach}
        </td>
    </tr>
</table>