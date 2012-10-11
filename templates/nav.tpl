{*
 * Nav
 *
 * Nav links template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

   {if $current=='summary'}
     {t}summary{/t}
   {else}
     <a href="{geturl project=$project}">{t}summary{/t}</a>
   {/if}
   | 
   {if $current=='shortlog' || !$commit}
     {t}shortlog{/t}
   {else}
     <a href="{geturl project=$project action=shortlog hash=$logcommit mark=$logmark}">{t}shortlog{/t}</a>
   {/if}
   | 
   {if $current=='log' || !$commit}
     {t}log{/t}
   {else}
     <a href="{geturl project=$project action=log hash=$logcommit mark=$logmark}">{t}log{/t}</a>
   {/if}
   | 
   {if $current=='commit' || !$commit}
     {t}commit{/t}
   {else}
     <a href="{geturl project=$project action=commit hash=$commit}">{t}commit{/t}</a>
   {/if}
   | 
   {if $current=='commitdiff' || !$commit}
     {t}commitdiff{/t}
   {else}
     <a href="{geturl project=$project action=commitdiff hash=$commit}">{t}commitdiff{/t}</a>
   {/if}
   | 
   {if $current=='tree' || !$commit}
     {t}tree{/t}
   {else}
     <a href="{geturl project=$project action=tree hashbase=$treecommit hash=$tree}">{t}tree{/t}</a>
   {/if}
