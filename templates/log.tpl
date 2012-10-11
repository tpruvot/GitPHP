{*
 *  log.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=links append}
{if $page > 0}
<link rel="prev" href="{geturl project=$project action=log hash=$commit page=$page-1 mark=$mark}" />
{/if}
{if $hasmorerevs}
<link rel="next" href="{geturl project=$project action=log hash=$commit page=$page+1 mark=$mark}" />
{/if}
{/block}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' current='log' logcommit=$commit treecommit=$commit logmark=$mark}
   <br />
   {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
     <a href="{geturl project=$project action=log mark=$mark}">{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
   {/if}
   &sdot; 
   {if $page > 0}
     <a href="{geturl project=$project action=log hash=$commit page=$page-1 mark=$mark}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
   &sdot; 
   {if $hasmorerevs}
     <a href="{geturl project=$project action=log hash=$commit page=$page+1 mark=$mark}" accesskey="n" title="Alt-n">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
   <br />
   {if $mark}
     {t}selected{/t} &sdot;
     <a href="{geturl project=$project action=commit hash=$mark}" class="list commitTip" {if strlen($mark->GetTitle()) > 30}title="{$mark->GetTitle()|escape}"{/if}><strong>{$mark->GetTitle(30)|escape:'html'}</strong></a>
     &sdot;
     <a href="{geturl project=$project action=log hash=$commit page=$page}">{t}deselect{/t}</a>
     <br />
   {/if}
 </div>
 {foreach from=$revlist item=rev}
   <div class="title">
     <a href="{geturl project=$project action=commit hash=$rev}" class="title"><span class="age">{agestring age=$rev->GetAge()}</span>{$rev->GetTitle()|escape:'html'}</a>
     {include file='refbadges.tpl' commit=$rev}
   </div>
   <div class="title_text">
     <div class="log_link">
       {assign var=revtree value=$rev->GetTree()}
       <a href="{geturl project=$project action=commit hash=$rev}">{t}commit{/t}</a> | <a href="{geturl project=$project action=commitdiff hash=$rev}">{t}commitdiff{/t}</a> | <a href="{geturl project=$project action=tree hash=$revtree hashbase=$rev}">{t}tree{/t}</a>
       <br />
       {if $mark}
         {if $mark->GetHash() == $rev->GetHash()}
	   <a href="{geturl project=$project action=log hash=$commit page=$page}">{t}deselect{/t}</a>
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
         <a href="{geturl project=$project action=log hash=$commit page=$page mark=$rev}">{t}select for diff{/t}</a>
       {/if}
       <br />
     </div>
     <em>{$rev->GetAuthorName()} [<time datetime="{$rev->GetAuthorEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{$rev->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}</time>]</em><br />
   </div>
   <div class="log_body">
     {assign var=bugpattern value=$project->GetBugPattern()}
     {assign var=bugurl value=$project->GetBugUrl()}
     {foreach from=$rev->GetComment() item=line}
       {if strncasecmp(trim($line),'Signed-off-by:',14) == 0}
       <span class="signedOffBy">{$line|htmlspecialchars|buglink:$bugpattern:$bugurl}</span>
       {else}
       {$line|htmlspecialchars|buglink:$bugpattern:$bugurl}
       {/if}
       <br />
     {/foreach}
     {if count($rev->GetComment()) > 0}
       <br />
     {/if}
   </div>
 {foreachelse}
   <div class="title">
     <a href="{geturl project=$project}" class="title">&nbsp</a>
   </div>
   <div class="page_body">
     {if $commit}
       {capture name=commitage assign=commitage}
         <time datetime="{$commit->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{agestring age=$commit->GetAge()}</time>
       {/capture}
       {t 1=$commitage}Last change %1{/t}
     {else}
     <em>{t}No commits{/t}</em>
     {/if}
     <br /><br />
   </div>
 {/foreach}

{/block}
