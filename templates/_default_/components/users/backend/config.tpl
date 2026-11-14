<form action="{$submit_uri}" method="post" name="optform" id="form1">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    <div id="config_tabs" style="margin-top:12px;" class="uitabs">

        <ul id="tabs">
            <li><a href="#basic"><span>{$lang->ad_profile_settings}</span></a></li>
            <li><a href="#avatars"><span>{$lang->ad_avatars}</span></a></li>
            <li><a href="#proftabs"><span>{$lang->ad_profiles_tab}</span></a></li>
            <li><a href="#forms"><span>{$lang->ad_more_fields}</span></a></li>
            <li><a href="#photos"><span>{$lang->ad_profile_settings}</span></a></li>
            <li><a href="#files"><span>{$lang->ad_file_archives}</span></a></li>
        </ul>

        <div id="basic">
            <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
                <tr>
                    <td><strong>{$lang->ad_viev_profiles}: </strong></td>
                    <td width="230">
                        <label>
                            <input name="sw_guest" type="radio" value="1" {if $options.sw_guest}checked{/if} /> {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_guest" type="radio" value="0" {if !$options.sw_guest}checked{/if} /> {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_search_users}: </strong></td>
                    <td>
                        <label>
                            <input name="sw_search" type="radio" value="1" {if $options.sw_search == 1}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_search" type="radio" value="0" {if $options.sw_search == 0}checked{/if} />
                            {$lang->ad_yes_only_view}
                        </label>
                        <label>
                            <input name="sw_search" type="radio" value="2" {if $options.sw_search == 2}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_show_more_comments}: </strong></td>
                    <td width="">
                        <label>
                            <input name="sw_comm" type="radio" value="1" {if $options.sw_comm}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_comm" type="radio" value="0" {if !$options.sw_comm}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                {if $forum_enabled}
                    <tr>
                        <td><strong>{$lang->ad_show_forum}: </strong></td>
                        <td>
                            <label>
                                <input name="sw_forum" type="radio" value="1" {if $options.sw_forum}checked{/if} />
                                {$lang->yes}
                            </label>
                            <label>
                                <input name="sw_forum" type="radio" value="0" {if !$options.sw_forum}checked{/if} />
                                {$lang->no}
                            </label>
                        </td>
                    </tr>
                {/if}
                <tr>
                    <td><strong>{$lang->ad_users_wall}: </strong></td>
                    <td>
                        <label>
                            <input name="sw_wall" type="radio" value="1" {if $options.sw_wall}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_wall" type="radio" value="0" {if !$options.sw_wall}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_personal_blogs}:</strong></td>
                    <td>
                        <label>
                            <input name="sw_blogs" type="radio" value="1" {if $options.sw_blogs}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_blogs" type="radio" value="0" {if !$options.sw_blogs}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                {if $blogs_enabled}
                    <tr>
                        <td><strong>{$lang->ad_show_ads}:</strong></td>
                        <td>
                            <label>
                                <input name="sw_board" type="radio" value="1" {if $options.sw_board}checked{/if} />
                                {$lang->yes}
                            </label>
                            <label>
                                <input name="sw_board" type="radio" value="0" {if !$options.sw_board}checked{/if} />
                                {$lang->no}
                            </label>
                        </td>
                    </tr>
                {/if}
                <tr>
                    <td><strong>{$lang->ad_private_mess}:</strong> </td>
                    <td>
                        <label>
                            <input name="sw_msg" type="radio" value="1" {if $options.sw_msg}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_msg" type="radio" value="0" {if !$options.sw_msg}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_notification_text}: </strong></td>
                    <td>/languages/{$lang->getLang()}/letters/newmessage.txt</td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_perod_karma}:</strong><br />
                        <span class="hinttext">{$lang->ad_perod_karma_hint} </span>
                    </td>
                    <td valign="top">
                        <input class="uispin" name="karmatime" type="text" id="int_1" size="5" value="{$options.karmatime}"/>
                        <select name="karmaint" id="int_2">
                            <option value="MINUTE" {if $options.karmaint == 'MINUTE'}selected{/if}>{$lang->minute10}</option>
                            <option value="HOUR" {if $options.karmaint == 'HOUR'}selected{/if}>{$lang->hour10}</option>
                            <option value="DAY" {if $options.karmaint == 'DAY'}selected{/if}>{$lang->day10}</option>
                            <option value="MONTH" {if $options.karmaint == 'MONTH'}selected{/if}>{$lang->month10}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_delete_inactive}:</strong><br />
                        <span class="hinttext">{$lang->ad_delete_inactive_hint}</span>
                    </td>
                    <td valign="top">
                        <input class="uispin" name="deltime" type="text" id="deltime" size="5" value="{$options.deltime}"/>
                        {$lang->month10}
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_users_on_page}:</strong>
                    </td>
                    <td valign="top">
                        <input class="uispin" name="users_perpage" type="text" id="users_perpage" size="5" value="{$options.users_perpage}"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_number_on_wall}:</strong>
                    </td>
                    <td valign="top">
                        <input class="uispin" name="wall_perpage" type="text" id="wall_perpage" size="5" value="{$options.wall_perpage}"/>
                    </td>
                </tr>
            </table>
        </div>

        <div id="avatars">
            <table width="450" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
                <tr>
                    <td><strong>{$lang->ad_width_small_avatar}: </strong></td>
                    <td width="210">
                        <input class="uispin" name="smallw" type="text" id="smallw" size="5" value="{$options.smallw}"/>
                        {$lang->ad_px}
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_width_large_avatar}: </strong></td>
                    <td>
                        <input class="uispin" name="medw" type="text" id="medw" size="5" value="{$options.medw}"/>
                        {$lang->ad_px}
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_height_large_avatar}: </strong></td>
                    <td>
                        <input class="uispin" name="medh" type="text" id="medh" size="5" value="{$options.medh}"/>
                        {$lang->ad_px}
                    </td>
                </tr>
            </table>
        </div>


        <div id="proftabs">
            <table width="450" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
                <tr>
                    <td><strong>{$lang->ad_tab_ribbon}</strong></td>
                    <td width="210">
                        <label>
                            <input name="sw_feed" type="radio" value="1" {if $options.sw_feed}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_feed" type="radio" value="0" {if !$options.sw_feed}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_tab_clubs}</strong></td>
                    <td>
                        <label>
                            <input name="sw_clubs" type="radio" value="1" {if $options.sw_clubs}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label><input name="sw_clubs" type="radio" value="0" {if !$options.sw_clubs}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><strong>{$lang->ad_tab_awards}</strong></td>
                    <td>
                        <label>
                            <input name="sw_awards" type="radio" value="1" {if $options.sw_awards}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_awards" type="radio" value="0" {if !$options.sw_awards}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div id="forms">
            <table width="380" border="0" cellspacing="0" cellpadding="10" class="proptable" style="border:none">
                <tr>
                    <td valign="top">
                        <div>{$lang->ad_forms_in_profiles}: </div>
                        <p>
                            <select name="privforms[]" size="10" style="width:350px; border:solid 1px silver;" multiple="multiple">
                                {foreach $forms as $f}
                                <option value="{$f.id}" {if $f.id is in $options.privforms}selected{/if}>{$f.title}</option>
                                {/foreach}
                            </select>
                        </p>
                        <p>{$lang->ad_select_multiple_ctrl}.</p>
                        <p>{$lang->ad_forms_in_profiles_edit} <a href="/cp/components/forms">{$lang->ad_form_designer}</a>.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="photos">
            <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
                <tr>
                    <td><strong>{$lang->ad_photo_albums}: </strong></td>
                    <td width="210">
                        <label>
                            <input name="sw_photo" type="radio" value="1" {if $options.sw_photo}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_photo" type="radio" value="0" {if !$options.sw_photo}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_enable_watermark}</strong> <br />
                        <span class="hinttext">{$lang->ad_apply_watermark_hint} &quot;<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>&quot;</span>
                    </td>
                    <td valign="top">
                        <label>
                            <input name="watermark" type="radio" value="1" {if $options.watermark}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="watermark" type="radio" value="0" {if !$options.watermark}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_lots_photos}:</strong><br />
                        <span class="hinttext">{$lang->ad_lots_photos_hint}</span>
                    </td>
                    <td>
                        <input class="uispin" name="photosize" type="text" id="photosize" size="5" value="{$options.photosize}"/>
                        {$lang->ad_pieces}
                    </td>
                </tr>
            </table>
        </div>

        <div id="files">
            <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
                <tr>
                    <td><strong>{$lang->ad_user_files}: </strong></td>
                    <td width="210">
                        <label>
                            <input name="sw_files" type="radio" value="1" {if $options.sw_files}checked{/if} />
                            {$lang->yes}
                        </label>
                        <label>
                            <input name="sw_files" type="radio" value="0" {if !$options.sw_files}checked{/if} />
                            {$lang->no}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_disk_space}:</strong><br />
                        <span class="hinttext">{$lang->ad_disk_space_hint}</span>
                    </td>
                    <td><input class="uispin" name="filessize" type="text" id="filessize" size="5" value="{$options.filessize}"/> {$lang->size_mb}</td>
                </tr>
                <tr>
                    <td>
                        <strong>{$lang->ad_file_tipes}:</strong><br />
                        <span class="hinttext">{$lang->ad_file_tipes_hint}</span>
                    </td>
                    <td>
                        <input name="filestype" type="text" id="filestype" size="30" value="{$options.filestype}"/>
                    </td>
                </tr>
            </table>
        </div>

    </div>
    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="{$lang->save}" />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.location.href = '/cp/components';"/>
    </p>
</form>