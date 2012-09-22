{*
 *  shortlog.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=links append}
{if $page > 0}
<link rel="prev" href="{geturl project=$project action=shortlog hash=$commit page=$page-1 mark=$mark}" />
{/if}
{if $hasmorerevs}
<link rel="next" href="{geturl project=$project action=shortlog hash=$commit page=$page+1 mark=$mark}" />
{/if}
{/block}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' current='shortlog' logcommit=$commit treecommit=$commit logmark=$mark}
   <br />
   {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
     <a href="{geturl project=$project action=shortlog mark=$mark}">{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
   {/if}
     &sdot; 
   {if $page > 0}
     <a href="{geturl project=$project action=shortlog hash=$commit page=$page-1 mark=$mark}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
     &sdot; 
   {if $hasmorerevs}
     <a href="{geturl project=$project action=shortlog hash=$commit page=$page+1 mark=$mark}" accesskey="n" title="Alt-n">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
   <br />
   {if $mark}
     {t}selected{/t} &sdot;
     <a href="{geturl project=$project action=commit hash=$mark}" class="list commitTip" {if strlen($mark->GetTitle()) > 30}title="{$mark->GetTitle()|escape}"{/if}><strong>{$mark->GetTitle(30)|escape:'html'}</strong></a>
     &sdot;
     <a href="{geturl project=$project action=shortlog hash=$commit page=$page}">{t}deselect{/t}</a>
     <br />
   {/if}
 </div>

 {include file='title.tpl' target='summary'}

 {include file='shortloglist.tpl' source='shortlog'}

{/block}
