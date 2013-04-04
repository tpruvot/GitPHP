{*
 *  searchfiles.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search files template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=links append}
{if $page > 0}
<link rel="prev" href="{geturl project=$project action=search hash=$commit search=$search searchtype=$searchtype page=$page-1}" />
{/if}
{if $hasmore}
<link rel="next" href="{geturl project=$project action=search hash=$commit search=$search searchtype=$searchtype page=$page+1}" />
{/if}
{/block}

{block name=main}

{* Nav *}
<div class="page_nav">
  {include file='nav.tpl' logcommit=$commit treecommit=$commit}
  <br />
  {if $page > 0}
    <a href="{geturl project=$project action=search hash=$commit search=$search searchtype=$searchtype}">{t}first{/t}</a>
  {else}
    {t}first{/t}
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{geturl project=$project action=search hash=$commit search=$search searchtype=$searchtype page=$page-1}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
  {else}
    {t}prev{/t}
  {/if}
    &sdot; 
  {if $hasmore}
    <a href="{geturl project=$project action=search hash=$commit search=$search searchtype=$searchtype page=$page+1}" accesskey="n" title="Alt-n">{t}next{/t}</a>
  {else}
    {t}next{/t}
  {/if}
  <br />
</div>
<div class="title">
  <a href="{geturl project=$project action=commit hash=$commit}" class="title">{$commit->GetTitle()|escape:'html'}</a>
</div>
{if $results}
<table>
  {* Print each match *}
  {foreach from=$results item=result key=path}
    <tr class="{cycle values="light,dark"}">
      {assign var=resultobject value=$result.object}
      {assign var=resultpath value=$resultobject->GetPath()}
      {assign var=wrap value=160}
      {if $resultobject instanceof GitPHP_Tree}
      <td>
        <a href="{geturl project=$project action=tree hash=$resultobject hashbase=$commit file=$resultpath}" class="list"><strong>{$resultpath|highlight:$search}</strong></a>
      </td>
      <td class="link">
        <a href="{geturl project=$project action=tree hash=$resultobject hashbase=$commit file=$resultpath}">{t}tree{/t}</a>
      </td>
      {else}
      <td>
        <a href="{geturl project=$project action=blob hash=$resultobject hashbase=$commit file=$resultpath}" class="list"><strong>{$resultpath|highlight:$search}</strong></a>
        {foreach from=$result.lines item=line name=match key=lineno}
          {if $smarty.foreach.match.first}<br />{/if}<span class="matchline">{$lineno}. {$line|highlight:$search:$wrap:true}</span><br />
        {/foreach}
      </td>
      <td class="link">
        <a href="{geturl project=$project action=blob hash=$resultobject hashbase=$commit file=$resultpath}">{t}blob{/t}</a> | <a href="{geturl project=$project action=history hash=$commit file=$resultpath}">{t}history{/t}</a>
      </td>
      {/if}
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{geturl project=$project action=search hash=$commit search=$search searchtype=$searchtype page=$page+1}" title="Alt-n">{t}next{/t}</a></td>
      <td></td>
    </tr>
  {/if}
</table>
{else}
<div class="message">
{t 1=$search}No matches for "%1"{/t}
</div>
{/if}

{/block}
