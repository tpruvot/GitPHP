{*
 * Graph selection template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
{extends file='projectbase.tpl'}

{block name=javascript}
{if $graphtype=='languagedist'}
  require.deps = ['languagedist'];
  {if file_exists('js/languagedist.min.js')}
  require.paths.languagedist = "languagedist.min";
  {/if}
{elseif $graphtype=='commitactivity'}
  require.deps = ['commitactivity'];
  {if file_exists('js/commitactivity.min.js')}
  require.paths.commitactivity = "commitactivity.min";
  {/if}
{/if}
{/block}

{block name=main}

<div class="page_nav">
{include file='nav.tpl' commit=$head current='graph'}
<br />
<br />
{if $graphtype=='commitactivity'}
  {t}commit activity{/t}
{else}
  <a href="{geturl project=$project action=graph graphtype=commitactivity}">{t}commit activity{/t}</a>
{/if}
|
{if $graphtype=='languagedist'}
  {t}language distribution{/t}
{else}
  <a href="{geturl project=$project action=graph graphtype=languagedist}">{t}language distribution{/t}</a>
{/if}
</div>

{include file='title.tpl'}

<div id="graph">
</div>

{/block}
