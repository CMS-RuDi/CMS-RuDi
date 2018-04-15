{foreach from=$icons item=icon}
    <a style="width:20px;height:20px;display:block; float:left; padding:2px" href="javascript:selectIcon('{$icon.name}')">
        <img alt="{$icon.name}" src="{$icon.src}" border="0" />
    </a>
{foreachelse}
    <p>{$lang->ad_empty_folder}</p>
{/foreach}

<div align="right" style="clear:both">
    [<a href="javascript:selectIcon('')">{$lang->ad_no_icon}</a>]
    [<a href="javascript:hideIcons()">{$lang->close}</a>]
</div>