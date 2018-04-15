{if empty($new_plugins) && empty($upd_plugins)}
    <p>{$lang->ad_no_search_plugins}</p>
    
    <p>{$lang->ad_if_want_setup_plugins}</p>
{else}
    {if !empty($new_plugins)}
        <p><strong>{$lang->ad_plugins_setup}</strong></p>
        
        <table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">
            {foreach from=$new_plugins item='plugin'}
                <tr>
                    <td width="16"><img src="/admin/images/icons/hmenu/plugins.png" /></td>
                    <td><a style="font-weight:bold;font-size:14px" title="{$lang->ad_setup} {$plugin.title|escape:'html'}" href="{$install_link}/{$plugin.plugin}">{$plugin.title}</a> v{$plugin.version}</td>
                </tr>
                <tr>
                    <td width="16">&nbsp;</td>
                    <td>
                        <div style="margin-bottom:6px;">{$plugin.description}</div>
                        <div style="color:gray"><strong>{$lang->ad_author}:</strong> {$plugin.author}</div>
                        <div style="color:gray"><strong>{$lang->ad_folder}:</strong> /plugins/{$plugin.plugin}</div>
                  </td>
                </tr>
            {/foreach}
        </table>
    {/if}
    
    {if !empty($upd_plugins)}
        <p><strong>{$lang->ad_plugins_update}</strong></p>
        
        <table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">
            {foreach from=$upd_plugins item='plugin'}
                <tr>
                    <td width="16"><img src="/admin/images/icons/hmenu/plugins.png" /></td>
                    <td><a style="font-weight:bold;font-size:14px" title="{$lang->ad_update} {$plugin.title|escape:'html'}" href="{$update_link}/{$plugin.plugin}">{$plugin.title}</a> v{$plugin.version}</td>
                </tr>
                <tr>
                    <td width="16">&nbsp;</td>
                    <td>
                        <div style="margin-bottom:6px;">{$plugin.description}</div>
                        <div style="color:gray"><strong>{$lang->ad_author}:</strong> {$plugin.author}</div>
                        <div style="color:gray"><strong>{$lang->ad_folder}:</strong> /plugins/{$plugin.plugin}</div>
                  </td>
                </tr>
            {/foreach}
        </table>
    {/if}
    
    <p>{$lang->ad_click_to_continue_plugin}</p>
{/if}

<p><a href="javascript:window.history.go(-1);">{$lang->back}</a></p>
        