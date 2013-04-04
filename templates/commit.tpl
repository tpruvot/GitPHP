{*
 *  commit.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

 <div class="page_nav">
   {include file='nav.tpl' logcommit=$commit treecommit=$commit current='commit'}
   <br /><br />
 </div>

{if $commit->GetParent()}
 	{include file='title.tpl' titlecommit=$commit target='commitdiff'}
{else}
	{include file='title.tpl' titlecommit=$commit titletree=$tree target='tree'}
{/if}

 <div class="title_text">
   {* Commit data *}
   <table>
     <tr>
       <td>{t}author{/t}</td>
       <td>{$commit->GetAuthorName()}</td>
       <td></td>
     </tr>
     <tr>
       <td></td>
       <td>
       {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} 
       {assign var=hourlocal value=$commit->GetAuthorLocalEpoch()|date_format:"%H"}
       {if $hourlocal < 6}
       (<span class="latenight">{$commit->GetAuthorLocalEpoch()|date_format:"%R"}</span> {$commit->GetAuthorTimezone()})
       {else}
       ({$commit->GetAuthorLocalEpoch()|date_format:"%R"} {$commit->GetAuthorTimezone()})
       {/if}
       </td>
       <td></td>
     </tr>
     <tr>
       <td>{t}committer{/t}</td>
       <td>{$commit->GetCommitterName()}</td>
       <td></td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} ({$commit->GetCommitterLocalEpoch()|date_format:"%R"} {$commit->GetCommitterTimezone()})</td>
       <td></td>
     </tr>
     <tr>
       <td>{t}commit{/t}</td>
       <td class="monospace">{$commit->GetHash()}</td>
       <td></td>
     </tr>
     <tr>
       <td>{t}tree{/t}</td>
       <td class="monospace"><a href="{geturl project=$project action=tree hash=$tree hashbase=$commit}" class="list">{$tree->GetHash()}</a></td>
       <td class="link"><a href="{geturl project=$project action=tree hash=$tree hashbase=$commit}">{t}tree{/t}</a> | <a href="{geturl project=$project action=snapshot hash=$commit}" class="snapshotTip">{t}snapshot{/t}</a></td>
     </tr>
     {foreach from=$commit->GetParents() item=par}
       <tr>
         <td>{t}parent{/t}</td>
         <td class="monospace"><a href="{geturl project=$project action=commit hash=$par}" class="list">{$par->GetHash()}</a></td>
         <td class="link"><a href="{geturl project=$project action=commit hash=$par}">{t}commit{/t}</a> | <a 
           href="{geturl project=$project action=commitdiff hash=$commit hashparent=$par output=unified}">{t}commitdiff{/t} {t}unified{/t}</a> | <a
           href="{geturl project=$project action=commitdiff hash=$commit hashparent=$par output=sidebyside}">{t}side by side{/t}</a>
         </td>
       </tr>
     {/foreach}
   </table>
 </div>
{assign var=comment value=$commit->GetComment()}
{if end($comment) != $commit->GetTitle()}
 <div class="page_body">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   <div class="commit_comment" style="overflow-y: auto; max-height: 30em;">
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
   </div>
 </div>
{/if}
 <div class="list_head">
   {if $treediff->Count() > 5}
     {t count=$treediff->Count() 1=$treediff->Count() plural="%1 files changed:"}%1 file changed:{/t}
   {/if}
 </div>
 <table class="filelist">
   {* Loop and show files changed *}
   {foreach from=$treediff item=diffline}
     <tr class="{cycle values="light,dark"}">
	 <td class="commit_fadd">{if $diffline->totAdd}+{$diffline->totAdd}{/if}</td>
	 <td class="commit_fdel">{if $diffline->totDel}-{$diffline->totDel}{/if}</td>
       {if $diffline->GetStatus() == "A"}
	 <td>
	   <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() hashbase=$commit file=$diffline->GetFromFile()}" class="list">
	     {$diffline->GetFromFile()}
	   </a>
	 </td>
	 <td>
	   <span class="newfile">
	     {localfiletype type=$diffline->GetToFileType() assign=localtotype}
	     [
	     {if $diffline->ToFileIsRegular()}
	       {assign var=tomode value=$diffline->GetToModeShort()}
	       {t 1=$localtotype 2=$tomode}new %1 with mode %2{/t}
	     {else}
	       {t 1=$localtotype}new %1{/t}
	     {/if}
	     ]
	   </span>
	 </td>
	 <td class="link">
	   <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() hashbase=$commit file=$diffline->GetFromFile()}">{t}blob{/t}</a>
	    | 
	   <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() file=$diffline->GetFromFile() output=plain}">{t}plain{/t}</a>
	 </td>
       {elseif $diffline->GetStatus() == "D"}
	 {assign var=parent value=$commit->GetParent()}
	 <td>
	   <a href="{geturl project=$project action=blob hash=$diffline->GetFromBlob() hashbase=$commit file=$diffline->GetFromFile()}" class="list">
	     {$diffline->GetFromFile()}
	   </a>
	 </td>
	 <td>
	   <span class="deletedfile">
	     {localfiletype type=$diffline->GetFromFileType() assign=localfromtype}
	     [ {t 1=$localfromtype}deleted %1{/t} ]
	   </span>
	 </td>
	 <td class="link">
	   <a href="{geturl project=$project action=blob hash=$diffline->GetFromBlob() hashbase=$commit file=$diffline->GetFromFile()}">{t}blob{/t}</a>
	    | 
	   <a href="{geturl project=$project action=history hash=$parent file=$diffline->GetFromFile()}">{t}history{/t}</a>
	    | 
	   <a href="{geturl project=$project action=blob hash=$diffline->GetFromBlob() file=$diffline->GetFromFile() output=plain}">{t}plain{/t}</a>
	 </td>
       {elseif $diffline->GetStatus() == "M" || $diffline->GetStatus() == "T"}
	 <td>
           {if $diffline->GetToHash() != $diffline->GetFromHash()}
             <a href="{geturl project=$project action=blobdiff hash=$diffline->GetToBlob() hashparent=$diffline->GetFromBlob() hashbase=$commit file=$diffline->GetToFile()}" class="list">
	       {$diffline->GetToFile()}
	     </a>
           {else}
             <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() hashbase=$commit file=$diffline->GetToFile()}" class="list">
	       {$diffline->GetToFile()}
	     </a>
           {/if}
	 </td>
	 <td>
	   {if $diffline->GetFromMode() != $diffline->GetToMode()}
	     <span class="changedfile">
	       [
	       {if $diffline->FileTypeChanged()}
	         {localfiletype type=$diffline->GetFromFileType() assign=localfromtype}
	         {localfiletype type=$diffline->GetToFileType() assign=localtotype}
	         {if $diffline->FileModeChanged()}
		   {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
		     {assign var=frommode value=$diffline->GetFromModeShort()}
		     {assign var=tomode value=$diffline->GetToModeShort()}
		     {t 1=$localfromtype 2=$localtotype 3=$frommode 4=$tomode}changed from %1 to %2 mode: %3 -> %4{/t}
		   {elseif $diffline->ToFileIsRegular()}
		     {assign var=tomode value=$diffline->GetToModeShort()}
		     {t 1=$localfromtype 2=$localtotype 3=$tomode}changed from %1 to %2 mode: %3{/t}
		   {else}
		     {t 1=$localfromtype 2=$localtotype}changed from %1 to %2{/t}
		   {/if}
		 {else}
		   {t 1=$localfromtype 2=$localtotype}changed from %1 to %2{/t}
		 {/if}
	       {else}
		 {if $diffline->FileModeChanged()}
		   {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
		     {assign var=frommode value=$diffline->GetFromModeShort()}
		     {assign var=tomode value=$diffline->GetToModeShort()}
		     {t 1=$frommode 2=$tomode}changed mode: %1 -> %2{/t}
		   {elseif $diffline->ToFileIsRegular()}
		     {assign var=tomode value=$diffline->GetToModeShort()}
		     {t 1=$tomode}changed mode: %1{/t}
		   {else}
		     {t}changed{/t}
		   {/if}
		 {else}
		   {t}changed{/t}
		 {/if}
	       {/if}
	       ]
	     </span>
	   {/if}
	 </td>
	 <td class="link">
	   <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() hashbase=$commit file=$diffline->GetToFile()}">{t}blob{/t}</a>
	   {if $diffline->GetToHash() != $diffline->GetFromHash()}
	     | <a href="{geturl project=$project action=blobdiff hash=$diffline->GetToBlob() hashparent=$diffline->GetFromBlob() hashbase=$commit file=$diffline->GetToFile()}">{t}diff{/t}</a>
	   {/if}
	     | <a href="{geturl project=$project action=history hash=$commit file=$diffline->GetFromFile()}">{t}history{/t}</a>
             | <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() file=$diffline->GetToFile() output=plain}">{t}plain{/t}</a>
	 </td>
       {elseif $diffline->GetStatus() == "R"}
	 <td>
	   <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() hashbase=$commit file=$diffline->GetToFile()}" class="list">
	     {$diffline->GetToFile()}</a>
	 </td>
	 <td>
	   <span class="movedfile">
	     {capture assign=fromfilelink}
	     <a href="{geturl project=$project action=blob hash=$diffline->GetFromBlob() hashbase=$commit file=$diffline->GetFromFile()}" class="list">{$diffline->GetFromFile()}</a>
	     {/capture}
	     [
	     {assign var=similarity value=$diffline->GetSimilarity()}
	     {if $diffline->GetFromMode() != $diffline->GetToMode()}
	       {assign var=tomode value=$diffline->GetToModeShort()}
	       {t escape=no 1=$fromfilelink 2=$similarity 3=$tomode}moved from %1 with %2%% similarity, mode: %3{/t}
	     {else}
	       {t escape=no 1=$fromfilelink 2=$similarity}moved from %1 with %2%% similarity{/t}
	     {/if}
	     ]
	   </span>
	 </td>
	 <td class="link">
	   <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() hashbase=$commit file=$diffline->GetToFile()}">{t}blob{/t}</a>
	   {if $diffline->GetToHash() != $diffline->GetFromHash()}
	     | <a href="{geturl project=$project action=blobdiff hash=$diffline->GetToBlob() hashparent=$diffline->GetFromBlob() hashbase=$commit file=$diffline->GetToFile()}">{t}diff{/t}</a>
	   {/if}
	    | <a href="{geturl project=$project action=blob hash=$diffline->GetToBlob() file=$diffline->GetToFile() output=plain}}">{t}plain{/t}</a>
	 </td>
       {/if}

     </tr>
   {/foreach}
 </table>

{/block}
