{*
 * Debug
 *
 * Debug log template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2013 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}
<table class"debug">
<tbody>
{foreach from=$debuglog->GetEntries() item=entry}
  <tr>
    <td class="debug_key">
      {$entry.name|escape}
    </td>
    <td class="debug_value">
      {if $entry.value}
        {if strlen($entry.value) > 512}
          {$entry.value|truncate:512:'...'|escape}
          <br />
          <span class="debug_addl">{strlen($entry.value)-512} bytes more in output</span>
        {else}
          {$entry.value|escape}
        {/if}
        <br />
      {/if}
      <span class="debug_toggle">trace</span>
      <div class="debug_bt">{$entry.bt|escape}</div>
    </td>
    <td class="debug_time">
      {if $entry.time}
        {$entry.time*1000|string_format:"%.1f"} {if $entry.reltime}ms from start{else}ms{/if}
      {/if}
    </td>
  </tr>
{/foreach}
</tbody>
</table>
