{*
 *  history.tpl
 *  gitphp: A PHP git repository browser
 *  Component: History view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=links append}
{if $page > 0}
<link rel="prev" href="{geturl project=$project action=history hash=$commit file=$blob->GetPath() page=$page-1}" />
{/if}
{if $hasmorehistory}
<link rel="next" href="{geturl project=$project action=history hash=$commit file=$blob->GetPath() page=$page+1}" />
{/if}
{/block}

{block name=main}

 {* Page header *}
 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br />
   {if $page > 0}
     <a href="{geturl project=$project action=history hash=$commit file=$blob->GetPath()}">{t}first{/t}</a>
   {else}
     {t}first{/t}
   {/if}
   &sdot;
   {if $page > 0}
     <a href="{geturl project=$project action=history hash=$commit file=$blob->GetPath() page=$page-1}">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
   &sdot;
   {if $hasmorehistory}
     <a href="{geturl project=$project action=history hash=$commit file=$blob->GetPath() page=$page+1}">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blob target='blob'}
 
 <table>
   {* Display each history line *}
   {foreach from=$history item=historyitem}
     {assign var=historycommit value=$historyitem->GetCommit()}
     <tr class="{cycle values="light,dark"}">
       <td title="{if $historycommit->GetAge() > 60*60*24*7*2}{agestring age=$historycommit->GetAge()}{else}{$historycommit->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em><time datetime="{$historycommit->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{if $historycommit->GetAge() > 60*60*24*7*2}{$historycommit->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{agestring age=$historycommit->GetAge()}{/if}</time></em></td>
       <td><em>{$historycommit->GetAuthorName()}</em></td>
       <td><a href="{geturl project=$project action=commit hash=$historycommit}" class="list commitTip" {if strlen($historycommit->GetTitle()) > 50}title="{$historycommit->GetTitle()|escape}"{/if}><strong>{$historycommit->GetTitle(50)|escape:'html'}</strong></a>
       {include file='refbadges.tpl' commit=$historycommit}
       </td>
       <td class="link"><a href="{geturl project=$project action=commit hash=$historycommit}">{t}commit{/t}</a> | <a href="{geturl project=$project action=commitdiff hash=$historycommit}">{t}commitdiff{/t}</a> | <a href="{geturl project=$project action=blob hashbase=$historycommit file=$blob->GetPath()}">{t}blob{/t}</a> | <a href="{geturl project=$project action=blobdiff hash=$historyitem->GetToBlob() hashparent=$historyitem->GetFromBlob() file=$blob->GetPath() hashbase=$historycommit}">{t}diff{/t}</a>{if $blob->GetHash() != $historyitem->GetToHash()} | <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$historyitem->GetToBlob() file=$blob->GetPath() hashbase=$historycommit}">{t}diff to current{/t}</a>{/if}
       </td>
     </tr>
   {/foreach}
   {if $hasmorehistory}
     <tr>
       <td><a href="{geturl project=$project action=history hash=$commit file=$blob->GetPath() page=$page+1}">{t}next{/t}</a></td>
       <td></td><td></td><td></td>
     </tr>
   {/if}
 </table>

{/block}
