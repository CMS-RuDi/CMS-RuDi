<ul id="{$menu}" class="menu">
    {if $cfg.show_home}
        <li {if $menuid==1}class="selected"{/if}>
            <a href="/" {if $menuid==1}class="selected"{/if}><span><i class="fa fa-home"></i>{$LANG.PATH_HOME}</span></a>
        </li>
    {/if}
    
    {foreach from=$root_items item=$item}
        <li class="{$item.css_class} {if ($menuid==$item.id || $current_uri == $item.link) || ($currentmenu.NSLeft > $item.NSLeft && $currentmenu.NSRight < $item.NSRight)}selected{/if}">
            <a href="{$item.link}" target="{$item.target}" {if ($menuid==$item.id || $current_uri == $item.link)}class="selected"{/if} title="{$item.title|escape:'html'}">
                <span>
                    {if $item.icon_as_img}<img src="/images/menuicons/{$item.iconurl}" alt="{$item.title|escape:'html'}" />{elseif $item.icon_as_css}<i class="{$item.iconurl}"></i>{/if}{$item.title}
                </span>
            </a>

            {if $child_items[$item.id]}
                {include file='modules/mod_submenu.tpl' mitems=$child_items[$item.id]}
            {/if}
        </li>
    {/foreach}
</ul>