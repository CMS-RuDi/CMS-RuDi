<div class="proptable" style="padding:15px;">
    <p><b>{$lang->p_filters_enable}</b></p>
    
    {foreach from=$filters item=filter}
        <div>
            <label>
                <input type="checkbox" name="config[{$filter}]" value="1" {if isset($config.$filter)}checked="checked"{/if} />
                <strong>{$lang->e($filter)} ({$filter})</strong>
            </label>
        </div>
    {/foreach}
</div>

<div style="margin-top:6px;">
    <input type="submit" name="save" value="{$lang->save}" />
    <input type="button" name="back" value="{$lang->cancel}" onclick="window.history.go(-1);" />
</div>