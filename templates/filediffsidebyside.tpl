{*
 * filediffsidebyside
 *
 * File diff with side-by-side changes template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @author Mattias Ulbrich
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
<table class="diffTable">
  {if $filediff->GetStatus() == 'D'}
    {assign var=delblob value=$filediff->GetFromBlob()}
    {foreach from=$delblob->GetData(true) item=blobline}
      <tr class="{cycle values="light-codedel,dark-codedel"} diff-deleted">
        <td class="diff-left">{$blobline|escape}</td>
        <td class="diff-right">&nbsp;</td>
      </tr>
    {/foreach}
  {elseif $filediff->GetStatus() == 'A'}
    {assign var=newblob value=$filediff->GetToBlob()}
    {foreach from=$newblob->GetData(true) item=blobline}
      <tr class="{cycle values="light-codeadd,dark-codeadd"} diff-added">
        <td class="diff-left">&nbsp;</td>
        <td class="diff-right">{$blobline|escape}</td>
      </tr>
    {/foreach}
  {else}
    {foreach from=$diffsplit item=lineinfo}
      {if $lineinfo[0]=='added'}
      <tr class="{cycle values="light-codeadd,dark-codeadd"} diff-added">
      {elseif $lineinfo[0]=='deleted'}
      <tr class="{cycle values="light-codedel,dark-codedel"} diff-deleted">
      {elseif $lineinfo[0]=='modified'}
      <tr class="{cycle values="light-codemod,dark-codemod"} diff-modified">
      {else}
      <tr class="{cycle values="light-code,dark-code"}">
      {/if}
        <td class="diff-left">{if $lineinfo[1]}{$lineinfo[1]|escape}{else}&nbsp;{/if}</td>
        <td class="diff-right">{if $lineinfo[2]}{$lineinfo[2]|escape}{else}&nbsp;{/if}</td>
      </tr>
    {/foreach}
  {/if}
</table>
