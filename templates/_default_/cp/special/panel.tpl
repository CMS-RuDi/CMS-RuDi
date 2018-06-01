<table width="100%" border="0" cellspacing="0" cellpadding="8" class="proptable">
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                <tr>
                    <td width="120">
                        <strong>{$lang->ad_insert}:</strong>
                    </td>
                    <td width="">
                        <select name="ins" id="ins" style="width:99%" onChange="showIns();">
                            <option value="frm" selected="selected">{$lang->ad_form}</option>
                            <option value="include">{$lang->file}</option>
                            <option value="filelink">{$lang->ad_link_download_file}</option>
                            {if $banners_enabled}
                                <option value="banpos">{$lang->ad_banner_position}</option>
                            {/if}
                            <option value="pagebreak">-- {$lang->ad_pagebreak} --</option>
                            <option value="pagetitle">-- {$lang->ad_pagetitle} --</option>
                      </select>
                    </td>
                    <td width="100">&nbsp;</td>
                </tr>
                <tr id="frm">
                    <td width="120">
                        <strong>{$lang->ad_form}:</strong>
                    </td>
                    <td>
                        <select name="fm" style="width:99%">{$forms_list}</select>
                    </td>
                    <td width="100">
                        <input type="button" value="{$lang->ad_insert}" style="width:100px" onClick="insertTag(document.addform.ins.options[document.addform.ins.selectedIndex].value);" />
                    </td>
                </tr>
                <tr id="include">
                    <td width="120">
                        <strong>{$lang->file}:</strong>
                    </td>
                    <td>
                        /includes/myphp/<input name="i" type="text" value="myscript.php" />
                    </td>
                    <td width="100">
                        <input type="button" value="{$lang->ad_insert}" style="width:100px" onClick="insertTag(document.addform.ins.options[document.addform.ins.selectedIndex].value);" />
                    </td>
                </tr>
                <tr id="filelink">
                    <td width="120">
                        <strong>{$lang->file}:</strong>
                    </td>
                    <td>
                        <input name="fl" type="text" value="/files/myfile.rar" />
                    </td>
                    <td width="100">
                        <input type="button" value="{$lang->ad_insert}" style="width:100px" onClick="insertTag(document.addform.ins.options[document.addform.ins.selectedIndex].value);" />
                    </td>
                </tr>
                {if $banners_enabled}
                    <tr id="banpos">
                        <td width="120">
                            <strong>{$lang->ad_position}:</strong>
                        </td>
                        <td>
                            <select name="ban" style="width:99%">{$banners_list}</select>
                        </td>
                        <td width="100">
                            <input type="button" value="{$lang->ad_insert}" style="width:100px" onClick="insertTag(document.addform.ins.options[document.addform.ins.selectedIndex].value);" />
                        </td>
                    </tr>
                {/if}
                <tr id="pagebreak">
                    <td width="120">
                        <strong>{$lang->tag}:</strong>
                    </td>
                    <td>
                        {literal}{pagebreak}{/literal}
                    </td>
                    <td width="100">
                        <input type="button" value="{$lang->ad_insert}" style="width:100px" onClick="insertTag(document.addform.ins.options[document.addform.ins.selectedIndex].value);" />
                    </td>
                </tr>
                <tr id="pagetitle">
                    <td width="120">
                        <strong>{$lang->ad_title}:</strong>
                    </td>
                    <td>
                        <input type="text" name="ptitle" style="width:99%" />
                    </td>
                    <td width="100">
                        <input type="button" value="{$lang->ad_insert}" style="width:100px" onClick="insertTag(document.addform.ins.options[document.addform.ins.selectedIndex].value);" />
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<script type="text/javascript">showIns();</script>