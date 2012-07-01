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
   <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}&amp;o=unified">{t}unified{/t}</a>
   {else}
   <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}&amp;o=sidebyside">{t}side by side{/t}</a>
   {/if}
    |
   <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=blobdiff_plain&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;f={$file}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blobparent target='blob'}
 
 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     {t}blob{/t}:<a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=blob&amp;h={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}a/{$file}{else}{$blobparent->GetHash()}{/if}</a> -&gt; {t}blob{/t}:<a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=blob&amp;h={$blob->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}b/{$file}{else}{$blob->GetHash()}{/if}</a>
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
