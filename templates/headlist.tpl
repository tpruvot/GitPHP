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
         <td><em>{agestring age=$headcommit->GetAge()}</em></td>
         <td><a href="{geturl project=$project action=shortlog hash=$head}" class="list"><strong>{$head->GetName()}</strong></a></td>
         <td class="link"><a href="{geturl project=$project action=shortlog hash=$head}">{t}shortlog{/t}</a> | <a href="{geturl project=$project action=log hash=$head}">{t}log{/t}</a> | <a href="{geturl project=$project action=tree hashbase=$headcommit}">{t}tree{/t}</a></td>
       </tr>
   {/foreach}
   {if $hasmoreheads}
       <tr>
       <td><a href="{geturl project=$project action=heads}">&hellip;</a></td>
       </tr>
   {/if}
 </table>

