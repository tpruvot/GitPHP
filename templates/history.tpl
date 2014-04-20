{*
 *  history.tpl
 *  gitphp: A PHP git repository browser
 *  Component: History view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

{if $foldertree}
  {assign var="f" value=$foldertree->GetPath()}
{else}
  {assign var="f" value=$blob->GetPath()}
{/if}

 {* Page header *}
 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br/>
   {if $page > 0}
     <a href="{geturl project=$project action=history hash=$commit file=$f}">{t}first{/t}</a>
   {else}
     {t}first{/t}
   {/if}
   &sdot;
   {if $page > 0}
     <a href="{geturl project=$project action=history hash=$commit file=$f page=$page-1}">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
   &sdot;
   {if $hasmorehistory}
     <a href="{geturl project=$project action=history hash=$commit file=$f page=$page+1}">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {if $foldertree}
   {include file='path.tpl' pathobject=$foldertree target='tree'}
 {else}
   {include file='path.tpl' pathobject=$blob target='blob'}
 {/if}
 
 {if $blob || $foldertree}
 {assign var=wraptext value=80}
 <table>
   {* Display each history line *}
   {foreach from=$history item=historyitem}
     {assign var=historycommit value=$historyitem->GetCommit()}
     <tr class="{cycle values="light,dark"}">
       <td title="{if $historycommit->GetAge() > 60*60*24*7*2}{agestring age=$historycommit->GetAge()}{else}{$historycommit->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}">
         <em>{if $historycommit->GetAge() > 60*60*24*7*2}{$historycommit->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{agestring age=$historycommit->GetAge()}{/if}</em>
       </td>
       <td><em>{$historycommit->GetAuthorName()}</em></td>
       <td>
         <a href="{geturl project=$project action=commit hash=$historycommit}" class="list commitTip" {if strlen($historycommit->GetTitle()) > $wraptext}title="{$historycommit->GetTitle()|escape}"{/if}>
           <strong>{$historycommit->GetTitle($wraptext)|escape:'html'}</strong>
         </a>
         {include file='refbadges.tpl' commit=$historycommit}
       </td>
       <td class="link">
         <a href="{geturl project=$project action=commit hash=$historycommit}">{t}commit{/t}</a>
       | <a href="{geturl project=$project action=commitdiff hash=$historycommit}">{t}commitdiff{/t}</a>
     {if !$foldertree}
       | <a href="{geturl project=$project action=blob hashbase=$historycommit file=$f}">{t}blob{/t}</a>
       | <a href="{geturl project=$project action=blobdiff hash=$historyitem->GetToBlob() hashparent=$historyitem->GetFromBlob() file=$f hashbase=$historycommit}">{t}blobdiff{/t}</a>
       {if $blob->GetHash() != $historyitem->GetToHash()}
       | <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$historyitem->GetToBlob() file=$f hashbase=$historycommit}#D1">{t}diff to current{/t}</a>
       {/if}
     {else}
       | <a href="{geturl project=$project action=commitdiff hash=$commit hashparent=$historycommit file=$f}">{t}diff to current{/t}</a>
     {/if}
       </td>
     </tr>
   {/foreach}
   {if $hasmorehistory}
     <tr>
       <td><a href="{geturl project=$project action=history hash=$commit file=$f page=$page+1}">{t}next{/t}</a></td>
       <td></td><td></td><td></td>
     </tr>
   {/if}
 </table>
 {/if}

{/block}
