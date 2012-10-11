{*
 *  tag.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' commit=$head treecommit=$head}
   <br /><br />
 </div>
 {* Tag data *}
 {assign var=object value=$tag->GetObject()}
 {assign var=objtype value=$tag->GetType()}
 <div class="title">
   {if $objtype == 'blob'}
   <a href="{geturl project=$project action=blob hash=$object}" class="title">{$tag->GetName()}</a>
   {else}
   <a href="{geturl project=$project action=commit hash=$object}" class="title">{$tag->GetName()}</a>
   {/if}
 </div>
 <div class="title_text">
   <table>
     <tr>
       <td>{t}object{/t}</td>
       {if $objtype == 'commit'}
         <td class="monospace"><a href="{geturl project=$project action=commit hash=$object}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{geturl project=$project action=commit hash=$object}">{t}commit{/t}</a></td>
       {elseif $objtype == 'tag'}
         <td class="monospace"><a href="{geturl project=$project action=tag tag=$object}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{geturl project=$project action=tag tag=$object}">{t}tag{/t}</a></td>
       {elseif $objtype == 'blob'}
         <td class="monospace"><a href="{geturl project=$project action=blob hash=$object}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{geturl project=$project action=blob hash=$object}">{t}blob{/t}</a></td>
       {/if}
     </tr>
     {if $tag->GetTagger()}
       <tr>
         <td>{t}author{/t}</td>
	 <td>{$tag->GetTagger()}</td>
       </tr>
       <tr>
         <td></td>
	 <td> <time datetime="{$tag->GetTaggerEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{$tag->GetTaggerEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}</time>
	 {assign var=hourlocal value=$tag->GetTaggerLocalEpoch()|date_format:"%H"}
	 {if $hourlocal < 6}
	 (<time datetime="{$tag->GetTaggerLocalEpoch()|date_format:"%Y-%m-%dT%H:%M:%S"}{$tag->GetTaggerTimezone(true)}"><span class="latenight">{$tag->GetTaggerLocalEpoch()|date_format:"%R"}</span> {$tag->GetTaggerTimezone()}</time>)
	 {else}
	 (<time datetime="{$tag->GetTaggerLocalEpoch()|date_format:"%Y-%m-%dT%H:%M:%S"}{$tag->GetTaggerTimezone(true)}">{$tag->GetTaggerLocalEpoch()|date_format:"%R"} {$tag->GetTaggerTimezone()}</time>)
	 {/if}
         </td>
       </tr>
     {/if}
   </table>
 </div>
 <div class="page_body">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   {foreach from=$tag->GetComment() item=line}
     {if strncasecmp(trim($line),'-----BEGIN PGP',14) == 0}
     <span class="pgpSig">
     {/if}
     {$line|htmlspecialchars|buglink:$bugpattern:$bugurl}<br />
     {if strncasecmp(trim($line),'-----END PGP',12) == 0}
     </span>
     {/if}
   {/foreach}
 </div>

{/block}
