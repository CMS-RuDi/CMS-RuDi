{if empty($new_modules) && empty($upd_modules)}
    <p>{$lang->ad_no_search_modules}</p>
    
    <p>{$lang->ad_if_want_setup_modules}</p>
{else}
    {if !empty($new_modules)}
        <p><strong>{$lang->ad_search_modules}</strong></p>
        
        <table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">
            {foreach from=$new_modules item='module'}
                <tr>
                    <td width="16"><img src="/admin/images/icons/hmenu/plugins.png" /></td>
                    <td><a style="font-weight:bold;font-size:14px" title="{$lang->ad_setup} {$module.title|escape:'html'}" href="{$install_link}/{$module.link}">{$module.title}</a> v{$module.version}</td>
                </tr>
                <tr>
                    <td width="16">&nbsp;</td>
                    <td>
                        <div style="margin-bottom:6px;">{$module.description}</div>
                        <div style="color:gray"><strong>{$lang->ad_author}:</strong> {$module.author}</div>
                        <div style="color:gray"><strong>{$lang->ad_folder}:</strong> /modules/{$module.link}</div>
                    </td>
                </tr>
        {/foreach}
    {/if}
    
    {if !empty($upd_modules)}
        <p><strong>{$lang->ad_modules_update}</strong></p>
        
        <table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">
            {foreach from=$upd_modules item='module'}
                <tr>
                    <td width="16"><img src="/admin/images/icons/hmenu/plugins.png" /></td>
                    <td><a style="font-weight:bold;font-size:14px" title="{$lang->ad_update} {$module.title|escape:'html'}" href="{$update_link}/{$module.link}">{$module.title}</a> v{$module.version}</td>
                </tr>
                <tr>
                    <td width="16">&nbsp;</td>
                    <td>
                        <div style="margin-bottom:6px;">{$module.description}</div>
                        <div style="color:gray"><strong>{$lang->ad_author}:</strong> {$module.author}</div>
                        <div style="color:gray"><strong>{$lang->ad_folder}:</strong> /modules/{$module.link}</div>
                    </td>
                </tr>
        {/foreach}
    {/if}
    
    <p>{$lang->ad_click_to_continue_module}</p>
{/if}

<p><a href="javascript:window.history.go(-1);">{$lang->back}</a></p>