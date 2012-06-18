{*
 *  remotes.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Remote Head view template
 *}
{extends file='projectbase.tpl'}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' commit=$head treecommit=$head}
   <br /><br />
 </div>

 {include file='title.tpl' target='summary'}

 {include file='remotelist.tpl'}

{/block}
