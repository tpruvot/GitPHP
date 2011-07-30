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

 <table cellspacing="0">
   {* Loop and display each head *}
   {foreach from=$remotelist item=head name=remotes}
       {assign var=headcommit value=$head->GetCommit()}
       <tr class="{cycle values="light,dark"}">
         <td><em>{$headcommit->GetAge()|agestring}</em></td>
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/remotes/{$head->GetName()}" class="list"><strong>{$head->GetName()}</strong></a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/remotes/{$head->GetName()}">{t}shortlog{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h=refs/remotes/{$head->GetName()}">{t}log{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$headcommit->GetHash()}">{t}tree{/t}</a></td>
       </tr>
   {/foreach}
   {if $hasmoreremotes}
       <tr>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=remotes">&hellip;</a></td>
       </tr>
   {/if}
 </table>

