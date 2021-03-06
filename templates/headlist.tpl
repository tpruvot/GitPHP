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

 <table class="headlist">
   {* Loop and display each head *}
   {foreach from=$headlist item=head name=heads}
       {assign var=headcommit value=$head->GetCommit()}
       <tr class="{cycle values="light,dark"}">
         <td class="age"><em>{agestring age=$headcommit->GetAge()}</em></td>
         <td><a href="{if $router->GetCleanUrl()}{geturl project=$project action=heads hash=$head->GetName()}{else}{geturl project=$project action=shortlog hash=$head->GetName()}{/if}" class="list"><strong>{$head->GetName()}</strong></a></td>
         <td class="link"><a href="{geturl project=$project action=shortlog hash=$head->GetName()}">{t}shortlog{/t}</a> | <a href="{geturl project=$project action=log hash=$head->GetName()}">{t}log{/t}</a> |
          <a href="{geturl project=$project action=tree hashbase=$headcommit}">{t}tree{/t}</a></td>
       </tr>
   {/foreach}
   {if $hasmoreheads}
       <tr>
       {if $source == 'summary'}
       <td><a href="{geturl project=$project action=heads}">&hellip;</a></td>
       {else}
       <td><a href="{geturl project=$project action=heads page=$page+1}" title="Alt-n">{t}next{/t}</a></td>
       {/if}
       <td></td><td></td>
       </tr>
   {/if}
 </table>

