{if $actions}
    {include file='components/com_actions_friends.tpl'}

    {include file='components/com_actions_tab.tpl'}
{else}
    <p>{$LANG.FEED_DESC}</p>
    <p>{$LANG.FEED_EMPTY_TEXT}</p>
{/if}