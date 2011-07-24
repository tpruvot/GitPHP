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
<div class="diffTable scrollPanel">
<table class="diffTable">
  {if $filediff->GetStatus() == 'D'}
    {assign var=delblob value=$filediff->GetFromBlob()}
    {foreach from=$delblob->GetData(true) item=blobline key=line}
      <tr class="{cycle values="light-codedel,dark-codedel"} diff-deleted diff-focus">
        <td class="ln left" width="10px">{$line}</td>
        <td class="diff-left"><div class="span">{$blobline|escape}</span></td>
        <td class="diff-right"></td>
        <td class="ln right" width="10px"></td>
      </tr>
    {/foreach}
  {elseif $filediff->GetStatus() == 'A'}
    {assign var=newblob value=$filediff->GetToBlob()}
    {foreach from=$newblob->GetData(true) item=blobline key=line}
      <tr class="{cycle values="light-codeadd,dark-codeadd"} diff-added diff-focus">
        <td class="ln left">&nbsp;</td>
        <td class="diff-left"></td>
        <td class="diff-right"><span class="line">{$blobline|escape}</span></td>
        <td class="ln right">{$line}</td>
      </tr>
    {/foreach}
  {else}
    {foreach from=$diffsplit item=lineinfo}
    {assign var=lnl value=$lineinfo[3]}
    {assign var=lnr value=$lineinfo[4]}
      {if $lineinfo[0]=='added'}
      <tr class="{cycle values="light-codeadd,dark-codeadd"} diff-added diff-focus">
      {elseif $lineinfo[0]=='deleted'}
      <tr class="{cycle values="light-codedel,dark-codedel"} diff-deleted diff-focus">
      {elseif $lineinfo[0]=='modified'}
        {if !$lineinfo[3]}
      <tr class="{cycle values="light-codeadd,dark-codeadd"} diff-modified diff-added diff-focus">
        {elseif !$lineinfo[4]}
      <tr class="{cycle values="light-codedel,dark-codedel"} diff-modified diff-deleted diff-focus">
        {else}
      <tr class="{cycle values="light-codemod,dark-codemod"} diff-modified diff-focus">
        {/if}
      {else}
      <tr class="{cycle values="light-code,dark-code"}">
      {/if}
        <td class="ln left"   >{if $lnl}<a name="L{$lnl}">{$lnl}</a>{else}&nbsp;{/if}</td>
        <td class="diff-left" >{if $lnl}<span class="line">{$lineinfo[1]|escape}</span>{/if}</td>
        <td class="diff-right">{if $lnr}<span class="line">{$lineinfo[2]|escape}</span>{/if}</td>
        <td class="ln right"  >{if $lnr}<a name="R{$lnr}">{$lnr}</a>{/if}</td>
      </tr>
    {/foreach}
  {/if}
</table>
</div>
