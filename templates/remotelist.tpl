{*
 * Remote Branch list
 *
 * Remote list template fragment
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table cellspacing="0" class="headlist">
   {* Loop and display each head *}
   {foreach from=$remotelist item=head name=heads}
       {assign var=headcommit value=$head->GetCommit()}
       <tr class="{cycle values="light,dark"}">
         <td><em>{agestring age=$headcommit->GetAge()}</em></td>
         <td><a href="{if $router->GetCleanUrl()}{geturl project=$project action=remotes hash=$head}{else}{geturl project=$project action=shortlog hash=$head}{/if}" class="list"><strong>{$head->GetName()}</strong></a></td>
         <td class="link"><a href="{geturl project=$project action=shortlog hash=$head}">{t}shortlog{/t}</a> | <a href="{geturl project=$project action=log hash=$head}">{t}log{/t}</a> | <a href="{geturl project=$project action=tree hashbase=$headcommit}">{t}tree{/t}</a></td>
       </tr>
   {/foreach}
   {if $hasmoreremotes}
       <tr>
       {if $source == 'summary'}
       <td><a href="{geturl project=$project action=remotes}">&hellip;</a></td>
       {else}
       <td><a href="{geturl project=$project action=remotes page=$page+1}" title="Alt-n">{t}next{/t}</a></td>
       {/if}
       <td></td><td></td>
       </tr>
   {/if}
 </table>

