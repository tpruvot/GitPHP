{*
 * Headlist
 *
 * Head list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table>
   {* Loop and display each head *}
   {foreach from=$headlist item=head name=heads}
       {assign var=headcommit value=$head->GetCommit()}
       <tr class="{cycle values="light,dark"}">
         <td><em><time datetime="{$headcommit->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{agestring age=$headcommit->GetAge()}</time></em></td>
         <td><a href="{if $router->GetCleanUrl()}{geturl project=$project action=heads hash=$head->GetName()}{else}{geturl project=$project action=shortlog hash=$head}{/if}" class="list"><strong>{$head->GetName()}</strong></a></td>
         <td class="link"><a href="{geturl project=$project action=shortlog hash=$head}">{t}shortlog{/t}</a> | <a href="{geturl project=$project action=log hash=$head}">{t}log{/t}</a> | <a href="{geturl project=$project action=tree hashbase=$headcommit}">{t}tree{/t}</a></td>
       </tr>
   {/foreach}
   {if $hasmoreheads}
       <tr>
       {if $source == 'summary'}
         <td><a href="{geturl project=$project action=heads}">&hellip;</a></td>
	 <td></td><td></td>
       {else if $source == 'heads'}
         <td><a href="{geturl project=$project action=heads page=$page+1}" title="Alt-n">{t}next{/t}</a></td>
	 <td></td><td></td>
       {/if}
       </tr>
   {/if}
 </table>

