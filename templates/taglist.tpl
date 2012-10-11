{*
 * Taglist
 *
 * Tag list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table class="tagTable">
   {foreach from=$taglist item=tag name=tag}
     <tr class="{cycle name=tags values="light,dark"}">
	   {assign var=object value=$tag->GetObject()}
	   {assign var=tagcommit value=$tag->GetCommit()}
	   {assign var=objtype value=$tag->GetType()}
           <td><em>{if $tagcommit}<time datetime="{$tagcommit->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{agestring age=$tagcommit->GetAge()}</time>{else}<time datetime="{$tag->GetTaggerEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{agestring age=$tag->GetAge()}</time>{/if}</em></td>
           <td>
	   {if $objtype == 'commit'}
		   <a href="{geturl project=$project action=commit hash=$object}" class="list"><strong>{$tag->GetName()}</strong></a>
	   {elseif $objtype == 'tag'}
		   <a href="{geturl project=$project action=tag tag=$tag}" class="list"><strong>{$tag->GetName()}</strong></a>
	   {elseif $objtype == 'blob'}
		   <a href="{geturl project=$project action=blob hash=$object}" class="list"><strong>{$tag->GetName()}</strong></a>
	   {/if}
	   </td>
           <td>
	     {assign var=comment value=$tag->GetComment()}
             {if count($comment) > 0}
               <a class="list {if !$tag->LightTag()}tagTip{/if}" href="{geturl project=$project action=tag tag=$tag}">{$comment[0]}</a>
             {/if}
           </td>
           <td class="link">
             {if !$tag->LightTag()}
   	       <a href="{geturl project=$project action=tag tag=$tag}">{t}tag{/t}</a> | 
             {/if}
	     {if $objtype == 'blob'}
		<a href="{geturl project=$project action=blob hash=$object}">{t}blob{/t}</a>
	     {else}
             <a href="{geturl project=$project action=commit hash=$tagcommit}">{t}commit{/t}</a>
	      | <a href="{geturl project=$project action=shortlog hash=$tagcommit}">{t}shortlog{/t}</a> | <a href="{geturl project=$project action=log hash=$tagcommit}">{t}log{/t}</a> | <a href="{geturl project=$project action=snapshot hash=$tagcommit}" class="snapshotTip">{t}snapshot{/t}</a>
	      {/if}
           </td>
       </tr>
     {/foreach}
     {if $hasmoretags}
       <tr>
         {if $source == 'summary'}
           <td><a href="{geturl project=$project action=tags}">&hellip;</a></td>
	   <td></td><td></td><td></td>
	 {else if $source == 'tags'}
	   <td><a href="{geturl project=$project action=tags page=$page+1}" title="Alt-n">{t}next{/t}</a></td>
	   <td></td><td></td><td></td>
	 {/if}
       </tr>
     {/if}
   </table>
