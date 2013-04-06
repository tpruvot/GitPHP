{*
 *  commitdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=javascriptpaths}
{if file_exists('js/commitdiff.min.js')}
GitPHPJSPaths.commitdiff = "commitdiff.min";
{/if}
{/block}
{block name=javascriptmodules}
	GitPHPJSModules = ['commitdiff'];
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
   <a href="{geturl project=$project action=commitdiff hash=$commit hashparent=$hashparent file=$file output=unified}">{t}unified{/t}</a>
   {else}
   <a href="{geturl project=$project action=commitdiff hash=$commit hashparent=$hashparent file=$file output=sidebyside}">{t}side by side{/t}</a>
   {/if}
   | <a href="{geturl project=$project action=commitdiff hash=$commit hashparent=$hashparent file=$file output=plain}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 <div class="page_body">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   {assign var=comment value=$commit->GetComment()}
   {if end($comment) != $commit->GetTitle()}
   {foreach from=$comment item=line}
     {if strstr(trim($line),'-by: ') || strstr(trim($line),'Cc: ')}
     <span class="signedOffBy">{$line|htmlspecialchars|buglink:$bugpattern:$bugurl}</span>
     {elseif preg_match('~http(s)?:~',$line)}
     <span class="signedOffBy commentLink">{$line|buglink:'/(http(s)?:\/\/)(.)*[\.](.)*$/':"\$0"}</span>
     {elseif strncasecmp(trim($line),'Change-Id:',10) == 0}
     <span class="changeId">{$line|buglink:$bugpattern:$bugurl}</span>
     {else}
     {$line|htmlspecialchars|commithash|buglink:$bugpattern:$bugurl}
     {/if}
     <br />
   {/foreach}
   <br />
   {/if}

   {if $sidebyside && ($treediff->Count() > 1)}
    <div class="commitDiffSBS">

     <div class="SBSTOC">
       <ul>
       <li class="listcount">
       {t count=$treediff->Count() 1=$treediff->Count() plural="%1 files changed:"}%1 file changed:{/t} [+/- {$treediff->StatCount()}] <a href="#" class="showAll">{t}(show all){/t}</a></li>
       {foreach from=$treediff item=filediff}
       <li class="SBSTOCFile">
       <a href="#{$filediff->GetFromHash()}_{$filediff->GetToHash()}" class="SBSTOCItem">
       {if $filediff->GetStatus() == 'A'}
         {if $filediff->GetToFile()}{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if}</a> <span class="add">({t}new{/t})</span>
       {elseif $filediff->GetStatus() == 'D'}
         {if $filediff->GetFromFile()}{$filediff->GetFromFile()}{else}{$filediff->GetToFile()}{/if}</a> <span class="del">({t}deleted{/t})</span>
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
	 {$fromfilename}</a>{if $fromfilename != $tofilename} -&gt; {$tofilename}{/if}
	 <span class="add">{if $filediff->totAdd}+{$filediff->totAdd}{/if}</span>
	 <span class="del">{if $filediff->totDel}-{$filediff->totDel}{/if}</span>
       {/if}
       </li>
       {/foreach}
       </ul>
     </div>

     <div class="SBSContent">
   {/if}

   {* Diff each file changed *}
   {foreach from=$treediff item=filediff}
     <div class="diffBlob diff-file" id="{$filediff->GetFromHash()}_{$filediff->GetToHash()}">
     <div class="diff_info">
     {if $filediff->GetStatus() != 'A'}
       {localfiletype type=$filediff->GetFromFileType() assign=localfromtype}
       {$localfromtype}:<a href="{geturl project=$project action=blob hash=$filediff->GetFromBlob() hashbase=$commit file=$filediff->GetFromFile()}">{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if}</a>
       {if $filediff->GetStatus() == 'D'}
         ({t}deleted{/t})
       {/if}
     {/if}

     {if $filediff->GetStatus() == 'M' || $filediff->GetStatus() == 'R'}
       -&gt;
     {/if}

     {if $filediff->GetStatus() != 'D'}
       {localfiletype type=$filediff->GetToFileType() assign=localtotype}
       {$localtotype}:<a href="{geturl project=$project action=blob hash=$filediff->GetToBlob() hashbase=$commit file=$filediff->GetToFile()}">{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if}</a>
       {if $filediff->GetStatus() == 'A'}
         ({t}new{/t})
       {/if}
     {/if}

     {if $sidebyside and $filediff->GetStatus() == 'M'}
         <div class="diff-head-links">
         <a onclick="toggleTabs(this);" href="javascript:void(0)">{t}toggle tabs{/t}</a>, 
         <a onclick="toggleNumbers(this);" href="javascript:void(0)">{t}numbers{/t}</a> | 
         <a onclick="toggleLeft(this);" href="javascript:void(0)">{t}left only{/t}</a>,
         <a onclick="toggleRight(this);" href="javascript:void(0)">{t}right only{/t}</a>
          | <a onclick="scrollToDiff(this,'tr.diff-focus:first');" href="javascript:void(0)">{t}first{/t}</a>,
         <a onclick="scrollToDiff(this,'tr.diff-focus:last');" href="javascript:void(0)">{t}last{/t}</a> {t}diff{/t}
         (<span class="diff-count">{$filediff->GetDiffCount()}</span>)
         </div>
     {/if}

     </div>

     {if $filediff->isPicture}
     <div class="diff_pict">
      {if $filediff->GetStatus() == 'A'}
       ({t}new{/t})
      {else}
       <img class="old" valign="middle" src="{geturl project=$project action=blob hash=$filediff->GetFromBlob() file=$filediff->GetFromFile() output=plain}">
      {/if}
      {if $filediff->GetStatus() == 'D'}
       ({t}deleted{/t})
      {else}
       <img class="new" valign="middle" src="{geturl project=$project action=blob hash=$filediff->GetToBlob() file=$filediff->GetToFile() output=plain}">
      {/if}
     </div>
     {elseif $sidebyside}
        {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
     {else}
        {include file='filediff.tpl' diff=$filediff->GetDiff('', true, true)}
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
