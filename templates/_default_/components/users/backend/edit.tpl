{if $ostatok}<h3>{$lang->ad_user_edit} {$ostatok}</h3>{/if}

<form action = "{$submit_uri}" method = "post" enctype = "multipart/form-data" name = "addform" id = "addform">
    <input type = "hidden" name = "csrf_token" value = "{csrf_token}" />
    <table width = "600" border = "0" cellpadding = "0" cellspacing = "10" class = "proptable">
        <tr>
            <td width = "" valign = "middle"><strong>{$lang->ad_login}: </strong></td>
            <td width="220" valign="middle">
                <input name="login" type="text" id="logininput" style="width:220px" value="{if isset($item.login)}{$item.login|escape:'html'}{/if}" onchange="checkLogin();" />
                <div id="logincheck"></div>
            </td>
            <td width="22">
                {if $item.id > 0}
                <a target="_blank" href="/users/{$item.login|escape:'html'}" title="{$lang->ad_user_profile}">
                    <img src="/admin/images/icons/site.png" border="0" alt="{$lang->ad_user_profile}"/>
                </a>
                {/if}
            </td>
        </tr>
        <tr>
            <td valign="middle"><strong>{$lang->ad_nickname}:</strong></td>
            <td valign="middle">
                <input name="nickname" type="text" id="login" style="width:220px" value="{if isset($item.nickname)}{$item.nickname|escape:'html'}{/if}"/>
            </td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td valign="middle"><strong>{$lang->ad_email}: </strong></td>
            <td valign="middle">
                <input name="email" type="text" id="nickname" style="width:220px" value="{if isset($item.email)}{$item.email|escape:'html'}{/if}"/>
            </td>
            <td>&nbsp;</td>
        </tr>
        <tr>
        {if $item.id > 0}
            <td valign="middle"><strong>{$lang->ad_new_pass}:</strong></td>
        {else}
            <td valign="middle"><strong>{$lang->ad_pass}:</strong> </td>
        {/if}
            <td><input name="pass" type="password" id="pass" style="width:220px"/></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td valign="middle"><strong>{$lang->ad_repeat_pass}:</strong> </td>
            <td valign="middle"><input name="pass2" type="password" id="pass2" style="width:220px"/></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td valign="middle"><strong>{$lang->ad_group}:</strong></td>
            <td valign="middle">
                <select name="group_id" id="group_id" style="width:225px">
                    {foreach $groups as $group}
                        <option value="{$group.id}" {if $group.id == $item.group_id}selected{/if}>{$group.title}</option>
                    {/foreach}
                </select>
            </td>
            <td>
            {if $item.id > 0}
                <a target="_blank" href="{$edit_group_uri}/{$item.group_id}">
                    <img src="/admin/images/icons/edit.png" border="0" title="{$lang->edit}"/>
                </a>
            {/if}
            </td>
        </tr>
        <tr>
            <td valign="middle"><strong>{$lang->ad_if_accaunt_lock}</strong></td>
            <td valign="middle">
                <label>
                    <input name="is_locked" type="radio" value="1" {if !empty($item.is_locked)}checked{/if} />{$lang->yes}
                </label>
                <label>
                    <input name="is_locked" type="radio" value="0" {if empty($item.is_locked)}checked{/if} />{$lang->no}
                </label>
            </td>
            <td>&nbsp;</td>
        </tr>
    </table>
    <p>
    {if $item.id > 0}
        <input name="add_mod" type="submit" id="add_mod" value="{$lang->save}" />
    {else}
        <input name="add_mod" type="submit" id="add_mod" value="{$lang->ad_user_add}" />
    {/if}
        <span style="margin-top:15px">
            <input name="back2" type="button" id="back2" value="{$lang->cancel}" onclick="window.history.back();" />
        </span>
    </p>
</form>
