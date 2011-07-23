{*
 *  tree.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=javascript}
    {if file_exists("js/tree.min.js")}
    <script type="text/javascript" src="js/tree.min.js"></script>
    {else}
    <script type="text/javascript" src="js/tree.js"></script>
    {/if}
{/block}

{block name=main}

 {* Nav *}
   <div class="page_nav">
     {include file='nav.tpl' current='tree' logcommit=$commit}
     <br /><br />
   </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$tree target='tree'}
 
 <div class="page_body">
   {* List files *}
<table cellspacing="0" class="treeTable">
     {include file='treelist.tpl'}
</table>
 </div>

{/block}
