<form action="{$submit_uri}" method="post" enctype="multipart/form-data" name="addform" id="addform">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    
    <table width="750" border="0" cellpadding="0" cellspacing="10" class="proptable">
        <tr>
            <td width="300" valign="middle">
                <strong>{$lang->title}:</strong><br/>
                <span class="hinttext">{$lang->ad_only_latin}</span>
            </td>
            <td width="" valign="middle">
                <input name="job_name" type="text" style="width:220px" value="{if !empty($item)}{$item.job_name}{/if}" />
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->description}: </strong><br/>
                <span class="hinttext">{$lang->ad_only_200_simbols}</span>
            </td>
            <td valign="middle">
                <input name="comment" type="text" maxlength="200" style="width:400px" value="{if !empty($item)}{$item.comment|escape:'html'}{/if}" />
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->ad_mission_on} </strong><br/>
                <span class="hinttext">{$lang->ad_mission_off}</span>
            </td>
            <td valign="middle">
                <label>
                    <input name="enabled" type="radio" value="1"{if !empty($item.is_enabled} checked="checked"{/if} /> {$lang->yes}
                </label>
                <label>
                    <input name="enabled" type="radio" value="0"{if empty($item.is_enabled} checked="checked"{/if} /> {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->ad_mission_interval}:</strong><br/>
                <span class="hinttext">{$lang->ad_mission_period}</span>
            </td>
            <td valign="middle">
                <input name="job_interval" type="text" maxlength="4" style="width:50px" value="{if !empty($item)}{$item.job_interval}{/if}" /> {$lang->hour1}.
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->ad_php_file} </strong><br/>
                <span class="hinttext">{$lang->ad_example}: <strong>includes/myphp/test.php</strong></span><br/>
            </td>
            <td valign="middle">
                <input name="custom_file" type="text" maxlength="250" style="width:220px" value="{if !empty($item)}{$item.custom_file}{/if}" />
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->ad_component}: </strong><br/>
            </td>
            <td valign="middle">
                <input name="component" type="text" maxlength="250" style="width:220px" value="{if !empty($item)}{$item.component}{/if}" />
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->ad_method} </strong><br/>
            </td>
            <td valign="middle">
                <input name="model_method" type="text" maxlength="250" style="width:220px" value="{if !empty($item)}{$item.model_method}{/if}" />
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->ucf('ad_class')}</strong><br/>
                <span class="hinttext">
                    <span style="color:#666;font-family: mono">{$lang->ad_file_class}</span>, {$lang->ad_example} <strong>actions|cmsActions</strong>&nbsp;{$lang->or}<br/>
                    <span style="color:#666;font-family: mono">{$lang->ad_class}</span>, {$lang->ad_example} <strong>cmsDatabase</strong>
                </span>
            </td>
            <td valign="top">
                <input name="class_name" type="text" maxlength="50" style="width:220px" value="{if !empty($item)}{$item.class_name}{/if}" />
            </td>
        </tr>
        <tr>
            <td width="" valign="middle">
                <strong>{$lang->ad_class_method} </strong><br/>
            </td>
            <td valign="middle">
                <input name="class_method" type="text" maxlength="50" style="width:220px" value="{if !empty($item)}{$item.class_method}{/if}" />
            </td>
        </tr>
    </table>
    <p>
        <input name="add_mod" type="submit" id="add_mod" value="{if !empty($item)}{$lang->ad_save_cron_mission}{else}{$lang->ad_create_cron_mission}{/if}" />

        <span style="margin-top:15px">
            <input name="back2" type="button" id="back2" value="{$lang->cancel}" onclick="window.history.back();" />
        </span>
        
        {if !empty($item)}<input type="hidden" name="item_id" value="{$item.id}" />{/if}
    </p>
</form>