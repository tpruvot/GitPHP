{*
 * Graph selection template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
{extends file='projectbase.tpl'}

{block name=javascriptpaths}
GitPHPJSPaths.d3 = "ext/d3.v2.min"
{if $graphtype=='languagedist' && file_exists('js/languagedist.min.js')}
GitPHPJSPaths.languagedist = "languagedist.min";
{elseif $graphtype=='commitactivity' && file_exists('js/commitactivity.min.js')}
GitPHPJSPaths.commitactivity = "commitactivity.min";
{/if}
{/block}

{block name=javascriptmodules}
{if $graphtype}
GitPHPJSModules = ['{$graphtype}'];
{else}
GitPHPJSModules = ['common'];
{/if}
{/block}

{block name=main}

<div class="page_nav">
{include file='nav.tpl' commit=$head current='graph'}
<br />
{if $graphtype=='commitactivity'}
  {t}commit activity{/t}
{else}
  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=graph&g=commitactivity">{t}commit activity{/t}</a>
{/if}
|
{if $graphtype=='languagedist'}
  {t}language distribution{/t}
{else}
  <a href="{$scripturl}?p={$project->GetProject()|rawurlencode}&amp;a=graph&g=languagedist">{t}language distribution{/t}</a>
{/if}
</div>

{include file='title.tpl'}

<div id="graph">
</div>

{/block}
