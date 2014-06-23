{*
 *  log.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' current='log' logcommit=$commit treecommit=$commit logmark=$mark}
   {assign var=wraptext value=30}
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
     <a href="{geturl project=$project action=commit hash=$mark}" class="list commitTip" {if strlen($mark->GetTitle()) > $wraptext}title="{$mark->GetTitle()|escape}"{/if}><strong>{$mark->GetTitle($wraptext)|escape:'html'}</strong></a>
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
       <a href="{geturl project=$project action=commit hash=$rev}">{t}commit{/t}</a>
     | <a href="{geturl project=$project action=commitdiff hash=$rev}">{t}commitdiff{/t}</a>
     | <a href="{geturl project=$project action=tree hash=$revtree hashbase=$rev}">{t}tree{/t}</a>
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
     <em>{$rev->GetAuthorName()} [{$rev->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S"} {date('O')}] ({$rev->GetAuthorLocalEpoch()|date_format:"%d %b %H:%M:%S"} {$rev->GetAuthorTimezone()})</em><br />
     {if $rev->GetAuthorEpoch() != $rev->GetCommitterEpoch()}
     <em>{$rev->GetCommitterName()} [{$rev->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S"} {date('O')}] ({$rev->GetCommitterLocalEpoch()|date_format:"%d %b %H:%M:%S"} {$rev->GetCommitterTimezone()})</em>
     {/if}
   </div>
   <div class="log_body">
   {assign var=comment value=$rev->GetComment()}
   {if end($comment) != $rev->GetTitle()}
     {assign var=bugpattern value=$project->GetBugPattern()}
     {assign var=bugurl value=$project->GetBugUrl()}
     {foreach from=$comment item=line}
       {if strstr(trim($line),'-by: ') || strstr(trim($line),'Cc: ')}
       <span class="signedOffBy">{$line|htmlspecialchars|buglink:$bugpattern:$bugurl}</span>
       {elseif preg_match('~http(s)?:~',$line)}
       <span class="signedOffBy commentLink">{$line|buglink:'/(http(s)?:\/\/)(.)*[\.](.)*$/':"\$0"}</span>
       {elseif strncasecmp(trim($line),'Change-Id:',10) == 0}
       <span class="changeId">{$line|buglink:$bugpattern:$bugurl}</span>
       {else}
       {$line|htmlspecialchars|commithash|buglink:$bugpattern:$bugurl}
       {/if}
       <br />
     {/foreach}
     {if count($rev->GetComment()) > 0}
       <br />
     {/if}
   {/if}
   </div>
 {foreachelse}
   <div class="title">
     <a href="{geturl project=$project}" class="title">&nbsp</a>
   </div>
   <div class="page_body">
     {if $commit}
       {assign var="commitage" value="{agestring age=$commit->GetAge()}"}
       {t 1=$commitage}Last change %1{/t}
     {else}
     <em>{t}No commits{/t}</em>
     {/if}
     <br /><br />
   </div>
 {/foreach}

{/block}
