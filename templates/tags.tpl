{*
 *  tags.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=links append}
{if $page > 0}
<link rel="prev" href="{geturl project=$project action=tags page=$page-1}" />
{/if}
{if $hasmoretags}
<link rel="next" href="{geturl project=$project action=tags page=$page+1}" />
{/if}
{/block}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' commit=$head treecommit=$head}
   <br />
   {if $page > 0}
     <a href="{geturl project=$project action=tags}">{t}first{/t}</a>
   {else}
     {t}first{/t}
   {/if}
     &sdot;
   {if $page > 0}
     <a href="{geturl project=$project action=tags page=$page-1}">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
     &sdot;
   {if $hasmoretags}
     <a href="{geturl project=$project action=tags page=$page+1}">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
 </div>

{include file='title.tpl' target='summary'}
 
 {* Display tags *}

 {include file='taglist.tpl' source=tags}

{/block}
