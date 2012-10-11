{*
 * blame.tpl
 * gitphp: A PHP git repository browser
 * Component: Blame view template
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=css}
{if $geshicss}
  <style type="text/css">
  {$geshicss}
  </style>
{/if}
{/block}

{block name=main}

 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br />
   <a href="{geturl project=$project action=blob hash=$blob file=$blob->GetPath() output=plain}">{t}plain{/t}</a> | 
   {if $commit->GetHash() != $head->GetHash()}
     <a href="{geturl project=$project action=blame hashbase=HEAD file=$blob->GetPath()}">{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
   {/if}
    | blame
   <br />
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blob target='blob'}
 
 <div class="page_body">
   {if $geshi}
     {$geshihead}
       <td class="ln de1" id="blameData">
        {include file='blamedata.tpl'}
       </td>
     {$geshibody}
     {$geshifoot}
   {else}
 	<table class="code">
	{foreach from=$blob->GetData(true) item=blobline name=blob}
	  {assign var=blamecommit value=$blame[$smarty.foreach.blob.iteration]}
	  {if $blamecommit}
	    {cycle values="light,dark" assign=rowclass}
	  {/if}
	  <tr class="{$rowclass}">
	    <td class="date">
	      {if $blamecommit}
	        <a href="{geturl project=$project action=commit hash=$blamecommit}" title="{$blamecommit->GetTitle()|escape}" class="commitTip"><time datetime="{$blamecommit->GetAuthorEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{$blamecommit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M:%S"}</time></a>
	      {/if}
	    </td>
	    <td class="author">
	      {if $blamecommit}
	        {$blamecommit->GetAuthorName()|escape}
	      {/if}
	    </td>
	    <td class="num"><a id="l{$smarty.foreach.blob.iteration}" href="#l{$smarty.foreach.blob.iteration}" class="linenr">{$smarty.foreach.blob.iteration}</a></td>
	    <td class="codeline">{$blobline|escape}</td>
	  </tr>
	{/foreach}
	</table>
  {/if}
 </div>

{/block}
