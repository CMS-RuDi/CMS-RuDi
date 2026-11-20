<form action="{$submit_uri}" method="post" name="optform" target="_self" id="form1">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    {literal}{tab={/literal}{$lang->ad_overall}{literal}}{/literal}
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>{$lang->ad_generate_cyryllic_url}: </strong></td>
            <td width="110">
                <label>
                    <input name="is_url_cyrillic" type="radio" value="1" {if !empty($options.is_url_cyrillic)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="is_url_cyrillic" type="radio" value="0" {if empty($options.is_url_cyrillic)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_output_announcements}: </strong></td>
            <td width="110">
                <label>
                    <input name="readdesc" type="radio" value="1" {if !empty($options.readdesc)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="readdesc" type="radio" value="0" {if empty($options.readdesc)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->articles_rating}: </strong></td>
            <td>
                <label>
                    <input name="rating" type="radio" value="1" {if !empty($options.rating)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="rating" type="radio" value="0" {if empty($options.rating)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td>
                <strong>{$lang->ad_generate_key_descr}:</strong>
            </td>
            <td valign="top">
                <label>
                    <input name="autokeys" type="radio" value="1" {if !empty($options.autokeys)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="autokeys" type="radio" value="0" {if empty($options.autokeys)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td>
                <strong>{$lang->ad_hide_root_cats}:</strong>
            </td>
            <td valign="top">
                <label>
                    <input name="hide_root" type="radio" value="1" {if !empty($options.hide_root)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="hide_root" type="radio" value="0" {if empty($options.hide_root)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_number_per_page}: </strong></td>
            <td><input class="uispin" name="perpage" type="text" id="perpage" value="{$options.perpage}" size="5" /></td>
        </tr>
    </table>
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>{$lang->ad_show_content}: </strong></td>
            <td width="110">
                <label>
                    <input name="pt_show" type="radio" value="1" {if !empty($options.pt_show)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="pt_show" type="radio" value="0" {if empty($options.pt_show)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_deploy_content}: </strong></td>
            <td>
                <label>
                    <input name="pt_disp" type="radio" value="1" {if !empty($options.pt_disp)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="pt_disp" type="radio" value="0" {if empty($options.pt_disp)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_hide_content}: </strong></td>
            <td>
                <label>
                    <input name="pt_hide" type="radio" value="1" {if !empty($options.pt_hide)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="pt_hide" type="radio" value="0" {if empty($options.pt_hide)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
    </table>
    {literal}{tab={/literal}{$lang->ad_photo_art}{literal}}{/literal}
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>{$lang->ad_photo_small}:</strong></td>
            <td width="120">
                <input class="uispin" name="img_small_w" type="text" id="img_small_w" value="{$options.img_small_w}" size="5" />
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_photo_big}:</strong></td>
            <td>
                <input class="uispin" name="img_big_w" type="text" id="img_big_w" value="{$options.img_big_w}" size="5" />
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_photo_square}:</strong></td>
            <td>
                <label>
                    <input name="img_sqr" type="radio" value="1" {if !empty($options.img_sqr)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="img_sqr" type="radio" value="0" {if empty($options.img_sqr)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td>
                <strong>{$lang->ad_allow_users_to}:</strong><br/>
                <span class="hinttext">{$lang->ad_allow_users_to_hint}</span>
            </td>
            <td>
                <label>
                    <input name="img_users" type="radio" value="1" {if !empty($options.img_users)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="img_users" type="radio" value="0" {if empty($options.img_users)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
        <tr>
            <td><strong>{$lang->ad_enable_watermark}</strong><br />
                <span class="hinttext">{$lang->ad_watermark_hint} "<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>"</span></td>
            <td width="260">
                <label>
                    <input name="watermark" type="radio" value="1" {if !empty($options.watermark)}checked{/if}/>
                    {$lang->yes}
                </label>
                <label>
                    <input name="watermark" type="radio" value="0" {if empty($options.watermark)}checked{/if}/>
                    {$lang->no}
                </label>
            </td>
        </tr>
    </table>
    {literal}{/tabs}{/literal}
    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="{$lang->save}" />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.location.href = '{$base_uri}';"/>
    </p>
</form>
