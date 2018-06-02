<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0">
    <tr>
        <td width="275" valign="top" style="padding-left:0px;">
            <div class="small_box">
                <div class="small_title">{$lang->ad_site_content}</div>
                <div style="padding:8px">
                    <table width="100%" border="0" align="center" cellpadding="5" cellspacing="0">
                        {if $content_counts.content !== false}
                            <tr>
                                <td><a href="/cp/components/content">{$lang->ad_articles}</a> {if $content_counts.content}<span class="new_content">+{$content_counts.content}</span>{/if}</td>
                                <td width="20" align="center"><a href="/cp/components/content/add_category"><img src="/admin/images/mainpage/folder_add.png" alt="{$lang->ad_create_section}" width="16" height="16" border="0" /></a></td>
                                <td width="20" align="center"><a href="/cp/components/content/add_item"><img src="/admin/images/mainpage/page_add.png" alt="{$lang->ad_create_article}" width="16" height="16" border="0" /></a></td>
                            </tr>
                        {/if}

                        {if $content_counts.photos !== false}
                            <tr>
                                <td><a href="/cp/components/photos">{$lang->ad_photogallery}</a> {if $content_counts.photos}<span class="new_content">+{$content_counts.photos}</span>{/if}</td>
                                <td align="center"><a href="/cp/components/photos/add_album"><img src="/admin/images/mainpage/folder_add.png" alt="{$lang->ad_create_album}" width="16" height="16" border="0" /></a></td>
                                <td align="center"></td>
                            </tr>
                        {/if}

                        {if $content_counts.video !== false}
                            <tr>
                                <td><a href="/cp/components/video">{$lang->ad_videogallery}</a> {if $content_counts.video}<span class="new_content">+{$content_counts.video}</span>{/if}</td>
                                <td align="center"><a href="/cp/components/video/add_cat"><img src="/admin/images/mainpage/folder_add.png" alt="{'ad_create_category'}" width="16" height="16" border="0" /></a></td>
                                <td align="center"></td>
                            </tr>
                        {/if}

                        {if $content_counts.audio !== false}
                            <tr>
                                <td><a href="/cp/components/audio">{$lang->ad_iaudio}</a></td>
                                <td align="center"></td>
                                <td align="center"></td>
                            </tr>
                        {/if}

                        {if $content_counts.maps !== false}
                            <tr>
                                <td><a href="/cp/components/maps">{$lang->ad_geo_catalog}</a> {if $content_counts.maps}<span class="new_content">+{$content_counts.maps}</span>{/if}</td>
                                <td align="center"><a href="/cp/components/maps/add_cat"><img src="/admin/images/mainpage/folder_add.png" alt="{$lang->ad_create_category}" width="16" height="16" border="0" /></a></td>
                                <td align="center"><a href="/cp/components/maps/add_item"><img src="/admin/images/mainpage/page_add.png" alt="{$lang->ad_add_object}" width="16" height="16" border="0" /></a></td>
                            </tr>
                        {/if}

                        {if $content_counts.faq !== false}
                            <tr>
                                <td><a href="/cp/components/faq">{$lang->get('ad_a&q')}</a> {if $content_counts.faq}<span class="new_content">+{$content_counts.faq}</span>{/if}</td>
                                <td align="center"><a href="/cp/components/faq/add_cat"><img src="/admin/images/mainpage/folder_add.png" alt="{$lang->ad_create_category}" width="16" height="16" border="0" /></a></td>
                                <td align="center"><a href="/cp/components/faq/add_item"><img src="/admin/images/mainpage/page_add.png" alt="{$lang->ad_create_question}" width="16" height="16" border="0" /></a></td>
                            </tr>
                        {/if}

                        {if $content_counts.board !== false}
                            <tr>
                                <td><a href="/cp/components/board">{$lang->ad_board}</a> {if $content_counts.board}<span class="new_content">+{$content_counts.board}</span>{/if}</td>
                                <td align="center"><a href="/cp/components/board/add_cat"><img src="/admin/images/mainpage/folder_add.png" alt="{$lang->ad_create_rubric}" width="16" height="16" border="0" /></a></td>
                                <td align="center"><a href="/cp/components/board/add_item"><img src="/admin/images/mainpage/page_add.png" alt="{$lang->ad_create_advert}" width="16" height="16" border="0" /></a></td>
                            </tr>
                        {/if}

                        {if $content_counts.catalog !== false}
                            <tr>
                                <td><a href="/cp/components/catalog">{$lang->ad_catalog}</a> {if $content_counts.catalog}<span class="new_content">+{$content_counts.catalog}</span>{/if}</td>
                                <td align="center"><a href="/cp/components/catalog/add_cat"><img src="/admin/images/mainpage/folder_add.png" alt="{$lang->ad_create_rubric}" width="16" height="16" border="0" /></a></td>
                                <td align="center"><a href="/cp/components/catalog/add_item"><img src="/admin/images/mainpage/page_add.png" alt="{$lang->ad_create_item}" width="16" height="16" border="0" /></a></td>
                            </tr>
                        {/if}

                        {if $content_counts.forum !== false}
                            <tr>
                                <td><a href="/cp/components/forum/list_forums">{$lang->ad_forums}</a> {if $content_counts.forum}<span class="new_content">+{$content_counts.forum}</span>{/if}</td>
                                <td align="center"><a href="/cp/components/forum/add_cat"><img src="/admin/images/mainpage/folder_add.png" alt="{$lang->ad_create_category}" width="16" height="16" border="0" /></a></td>
                                <td align="center"><a href="/cp/components/forum/add_forum"><img src="/admin/images/mainpage/page_add.png" alt="{$lang->ad_create_forum}" width="16" height="16" border="0" /></a></td>
                            </tr>
                        {/if}
                    </table>
                </div>
            </div>
            <div class="small_box">
                <div class="small_title">{$lang->ad_users}</div>
                <div style="padding:8px">
                    <table width="100%" border="0" align="center" cellpadding="5" cellspacing="0">
                        <tr>
                            <td width="16"><img src="/admin/images/icons/hmenu/users.png" width="16" height="16" /></td>
                            <td><a href="/cp/components/users">{$lang->ad_from_users}</a> &mdash; {$total_users}</td>
                        </tr>
                        <tr>
                            <td><img src="/admin/images/icons/hmenu/users.png" width="16" height="16" /></td>
                            <td>{$lang->ad_new_users_today} &mdash; {$today_reg_users}</td>
                        </tr>
                        <tr>
                            <td><img src="/admin/images/icons/hmenu/users.png" width="16" height="16" /></td>
                            <td>{$lang->ad_new_users_thees_week} &mdash; {$week_reg_users}</td>
                        </tr>
                        <tr>
                            <td><img src="/admin/images/icons/hmenu/users.png" width="16" height="16" /></td>
                            <td>{$lang->ad_new_users_thees_month} &mdash; {$month_reg_users}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="small_box">
                <div class="small_title"><strong>{$lang->ad_users_online}</strong></div>
                <div style="font-size:10px;margin:8px;">
                    <div>
                        <table width="100%" cellpadding="2" cellspacing="2">
                            <tr>
                                <td width="24" valign="top">
                                    <img src="/admin/images/user.gif"/>
                                </td>
                                <td width="" valign="top">
                                    <div><strong>{$lang->AD_FROM_USERS}: </strong>{$people.users}</div>
                                    <div><strong>{$lang->AD_FROM_GUESTS}: </strong>{$people.guests}</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </td>
        <td width="" valign="top" style="">
            <div class="small_box">
                <div class="small_title"><strong>{$lang->ad_latest_events}</strong></div>
                <div id="actions_box">
                    <div id="actions">
                        {include file='cp/actions.tpl'}
                    </div>
                </div>
            </div>
        </td>
        <td width="325" valign="top" style="">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td height="100" valign="top">
                        {if $new_quests || $new_content || $new_catalog }
                            <div class="small_box">
                                <div class="small_title">
                                    <span class="attention">
                                        <strong>{$lang->ad_from_moderation}</strong>
                                    </span>
                                </div>
                                <div style="padding:10px">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="2" align="center">
                                        {if $new_content}
                                            <tr>
                                                <td width="16"><img src="images/updates/content.gif" width="16" height="16" /></td>
                                                <td><a href="/cp/components/content?orderby=pubdate&orderto=desc&only_hidden=1">{$lang->ad_articles}</a> ({$new_content})</td>
                                            </tr>
                                        {/if}
                                        {if $new_quests}
                                            <tr>
                                                <td width="16"><img src="images/updates/quests.gif" width="16" height="16" /></td>
                                                <td><a href="/cp/components/faq/list_items">{$lang->ad_questions}</a> ({$new_quests})</td>
                                            </tr>
                                        {/if}
                                        {if $new_catalog}
                                            <tr>
                                                <td width="16"><img src="images/updates/content.gif" width="16" height="16" /></td>
                                                <td><a href="/cp/components/catalog/list_items?on_moderate=1">{$lang->ad_catalog_items}</a> ({$new_catalog})</td>
                                            </tr>
                                        {/if}
                                    </table>
                                </div>
                            </div>
                        {/if}

                        {if $rssfeed}
                            <div class="small_box">
                                <div class="small_title">{$lang->ad_rss}</div>
                                <div style="padding:10px;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="2" align="center">
                                        <tr>
                                            <td width="16"><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                            <td><a href="/rss/comments/all/feed.rss" id="rss_link">{$lang->ad_rss_coment} </a></td>
                                            <td width="16"><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                            <td><a href="/rss/blogs/all/feed.rss" id="rss_link">{$lang->ad_rss_blogs}</a></td>
                                        </tr>
                                        <tr>
                                        <tr>
                                            {if $com_enabled.forum}
                                                <td width="16"><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                                <td><a href="/rss/forum/all/feed.rss" id="rss_link">{$lang->ad_rss_forum}</a></td>
                                            {else}
                                                <td></td>
                                                <td></td>
                                            {/if}
                                            {if $com_enabled.catalog}
                                                <td width="16"><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                                <td><a href="/rss/catalog/all/feed.rss" id="rss_link">{$lang->ad_rss_catalog}</a></td>
                                            {else}
                                                <td></td>
                                                <td></td>
                                            {/if}
                                        </tr>
                                        <tr>
                                            <td><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                            <td><a href="/rss/content/all/feed.rss" id="rss_link">{$lang->ad_rss_content}</a> </td>
                                            {if $com_enabled.board}
                                                <td><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                                <td><a href="/rss/board/all/feed.rss" id="rss_link">{$lang->ad_rss_adverts}</a> </td>
                                            {else}
                                                <td></td>
                                                <td></td>
                                            {/if}
                                        </tr>
                                        <tr>
                                            {if $content_counts.video !== false}
                                                <td><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                                <td><a href="/rss/video/all/feed.rss" id="rss_link">{$lang->ad_rss_video}</a> </td>
                                            {else}
                                                <td></td>
                                                <td></td>
                                            {/if}

                                            {if $content_counts.audio !== false}
                                                <td><img src="/images/markers/rssfeed.png" width="16" height="16" /></td>
                                                <td><a href="/rss/audio/artists/feed.rss" id="rss_link">{$lang->ad_rss_audio}</a> </td>
                                            {else}
                                                <td></td>
                                                <td></td>
                                            {/if}
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td><img src="/admin/images/icons/config.png" width="16" height="16" /></td>
                                            <td><a href="/cp/components/rssfeed/config" id="rss_link">{$lang->ad_rss_tuning}</a></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        {/if}

                        <div class="small_box">
                            <div class="small_title">{$lang->ad_icms_rave}</div>
                            <ul>
                                <li><a href="http://www.instantcms.ru/"><strong>{$lang->ad_icms_official}</strong></a></li>
                                <li><a href="http://www.instantcms.ru/wiki">{$lang->ad_icms_documentation}</a></li>
                                <li><a href="http://www.instantcms.ru/forum">{$lang->ad_icms_forum}</a></li>
                            </ul>
                        </div>
                        <div class="small_box">
                            <div class="small_title">{$lang->ad_premium}</div>
                            <div class="advert_iaudio"><a href="http://www.instantvideo.ru/software/iaudio.html"><strong>iAudio</strong></a> &mdash; {$lang->ad_audio_galery}</div>
                            <div class="advert_billing"><a href="http://www.instantcms.ru/billing/about.html"><strong>{$lang->ad_billing}</strong></a> &mdash; {$lang->ad_gain}</div>
                            <div class="advert_inmaps"><a href="http://www.instantmaps.ru/"><strong>InstantMaps</strong></a> &mdash; {$lang->ad_object_to_map}</div>
                            <div class="advert_inshop"><a href="http://www.instantcms.ru/blogs/InstantSoft/professionalnyi-magazin-dlja-InstantCMS.html"><strong>InstantShop</strong></a> &mdash; {$lang->ad_shop}</div>
                            <div class="advert_invideo"><a href="http://www.instantvideo.ru/software/instantvideo.html"><strong>InstantVideo</strong></a> &mdash; {$lang->ad_video_galery}></div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>