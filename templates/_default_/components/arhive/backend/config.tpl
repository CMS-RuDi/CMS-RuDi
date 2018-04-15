<form action="{$submit_uri}" method="post" name="optform" target="_self" id="form1">
    <input type="hidden" name="csrf_token" value="{csrf_token}" />

    <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td valign="top">
                <strong>{$lang->ad_source_materials}</strong>
            </td>
            <td width="100" valign="top">
                <select name="source" id="source" style="width:285px">
                    <option value="content"{if $options.source == 'content'} selected="selected"{/if}>{$lang->ad_article_site}</option>
                    <option value="arhive"{if $options.source == 'arhive'} selected="selected"{/if}>{$lang->ad_articles_archive}</option>
                    <option value="both"{if $options.source == 'both'} selected="selected"{/if}>{$lang->ad_catalog_and_archive}</option>
                </select>
            </td>
        </tr>
    </table>

    <p>
        <input name="save" type="submit" id="save" value="{$lang->save}" />
        <input name="back" type="button" id="back" value="{$lang->cancel}" onclick="window.location.href = '{$base_uri}';" />
    </p>
</form>