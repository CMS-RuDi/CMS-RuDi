<div id="hmenu">
    <ul id="nav">
        {if $menu_access}
            <li>
                <a href="/cp/menu" class="menu">{$lang->ad_menu}</a>
                <ul>
                    <li><a href="/cp/menu/add" class="add">{$lang->ad_menu_point_add}</a></li>
                    <li><a href="/cp/menu/addmenu" class="add">{$lang->ad_menu_add}</a></li>
                    <li><a href="/cp/menu" class="list">{$lang->ad_show_all}</a></li>
                </ul>
            </li>
        {/if}
        
        {if $modules_access}
            <li>
                <a href="/cp/modules" class="modules">{$lang->ad_modules}</a>
                <ul>
                    <li><a href="/cp/install/module" class="install">{$lang->ad_modules_setup}</a></li>
                    <li><a href="/cp/modules/add" class="add">{$lang->ad_module_add}</a></li>
                    <li><a href="/cp/modules" class="list">{$lang->ad_show_all}</a></li>
                </ul>
            </li>
        {/if}
        
        {if $content_access}
            <li>
                <a href="/cp/components/content" class="content">{$lang->ad_article_site}</a>
                <ul>
                    <li><a href="/cp/components/content" class="content">{$lang->ad_articles}</a></li>
                    <li><a href="/cp/components/arhive" class="arhive">{$lang->ad_articles_archive}</a></li>
                    <li><a href="/cp/components/content/add_category" class="add">{$lang->ad_create_section}</a></li>
                    <li><a href="/cp/components/content/add" class="add">{$lang->ad_create_article}</a></li>
                </ul>
            </li>
        {/if}
        
        {if $components_access}
            <li>
                <a href="/cp/components" class="components">{$lang->ad_components}</a>
                <ul>
                    <li><a href="/cp/install/component" class="install">{$lang->ad_install_components}</a></li>
                    {foreach from=$components item=component key=name }
                        <li>
                            <a href="/cp/components/{$name}" style="margin-left:5px; background:url(/admin/images/components/{$name}.png) no-repeat 6px 6px;">
                                {$component.title}
                            </a>
                        </li>
                    {/foreach}

                    <li><a href="/cp/components" class="list">{$lang->ad_show_all}...</a></li>
                </ul>
            </li>
        {/if}
        
        {if $plugins_access}
            <li>
                <a class="plugins">{$lang->ad_additions}</a>
                <ul>
                    <li><a href="/cp/install/plugin" class="install">{$lang->ad_install_plugins}</a></li>
                    <li><a href="/cp/plugins" class="plugins">{$lang->ad_plugins}</a></li>
                </ul>
            </li>
        {/if}
        
        {if $users_access}
            <li>
                <a href="/cp/components/users" class="users">{$lang->ad_users}</a>
                <ul>
                    <li><a href="/cp/components/users" class="user">{$lang->ad_users}</a></li>
                    <li><a href="/cp/components/users/banlist" class="banlist">{$lang->ad_banlist}</a></li>
                    <li><a href="/cp/components/users/groups" class="users">{$lang->ad_users_group}</a></li>
                    <li><a href="/cp/components/users/add" class="add">{$lang->ad_user_add}</a></li>
                    <li><a href="/cp/components/users/add_group" class="add">{$lang->ad_create_group}</a></li>
                    <li><a href="/cp/components/users/config" class="config">{$lang->ad_profile_settings}</a></li>
                </ul>
            </li>
        {/if}
        
        {if $config_access}
            <li>
                <a href="/cp/config" class="config">{$lang->ad_settings}</a>
                <ul>
                    <li><a href="/cp/config" class="config">{$lang->ad_site_setting}</a></li>
                    <li><a href="/cp/repairnested" class="repairnested">{$lang->ad_checking_trees}</a></li>
                    <li><a href="/cp/cron" class="cron">{$lang->ad_cron_mission}</a></li>
                    <li><a href="/cp/phpinfo" class="phpinfo">{$lang->ad_php_info}</a></li>
                    <li><a href="/cp/cache/clear" class="clearcache">{$lang->ad_clear_sys_cache}</a></li>
                </ul>
            </li>
        {/if}
        
        <li>
            <a href="http://www.instantcms.ru/wiki" target="_blank" class="help">{$lang->ad_docs}</a>
        </li>
    </ul>
</div>