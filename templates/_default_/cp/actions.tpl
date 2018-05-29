{if  $actions }
    <div class="actions_list">
        {foreach from=$actions item=action }
            <div class="action_entry act_{$action.name}">
                <div class="action_date
                    {if $action.is_new} is_new{/if}">{$action.pubdate} {$lang->back}
                    <a href="#" class="action_delete uittip" title="{$lang->delete}" onclick="jsmsg('{$lang->ad_delete_action}', '/actions/delete/{$action.id}');return false;"></a>
                </div>
                <div class="action_title">
                    <a href="{$action.user_url}" class="action_user">{$action.user_nickname}</a>
                    {if $action.message}
                        {$action.message}{if $action.description}:{/if}
                    {else}
                        {if $action.description}
                            &rarr; {$action.description}
                        {/if}
                    {/if}
                </div>
                {if $action.message}
                    {if $action.description}
                        <div class="action_details">{$action.description}</div>
                    {/if}
                {/if}
            </div>
        {/foreach}
    </div>
    {if !empty($pagebar)} {$pagebar} {/if}
{else}
    {$lang->objects_not_found}
{/if}