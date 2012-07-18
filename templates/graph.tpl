{*
 * Graph selection template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
{extends file='projectbase.tpl'}

{block name=main}

<div class="page_nav">
{include file='nav.tpl' commit=$head current='graph'}
<br />
<br />
{if $graphtype=='commitactivity'}
  {t}commit activity{/t}
{else}
  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=graph&g=commitactivity">{t}commit activity{/t}</a>
{/if}
</div>

{include file='title.tpl'}

{if $graphtype}
<div id="graph">
</div>
{else}
Select a graph type
{/if}

{/block}
