{*
 * Shortlog List
 *
 * Shortlog list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table>
   {foreach from=$revlist item=rev}
     <tr class="{cycle values="light,dark"}">
       <td class="monospace">{$rev->GetHash(true)}</td>
       <td title="{if $rev->GetAge() > 60*60*24*7*2}{agestring age=$rev->GetAge()}{else}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em><time datetime="{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{agestring age=$rev->GetAge()}{/if}</time></em></td>
       <td><em>{$rev->GetAuthorName()}</em></td>
       <td>
         <a href="{geturl project=$project action=commit hash=$rev}" class="list commitTip" {if strlen($rev->GetTitle()) > 50}title="{$rev->GetTitle()|escape}"{/if}>
         {if $rev->IsMergeCommit()}<span class="merge_title">{else}<span class="commit_title">{/if}{$rev->GetTitle(50)|escape}</span>
         </a>
	 {include file='refbadges.tpl' commit=$rev}
       </td>
       <td class="link">
         {assign var=revtree value=$rev->GetTree()}
         <a href="{geturl project=$project action=commit hash=$rev}">{t}commit{/t}</a> | <a href="{geturl project=$project action=commitdiff hash=$rev}">{t}commitdiff{/t}</a> | <a href="{geturl project=$project action=tree hash=$revtree hashbase=$rev}">{t}tree{/t}</a> | <a href="{geturl project=$project action=snapshot hash=$rev}" class="snapshotTip">{t}snapshot{/t}</a>
	 {if $source == 'shortlog'}
	  | 
	  {if $mark}
	    {if $mark->GetHash() == $rev->GetHash()}
	      <a href="{geturl project=$project action=shortlog hash=$commit page=$page}">{t}deselect{/t}</a>
	    {else}
	      {if $mark->GetCommitterEpoch() > $rev->GetCommitterEpoch()}
	        {assign var=markbase value=$mark}
		{assign var=markparent value=$rev}
	      {else}
	        {assign var=markbase value=$rev}
		{assign var=markparent value=$mark}
	      {/if}
	      <a href="{geturl project=$project action=commitdiff hash=$markbase hashparent=$markparent}">{t}diff with selected{/t}</a>
	    {/if}
	  {else}
	    <a href="{geturl project=$project action=shortlog hash=$commit page=$page mark=$rev}">{t}select for diff{/t}</a>
	  {/if}
	{/if}
       </td>
     </tr>
   {foreachelse}
     <tr><td><em>{t}No commits{/t}</em></td></tr>
   {/foreach}

   {if $hasmorerevs}
     <tr>
     {if $source == 'summary'}
       <td><a href="{geturl project=$project action=shortlog}">&hellip;</a></td><td></td><td></td><td></td><td></td>
     {else if $source == 'shortlog'}
       <td><a href="{geturl project=$project action=shortlog hash=$commit page=$page+1 mark=$mark}" title="Alt-n">{t}next{/t}</a></td><td></td><td></td><td></td><td></td>
     {/if}
     </tr>
   {/if}
 </table>

