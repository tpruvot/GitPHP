{*
 *  shortlog.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {assign var="baseurl"
         value="{$SCRIPT_NAME}?p={$project->GetProject('f')}"
   }
   {include file='nav.tpl' current='shortlog' logcommit=$commit treecommit=$commit logmark=$mark}
   <br />
   {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
     <a href="{$baseurl}&amp;a=shortlog{if $mark}&amp;m={$mark->GetHash()}{/if}">{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
   {/if}
     &sdot; 
   {if $page > 0 && $commit}
     <a href="{$baseurl}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page-1}{if $mark}&amp;m={$mark->GetHash()}{/if}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
     &sdot; 
   {if $hasmorerevs && $commit}
     <a href="{$baseurl}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()}{/if}" accesskey="n" title="Alt-n">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
   <br />
   {if $mark}
     {t}selected{/t} &sdot;
     <a href="{$baseurl}&amp;a=commit&amp;h={$mark->GetHash()}" class="list commitTip" {if strlen($mark->GetTitle()) > 80}title="{$mark->GetTitle()|escape}"{/if}><strong>{$mark->GetTitle(80)|escape:'html'}</strong></a>
     {if $commit}
     &sdot;
     <a href="{$baseurl}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}deselect{/t}</a>
     {/if}
     <br />
   {/if}
 </div>

 {if $commit}
 {include file='title.tpl' target='summary' titlecommit=$commit}
 {/if}

 {include file='shortloglist.tpl' source='shortlog'}

{/block}
