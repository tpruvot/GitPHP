{*
 *  blobdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{if $sidebyside}
{block name=javascriptpaths}
{if file_exists('js/blobdiff.min.js')}
	GitPHPJSPaths.blobdiff = "blobdiff.min";
{/if}
{/block}
{block name=javascriptmodules}
	GitPHPJSModules = ['blobdiff'];
{/block}
{/if}

{block name=main}

 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br />
   {if $sidebyside}
   <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$blobparent hashbase=$commit file=$file diffmode=unified}">{t}unified{/t}</a>
   {else}
   <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$blobparent hashbase=$commit file=$file diffmode=sidebyside}#D1">{t}side by side{/t}</a>
   {/if}
    |
   <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$blobparent file=$file output=plain}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blobparent target='blob'}

 <div class="page_body diff-file">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     {t}blob{/t}:<a href="{geturl project=$project action=blob hash=$blobparent hashbase=$commit file=$file}">{if $file}a/{$file}{else}{$blobparent->GetHash()}{/if}</a> -&gt; {t}blob{/t}:<a href="{geturl project=$project action=blob hash=$blob hashbase=$commit file=$file}">{if $file}b/{$file}{else}{$blob->GetHash()}{/if}</a>

{if $picture}
   </div>
   <div class="diff_pict">
     {if $filediff->GetStatus() == 'A'}
      {t}(new){/t}
     {else}
      <img class="old" valign="middle" src="{geturl project=$project action=blob hash=$blobparent file=$file output=plain}">
     {/if}
      <img class="new" valign="middle" src="{geturl project=$project action=blob hash=$blob file=$file output=plain}">

{else}

     {t}numstat{/t}:<span class="commit_fadd">{if $filediff->totAdd}+{$filediff->totAdd}{/if}</span>
     <span class="commit_fdel">{if $filediff->totDel}-{$filediff->totDel}{/if}</span>

     {if $sidebyside}
     <div class="diff-head-links">
       <a onclick="toggleTabs(this);" href="javascript:void(0)">{t}toggle tabs{/t}</a>, 
       <a onclick="toggleNumbers(this);" href="javascript:void(0)">{t}numbers{/t}</a> | 
       <a onclick="toggleLeft(this);" href="javascript:void(0)">{t}left only{/t}</a>,
       <a onclick="toggleRight(this);" href="javascript:void(0)">{t}right only{/t}</a> | 
       <a href="#D1">{t}first{/t}</a>,
       <a onclick="scrollToDiff(this,'tr.diff-focus:last');" href="javascript:void(0)">{t}last{/t}</a> {t}diff{/t}
       (<span class="diff-count">{$filediff->GetDiffCount()}</span>)
     </div>
     {/if}
   </div>
   {if $sidebyside}
   {* Display the sidebysidediff *}
   {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
   {else}
   {* Display the diff *}
   {include file='filediff.tpl' diff=$filediff->GetDiff($file, false, true)}
   {/if}

/if}

 </div>

{/block}
