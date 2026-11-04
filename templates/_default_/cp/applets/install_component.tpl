{if empty($new_components) && empty($upd_components)}
    <p>{$lang->ad_no_search_components}</p>
    <p>{$lang->ad_if_want_setup_components}</p>

    <h3>CMS RuDi</h3>

    <p><a href="https://cmsrudi.ru" target="_blank">https://cmsrudi.ru</a></p>
{else}
    {if !empty($new_components)}
        <p><strong>{$lang->ad_components_setup}</strong></p>
        <table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">
            {foreach from=$new_components item='component'}
                <tr>
                    <td width="16">
                        <img src="/admin/images/icons/hmenu/plugins.png" />
                    </td>
                    <td>
                        <a style="font-weight:bold;font-size:14px" title="{$lang->ad_setup} {$component.title}" href="{$install_link}/{$component.link}">{$component.title}</a> v{$component.version}
                    </td>
                </tr>
                <tr>
                    <td width="16">&nbsp;</td>
                    <td>
                        <div style="margin-bottom:6px;">{$component.description}</div>
                        <div style="color:gray"><strong>{$lang->ad_author}:</strong> {$component.author}</div>
                        <div style="color:gray"><strong>{$lang->ad_folder}:</strong> /components/{$component.link}</div>
                    </td>
                </tr>
            {/foreach}
        </table>
    {/if}
    
    {if !empty($upd_components)}
        <p><strong>{$lang->ad_components_update}</strong></p>
        <table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">
            {foreach from=$upd_components item='component'}
                <tr>
                    <td width="16">
                        <img src="/admin/images/icons/hmenu/plugins.png" />
                    </td>
                    <td>
                        <a style="font-weight:bold;font-size:14px" title="{$lang->ad_update} {$component.title}" href="{$update_link}/{$component.link}">{$component.title}</a> v{$component.version}
                    </td>
                </tr>
                <tr>
                    <td width="16">&nbsp;</td>
                    <td>
                        <div style="margin-bottom:6px;">{$component.description}</div>
                        <div style="color:gray"><strong>{$lang->ad_author}:</strong> {$component.author}</div>
                        <div style="color:gray"><strong>{$lang->ad_folder}:</strong> /components/{$component.link}</div>
                    </td>
                </tr>
            {/foreach}
        </table>
    {/if}

    <p>{$lang->ad_click_to_continue_component}</p>
    <p><a href="javascript:window.history.go(-1);">{$lang->back}</a></p>
{/if}