<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        {printHead}
    </head>

    <body>
        <table cellpadding="0" cellspacing="0" border="0" width="100%" height="100%">
            <tr>
                <td valign="top">
                    <div id="container">
                        <div id="header" style="height:69px">
                            <table width="100%" height="69" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="230" align="left" valign="middle" style="padding-left:20px; padding-top:5px;">
                                        <a href="/admin/">
                                            <img src="/admin/images/toplogo.png" alt="{$lang->ad_admin_panel}" border="0" />
                                        </a>
                                    </td>
                                    <td width="120">
                                        <div class="jdate">{$nowdate}</div>
                                        <div class="jclock">00:00:00</div>
                                    </td>
                                    <td>
                                        <div class="juser">{$lang->ad_you} &mdash; <a href="{$profile_url}" target="_blank" title="{$lang->ad_go_profile}">{$nickname}</a>, ip: {$ip}</div>
                                        <div class="jmessages">
                                            {if  $new_messages.total}
                                                <a href="/users/{$user_id}/messages.html" style="color:yellow">{$lang->ad_new_msg} ({$new_messages.total})</a>
                                            {else}
                                                <span>{$lang->no} {$lang->new_messages}</span>
                                            {/if}
                                        </div>
                                    </td>
                                    <td width="120">
                                        <div class="jsite"><a href="/" target="_blank">{$lang->ad_open_site}</a></div>
                                        <div class="jlogout"><a href="/logout" target="" >{$lang->ad_exit}</a></div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div id="mainmenu" style="height:24px; background:url(/admin/js/hmenu/hmenubg.jpg) repeat-x">
                            <div style="padding-left:15px;height:24px">
                                {$menu}
                            </div>
                        </div>
                        <div id="pathway" style="margin-top:4px;">
                            {printPathway sep='&rarr;' cp='cp'}
                        </div>
                        {if $messages}
                            <div class="sess_messages">
                                {foreach from=$messages item=message}
                                    {$message}
                                {/foreach}
                            </div>
                        {/if}
                        <div id="body" style="padding:5px 10px 10px 10px;">
                            {printToolMenu}
                            {printBody}
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td height="50">
                    <div id="footer">
                        <a href="https://cmsrudi.ru">CMS RuDi</a>
                        v1.0.0 — &copy;
                        <a href="https://ds-soft.ru">DS Soft</a>
                        2018{if $smarty.now|date_format:'%Y' > 2018}-{$smarty.now|date_format:'%Y'}{/if} —
                        <a href="/cp/credits">Credits</a>
                        {show_debug_info}
                    </div>
                    
                </td>
            </tr>
        </table>
    </body>
</html>