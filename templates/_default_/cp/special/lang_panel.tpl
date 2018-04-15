{foreach from=$langs item=lang}
    &nbsp;<a class="ajaxlink editfieldlang" href="#" onclick="return editFieldLang('{$lang}', '{$target}', '{$target_id}', '{$field}', this);"><strong>{$lang|upper}</strong></a>&nbsp;
{/foreach}