{*
 *  search.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search view template
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

{include file='title.tpl' titlecommit=$commit}

{if $results}
<table>
  {* Print each match *}
  {foreach from=$results item=result}
    <tr class="{cycle values="light,dark"}">
      <td title="{if $result->GetAge() > 60*60*24*7*2}{agestring age=$result->GetAge()}{else}{$result->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em><time datetime="{$result->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{if $result->GetAge() > 60*60*24*7*2}{$result->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{agestring age=$result->GetAge()}{/if}</time></em></td>
      <td>
        <em>
	  {if $searchtype == 'author'}
	    {$result->GetAuthorName()|escape|highlight:$search}
	  {elseif $searchtype == 'committer'}
	    {$result->GetCommitterName()|escape|highlight:$search}
	  {else}
	    {$result->GetAuthorName()|escape}
	  {/if}
        </em>
      </td>
      <td><a href="{geturl project=$project action=commit hash=$result}" class="list commitTip" {if strlen($result->GetTitle()) > 50}title="{$result->GetTitle()|escape}"{/if}><strong>{$result->GetTitle(50)|escape:'html'}</strong></a>
      {if $searchtype == 'commit'}
        {foreach from=$result->SearchComment($search) item=line name=match}
          <br />{$line|escape|highlight:$search:50}
        {/foreach}
      {/if}
      </td>
      {assign var=resulttree value=$result->GetTree()}
      <td class="link"><a href="{geturl project=$project action=commit hash=$result}">{t}commit{/t}</a> | <a href="{geturl project=$project action=commitdiff hash=$result}">{t}commitdiff{/t}</a> | <a href="{geturl project=$project action=tree hash=$resulttree hashbase=$result}">{t}tree{/t}</a> | <a href="{geturl project=$project action=snapshot hash=$result}" class="snapshotTip">{t}snapshot{/t}</a>
      </td>
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{geturl project=$project action=search hash=$commit search=$search searchtype=$searchtype page=$page+1}" title="Alt-n">{t}next{/t}</a></td>
      <td></td>
      <td></td>
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
