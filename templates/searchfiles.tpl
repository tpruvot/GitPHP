{*
 *  searchfiles.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search files template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

{* Nav *}
<div class="page_nav">
  {include file='nav.tpl' logcommit=$commit treecommit=$commit}
  <br />
  {if $page > 0}
    <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}">{t}first{/t}</a>
  {else}
    {t}first{/t}
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}{if $page > 1}&amp;pg={$page-1}{/if}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
  {else}
    {t}prev{/t}
  {/if}
    &sdot; 
  {if $hasmore}
    <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" accesskey="n" title="Alt-n">{t}next{/t}</a>
  {else}
    {t}next{/t}
  {/if}
  <br />
</div>
<div class="title">
  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=commit&amp;h={$commit->GetHash()}" class="title">{$commit->GetTitle()|escape:'html'}</a>
</div>

{if $results}
<table>
  {* Print each match *}
  {foreach from=$results item=result}
    <tr class="{cycle values="light,dark"}">
      {assign var=resultobject value=$result->GetObject()}
      {if $resultobject instanceof GitPHP_Tree}
	      <td>
		  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=tree&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$result->GetPath()}" class="list"><strong>{$result->GetPath()|highlight:$search}</strong></a>
	      </td>
	      <td class="link">
		  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=tree&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$result->GetPath()}">{t}tree{/t}</a>
	      </td>
      {else}
	      <td>
		  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=blob&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$result->GetPath()}" class="list"><strong>{$result->GetPath()|highlight:$search}</strong></a>
		  {foreach from=$result->GetMatchingLines() item=line name=match key=lineno}
		    {if $smarty.foreach.match.first}<br />{/if}<span class="matchline">{$lineno}. {$line|highlight:$search:50:true}</span><br />
		  {/foreach}
	      </td>
	      <td class="link">
		  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=blob&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$result->GetPath()}">{t}blob{/t}</a> | <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$result->GetPath()}">{t}history{/t}</a>
	      </td>
      {/if}
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" title="Alt-n">{t}next{/t}</a></td>
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
