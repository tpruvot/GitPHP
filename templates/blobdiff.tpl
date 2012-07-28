{*
 *  blobdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br />
   {if $sidebyside}
   <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$blobparent hashbase=$commit file=$file diffmode=unified}">{t}unified{/t}</a>
   {else}
   <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$blobparent hashbase=$commit file=$file diffmode=sidebyside}">{t}side by side{/t}</a>
   {/if}
    |
   <a href="{geturl project=$project action=blobdiff hash=$blob hashparent=$blobparent file=$file output=plain}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blobparent target='blob'}
 
 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     {t}blob{/t}:<a href="{geturl project=$project action=blob hash=$blobparent hashbase=$commit file=$file}">{if $file}a/{$file}{else}{$blobparent->GetHash()}{/if}</a> -&gt; {t}blob{/t}:<a href="{geturl project=$project action=blob hash=$blob hashbase=$commit file=$file}">{if $file}b/{$file}{else}{$blob->GetHash()}{/if}</a>
   </div>
   {if $filediff->IsBinary()}
<pre>
 {t 1=$filediff->GetFromLabel($file) 2=$filediff->GetToLabel($file)}Binary files %1 and %2 differ{/t}
</pre>
   {else}
     {if $sidebyside}
       {* Display the sidebysidediff *}
       {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
     {else}
       {* Display the diff *}
       {include file='filediff.tpl' diff=$filediff->GetDiff($file, false, true)}
     {/if}
   {/if}
 </div>

{/block}
