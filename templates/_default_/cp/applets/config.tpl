<div>
    <div id="config_tabs" class="uitabs">

        <ul id="tabs">
            <li><a href="#basic"><span>{$lang->ad_site}</span></a></li>
            <li><a href="#home"><span>{$lang->ad_main}</span></a></li>
            <li><a href="#design"><span>{$lang->ad_design}</span></a></li>
            <li><a href="#time"><span>{$lang->ad_time}</span></a></li>
            <li><a href="#database"><span>{$lang->ad_db}</span></a></li>
            <li><a href="#mail"><span>{$lang->ad_post}</span></a></li>
            <li><a href="#other"><span>{$lang->ad_pathway}</span></a></li>
            <li><a href="#seq"><span>{$lang->ad_security}</span></a></li>
        </ul>

        <form action="{$submit_uri}" method="post" name="CFGform" target="_self" id="CFGform" style="margin-bottom:30px">
            <input type="hidden" name="csrf_token" value="{csrf_token}" />
            
            <div id="basic">
                <table width="720" border="0" cellpadding="5">
                    <tr>
                        <td>
                            <strong>{$lang->ad_sitename}</strong><br/>
                            <span class="hinttext">{$lang->ad_use_header}</span>
                        </td>
                        <td width="350" valign="top">
                            <input name="sitename" type="text" id="sitename" value="{$config.sitename|escape:'html'}" style="width:358px" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_tage_add}</strong>
                        </td>
                        <td valign="top">
                            <label><input name="title_and_sitename" type="radio" value="1"{if !empty($config.title_and_sitename)} checked="checked"{/if} /> {$lang->yes}</label>
                            <label><input name="title_and_sitename" type="radio" value="0"{if empty($config.title_and_sitename)} checked="checked"{/if} /> {$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_tage_add_pagination}</strong>
                        </td>
                        <td valign="top">
                            <label><input name="title_and_page" type="radio" value="1"{if !empty($config.title_and_page)} checked="checked"{/if} /> {$lang->yes}</label>
                            <label><input name="title_and_page" type="radio" value="0"{if empty($config.title_and_page)} checked="checked"{/if} /> {$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->template_interface_lang}:</strong>
                        </td>
                        <td width="350" valign="top">
                            <select name="lang" id="lang" style="width:364px">
                                {foreach from=$langs item=lng}
                                    <option value="{$lng}"{if $config.lang == $lng} selected="selected"{/if}>{$lng}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_site_language_change}</strong><br/>
                            <span class="hinttext">{$lang->ad_view_form_language_change}</span>
                        </td>
                        <td valign="top">
                            <label><input name="is_change_lang" type="radio" value="1"{if !empty($config.is_change_lang)} checked="checked"{/if} /> {$lang->yes}</label>
                            <label><input name="is_change_lang" type="radio" value="0"{if empty($config.is_change_lang)} checked="checked"{/if} /> {$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_site_on}</strong><br/>
                            <span class="hinttext">{$lang->ad_only_admins}</span>
                        </td>
                        <td valign="top">
                            <label><input name="siteoff" type="radio" value="0"{if !empty($config.siteoff)} checked="checked"{/if} /> {$lang->yes} </label>
                            <label><input name="siteoff" type="radio" value="1"{if empty($config.siteoff)} checked="checked"{/if} /> {$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->AD_DEBUG_ON}</strong><br/>
                            <span class="hinttext">{$lang->AD_WIEW_DB_ERRORS}</span>
                        </td>
                        <td valign="top">
                            <label><input name="debug" type="radio" value="1"{if !empty($config.debug)} checked="checked"{/if} /> {$lang->yes}</label>
                            <label><input name="debug" type="radio" value="0"{if empty($config.debug)} checked="checked"{/if} />{$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td valign="middle">
                            <strong>{$lang->ad_why_stop}</strong><br />
                            <span class="hinttext">{$lang->ad_view_why_stop}</span>

                        </td>
                        <td valign="top"><input name="offtext" type="text" id="offtext" value="{$config.offtext|escape:'html'}" style="width:358px" /></td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_watermark} </strong><br/>
                            <span class="hinttext">{$lang->ad_watermark_name}</span>
                        </td>
                        <td>
                            <input name="wmark" type="text" id="wmark" value="{$config.wmark}" style="width:358px" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_quick_config}</strong> <br />
                            <span class="hinttext">{$lang->ad_module_config}</span>
                        </td>
                        <td valign="top">
                            <label><input name="fastcfg" type="radio" value="1"{if !empty($config.fastcfg)} checked="checked"{/if} /> {$lang->yes}</label>
                            <label><input name="fastcfg" type="radio" value="0"{if empty($config.fastcfg)} checked="checked"{/if} /> {$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_onlinestats}</strong>
                        </td>
                        <td valign="top">
                            <label><input name="user_stats" type="radio" value="0"{if empty($config.user_stats)} checked="checked"{/if} />{$lang->ad_no_onlinestats}</label><br>
                            <label><input name="user_stats" type="radio" value="1"{if !empty($config.user_stats) && $config.user_stats == 1} checked="checked"{/if} />{$lang->ad_yes_onlinestats}</label><br>
                            <label><input name="user_stats" type="radio" value="2"{if !empty($config.user_stats) && $config.user_stats == 2} checked="checked"{/if} />{$lang->ad_cron_onlinestats}</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_seo_url_count} </strong><br/>
                            <span class="hinttext">{$lang->ad_seo_url_count_hint}</span>
                        </td>
                        <td>
                            <input name="seo_url_count" type="text" class="uispin" value="{$config.seo_url_count}" style="width:50px" />
                        </td>
                    </tr>
                </table>
            </div>
            <div id="home">
                <table width="720" border="0" cellpadding="5">
                    <tr>
                        <td>
                            <strong>{$lang->ad_main_page}</strong><br />
                            <span class="hinttext">{$lang->ad_main_sitename}</span><br/>
                            <span class="hinttext">{$lang->ad_browser_title}</span>
                        </td>
                        <td width="350" valign="top">
                            <input name="hometitle" type="text" id="hometitle" value="{$config.hometitle|escape:'html'}" style="width:358px" />
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <strong>{$lang->ad_key_words}</strong><br />
                            <span class="hinttext">{$lang->ad_from_comma}</span>
                            <div class="hinttext" style="margin-top:4px"><a style="color:#09C" href="http://tutorial.semonitor.ru/#5" target="_blank">{$lang->ad_what_key_words}</a></div>
                        </td>
                        <td>
                            <textarea name="keywords" style="width:350px" rows="3" id="keywords">{$config.keywords}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <strong>{$lang->ad_description}</strong><br />
                            <span class="hinttext">{$lang->ad_less_than}</span>
                            <div class="hinttext" style="margin-top:4px"><a style="color:#09C" href="http://tutorial.semonitor.ru/#219" target="_blank">{$lang->ad_what_description}</a></div>
                        </td>
                        <td>
                            <textarea name="metadesc" style="width:350px" rows="3" id="metadesc">{$config.metadesc}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_main_page_component}</strong>
                        </td>
                        <td width="350" valign="top">
                            <select name="homecom" style="width:358px">
                                <option value=""{if empty($config.homecom)} selected="selected"{/if}>{$lang->ad_only_modules}</option>
                                {$components_list}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_gate_page}</strong> <br/>
                            <span class="hinttext">{$lang->ad_first_visit}</span> <br/>
                            <span class="hinttext">{$lang->ad_first_visit_template}</strong></span>
                        </td>
                        <td valign="top">
                            <label><input name="splash" type="radio" value="0"{if empty($config.splash)} checked="checked"{/if} /> {$lang->hide}</label>
                            <label><input name="splash" type="radio" value="1"{if !empty($config.splash)} checked="checked"{/if} /> {$lang->show}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="design">
                <table width="100%" cellpadding="5">
                    <tr>
                        <td valign="top" width="300">
                            <div style="margin-top:2px">
                                <strong>{$lang->template}:</strong><br />
                                <span class="hinttext">{$lang->ad_template_folder} </span>
                            </div>
                        </td>
                        <td>
                            <select name="template" id="template" style="width:350px" onchange="document.CFGform.submit();">
                                {foreach from=$templates item=template}
                                    <option value="{$template}"{if $config.template == $template} selected="selected"{/if}>{$template}</option>
                                {/foreach}
                                ?>
                            </select>
                            {if !empty($position_view)}
                                <script>
                                    $(function () {
                                        $('#pos').dialog({ modal: true, autoOpen: false, closeText: LANG_CLOSE, width: 'auto' });
                                    });
                                </script>
                                <a onclick="$('#pos').dialog('open');return false;" href="#" class="ajaxlink">{$lang->ad_tpl_pos}</a>
                                <div id="pos" title="{$lang->ad_tpl_pos}"><img src="/templates/{template}/positions.jpg" alt="{$lang->ad_tpl_pos}" /></div>
                            {/if}
                            <div style="margin-top:5px" class="hinttext">
                                {$tpl_info}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>{$lang->ad_search_result}</strong></td>
                        <td valign="top">
                            <label><input name="slight" type="radio" value="1"{if !empty($config.slight)} checked="checked"{/if}/> {$lang->yes}</label>
                            <label><input name="slight" type="radio" value="0"{if empty($config.slight)} checked="checked"{/if}/> {$lang->no}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="time">
                <table width="720" border="0" cellpadding="5">
                    <tr>
                        <td valign="top" width="100">
                            <div style="margin-top:2px">
                                <strong>{$lang->ad_time_arrea}</strong>
                            </div>
                        </td>
                        <td>
                            <select name="timezone" id="timezone" style="width:350px">
                                {$timezone_list}
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="database">
                <table width="720" border="0" cellpadding="5" style="margin-top:15px;">
                    <tr>
                        <td>
                            <strong>{$lang->ad_db_size}</strong>
                        </td>
                        <td width="350">
                            {$db_size}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><span class="hinttext">{$lang->ad_mysql_config}</span></td>
                    </tr>
                </table>
            </div>
            <div id="mail">
                <table width="720" border="0" cellpadding="5" style="margin-top:15px;">
                    <tr>
                        <td width="250">
                            <strong>{$lang->ad_site_email} </strong><br/>
                            <span class="hinttext">{$lang->ad_site_email_post}</span>
                        </td>
                        <td>
                            <input name="sitemail" type="text" id="sitemail" value="{$config.sitemail}" style="width:358px" />
                        </td>
                    </tr>
                    <tr>
                        <td width="250">
                            <strong>{$lang->ad_sender_email}</strong><br/>
                            <span class="hinttext">{$lang->ad_if_not_handler}</span>
                        </td>
                        <td>
                            <input name="sitemail_name" type="text" id="sitemail_name" value="{$config.sitemail_name}" style="width:358px" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_send_method}</strong>
                        </td>
                        <td>
                            <select name="mailer" style="width:354px">
                                <option value="mail"{if $config.mailer == 'mail'} selected="selected"{/if}>{$lang->ad_php_mailer}</option>
                                <option value="sendmail"{if $config.mailer == 'sendmail'} selected="selected"{/if}>{$lang->ad_send_mailer}</option>
                                <option value="smtp"{if $config.mailer == 'smtp'} selected="selected"{/if}>{$lang->ad_smtp_mailer}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_encrypting}</strong>
                        </td>
                        <td>
                            <label><input name="smtpsecure" type="radio" value=""{if empty($config.smtpsecure)} checked="checked"{/if} />{$lang->no}</label>
                            <label><input name="smtpsecure" type="radio" value="tls"{if !empty($config.smtpsecure) && $config.smtpsecure == 'tls'} checked="checked"{/if} /> tls</label>
                            <label><input name="smtpsecure" type="radio" value="ssl"{if !empty($config.smtpsecure) && $config.smtpsecure == 'ssl'} checked="checked"{/if} /> ssl</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_smtp_login}</strong>
                        </td>
                        <td>
                            <label><input name="smtpauth" type="radio" value="1"{if !empty($config.smtpauth)}checked="checked"{/if} />{$lang->yes}</label>
                            <label><input name="smtpauth" type="radio" value="0"{if empty($config.smtpauth)}checked="checked"{/if} />{$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_smtp_user}</strong>
                        </td>
                        <td>
                            {if empty($config.smtpuser)}
                                <input name="smtpuser" type="text" id="smtpuser" value="" style="width:350px" />
                            {else}
                                <span class="hinttext">{$lang->ad_if_change_user}</span>
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_smtp_pass}</strong>
                        </td>
                        <td>
                            {if empty($config.smtppass)}
                                <input name="smtppass" type="password" id="smtppass" value="" style="width:350px" />
                            {else}
                                <span class="hinttext">{$lang->ad_if_change_pass}</span>
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_smtp_host}</strong><br>
                            <span class="hinttext">{$lang->ad_some_host}</span>
                        </td>
                        <td>
                            <input name="smtphost" type="text" id="smtphost" value="{$config.smtphost}" style="width:350px" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{$lang->ad_smtp_port}</strong>
                        </td>
                        <td>
                            <input name="smtpport" type="text" id="smtpport" value="{$config.smtpport}" style="width:350px" />
                        </td>
                    </tr>
                </table>
            </div>
            <div id="other">
                <table width="720" border="0" cellpadding="5">
                    <tr>
                        <td>
                            <strong>{$lang->ad_view_pathway}</strong><br />
                            <span class="hinttext">
                                {$lang->ad_path_to_category}
                            </span>
                        </td>
                        <td>
                            <label><input name="show_pw" type="radio" value="1"{if !empty($config.show_pw)} checked="checked"{/if} /> {$lang->yes}</label>
                            <label><input name="show_pw" type="radio" value="0"{if empty($config.show_pw)} checked="checked"{/if}/> {$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>{$lang->ad_mainpage_pathway}</strong></td>
                        <td>
                            <label><input name="index_pw" type="radio" value="1"{if !empty($config.index_pw)} checked="checked"{/if} /> {$lang->yes}</label>
                            <label><input name="index_pw" type="radio" value="0"{if empty($config.index_pw)} checked="checked"{/if} /> {$lang->no}</label>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>{$lang->ad_page_pathway}</strong></td>
                        <td>
                            <label><input name="last_item_pw" type="radio" value="0"{if empty($config.last_item_pw)} checked="checked"{/if} /> {$lang->hide}</label>
                            <label><input name="last_item_pw" type="radio" value="1"{if !empty($config.last_item_pw) && $config.last_item_pw == 1} checked="checked"{/if} /> {$lang->ad_page_pathway_link}</label>
                            <label><input name="last_item_pw" type="radio" value="2"{if !empty($config.last_item_pw) && $config.last_item_pw == 2} checked="checked"{/if} /> {$lang->ad_page_pathway_text}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="seq">
                <table width="720" border="0" cellpadding="5">
                    <tr>
                        <td>
                            <strong>{$lang->ad_ip_admin}</strong> <br />
                            <span class="hinttext">{$lang->ad_ip_comma}</span></td>
                        <td valign="top">
                            <input name="allow_ip" type="text" id="allow_ip" value="{$config.allow_ip|escape:'html'}" style="width:358px" /></td>
                    </tr>
                </table>
                <p style="color:#900">{$lang->ad_attention}</p>
            </div>

            <div align="left">
                <input name="save" type="submit" id="save" value="{$lang->save}" />
                <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.history.back();" />
            </div>
        </form>
    </div>
</div>