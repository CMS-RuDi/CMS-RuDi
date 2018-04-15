{if empty($config) && empty()}
    <p>{$lang->ad_plugin_disable}</p>
    <p><a href="javascript:window.history.go(-1);">{$lang->back}</a></p>
{else}
    <form name="addform" action="{$submit_uri}" method="POST">
        {if !empty($form_html)}
            {$form_html}
        {else}
            <input type="hidden" name="csrf_token" value="{csrf_token}" />

            <table class="proptable" width="605" cellpadding="8" cellspacing="0" border="0">
            {foreach from=$config key=field item=value}
                <tr>
                    <td width="150"><strong>{$lang->e($field)}:</strong></td>
                    <td><input type="text" style="width:90%" name="config[{$field}]" value="{$value|escape:'html'}" /></td>
                </tr>
            {/foreach}
            </table>

            <div style="margin-top:6px;">
                <input type="submit" name="save" value="{$lang->save}" />
                <input type="button" name="back" value="{$lang->cancel}" onclick="window.history.go(-1);" />
            </div>
        {/if}
    </form>
{/if}