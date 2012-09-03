{*
 *  commitdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=javascript}
require.deps = ['commitdiff'];
{if file_exists('js/commitdiff.min.js')}
require.paths.commitdiff = "commitdiff.min";
{/if}
{/block}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {if $commit}
   {assign var=tree value=$commit->GetTree()}
   {/if}
   {include file='nav.tpl' current='commitdiff' logcommit=$commit treecommit=$commit}
   <br />
   {if $sidebyside}
   <a href="{geturl project=$project action=commitdiff hash=$commit hashparent=$hashparent diffmode=unified}">{t}unified{/t}</a>
   {else}
   <a href="{geturl project=$project action=commitdiff hash=$commit hashparent=$hashparent diffmode=sidebyside}">{t}side by side{/t}</a>
   {/if}
   | <a href="{geturl project=$project action=commitdiff hash=$commit hashparent=$hashparent output=plain}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}
 
 <div class="page_body">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   {foreach from=$commit->GetComment() item=line}
     {if strncasecmp(trim($line),'Signed-off-by:',14) == 0}
     <span class="signedOffBy">{$line|htmlspecialchars|buglink:$bugpattern:$bugurl}</span>
     {else}
     {$line|htmlspecialchars|buglink:$bugpattern:$bugurl}
     {/if}
     <br />
   {/foreach}
   <br />

   {if $sidebyside && ($treediff->Count() > 1)}
    <div class="commitDiffSBS">

     <div class="SBSTOC">
       <ul>
       <li class="listcount">
       {t count=$treediff->Count() 1=$treediff->Count() plural="%1 files changed:"}%1 file changed:{/t} <a href="#" class="showAll">{t}(show all){/t}</a></li>
       {foreach from=$treediff item=filediff}
       <li>
       <a href="#{$filediff->GetFromHash()}_{$filediff->GetToHash()}" class="SBSTOCItem">
       {if $filediff->GetStatus() == 'A'}
         {if $filediff->GetToFile()}{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if} {t}(new){/t}
       {elseif $filediff->GetStatus() == 'D'}
         {if $filediff->GetFromFile()}{$filediff->GetFromFile()}{else}{$filediff->GetToFile()}{/if} {t}(deleted){/t}
       {elseif $filediff->GetStatus() == 'M'}
         {if $filediff->GetFromFile()}
	   {assign var=fromfilename value=$filediff->GetFromFile()}
	 {else}
	   {assign var=fromfilename value=$filediff->GetFromHash()}
	 {/if}
	 {if $filediff->GetToFile()}
	   {assign var=tofilename value=$filediff->GetToFile()}
	 {else}
	   {assign var=tofilename value=$filediff->GetToHash()}
	 {/if}
	 {$fromfilename}{if $fromfilename != $tofilename} -&gt; {$tofilename}{/if}
       {/if}
       </a>
       </li>
       {/foreach}
       </ul>
     </div>

     <div class="SBSContent">
   {/if}

   {* Diff each file changed *}
   {foreach from=$treediff item=filediff}
     <div class="diffBlob" id="{$filediff->GetFromHash()}_{$filediff->GetToHash()}">
     <div class="diff_info">
     {if ($filediff->GetStatus() == 'D') || ($filediff->GetStatus() == 'M')}
       {localfiletype type=$filediff->GetFromFileType()}:<a href="{geturl project=$project action=blob hash=$filediff->GetFromBlob() hashbase=$commit file=$filediff->GetFromFile()}">{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if}</a>
       {if $filediff->GetStatus() == 'D'}
         {t}(deleted){/t}
       {/if}
     {/if}

     {if $filediff->GetStatus() == 'M'}
       -&gt;
     {/if}

     {if ($filediff->GetStatus() == 'A') || ($filediff->GetStatus() == 'M')}
       {localfiletype type=$filediff->GetToFileType()}:<a href="{geturl project=$project action=blob hash=$filediff->GetToBlob() hashbase=$commit file=$filediff->GetToFile()}">{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if}</a>

       {if $filediff->GetStatus() == 'A'}
         {t}(new){/t}
       {/if}
     {/if}
     </div>
     {if $filediff->IsBinary()}
<pre>
 {t 1=$filediff->GetFromLabel() 2=$filediff->GetToLabel()}Binary files %1 and %2 differ{/t}
</pre>
     {else}
       {if $sidebyside}
          {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
       {else}
          {include file='filediff.tpl' diff=$filediff->GetDiff('', true, true)}
       {/if}
     {/if}
     </div>
   {/foreach}

   {if $sidebyside && ($treediff->Count() > 1)}
     </div>
     <div class="SBSFooter"></div>

    </div>
   {/if}


 </div>

{/block}
