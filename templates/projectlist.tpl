{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='main.tpl'}

{block name=javascriptpaths}
{if file_exists('js/projectlist.min.js')}
GitPHPJSPaths.projectlist = "projectlist.min";
{/if}
{/block}
{block name=javascriptmodules}
GitPHPJSModules = ['projectlist'];
{/block}

{block name=main}

<div class="index_header">
{if file_exists('templates/hometext.tpl') }
{include file='hometext.tpl'}
{else}
{* default header *}
<p>
git source code archive
</p>
{/if}
</div>

<div class="projectSearch">
<form method="get" action="{geturl}" id="projectSearchForm" enctype="application/x-www-form-urlencoded">
{t}Search projects{/t}: <input type="text" name="s" class="projectSearchBox" {if $search}value="{$search}"{/if} /> <a href="{geturl}" class="clearSearch" {if !$search}style="display: none;"{/if}>X</a> {if $javascript}<img src="images/search-loader.gif" class="searchSpinner" style="display: none;" alt="{t}Loading…{/t}" />{/if}
</form>
</div>

<table class="projectList">
  {assign var=currentcategory value="&nbsp;"}
  {foreach name=projects from=$projectlist item=proj}
    {if $smarty.foreach.projects.first}
      {* Header *}
      <tr class="projectHeader">
        {if $sort == "project"}
          <th>{t}Project{/t}</th>
        {else}
          <th><a class="header" href="{geturl sort=project}">{t}Project{/t}</a></th>
        {/if}
        {if $sort == "descr"}
          <th>{t}Description{/t}</th>
        {else}
          <th><a class="header" href="{geturl sort=descr}">{t}Description{/t}</a></th>
        {/if}
        {if $sort == "age"}
          <th>{t}Last Change{/t}</th>
        {else}
          <th><a class="header" href="{geturl sort=age}">{t}Last Change{/t}</a></th>
        {/if}
        {if $show_branch }
         {if $sort == "branch"}
          <th>{t}Branch{/t}</th>
         {else}
          <th><a class="header" href="{geturl sort=branch}">{t}Branch{/t}</a></th>
         {/if}
        {/if}
        {if $show_owner }
         {if $sort == "owner"}
          <th>{t}Owner{/t}</th>
         {else}
          <th><a class="header" href="{geturl sort=owner}">{t}Owner{/t}</a></th>
         {/if}
        {/if}
        <th class="actions">{t}Actions{/t}</th>
        <th></th>
      </tr>
    {/if}

    {if $currentcategory != $proj->GetCategory('&nbsp;')}
      {assign var=currentcategory value=$proj->GetCategory('&nbsp;')}
      {if $currentcategory != "&nbsp;" || $sort == "age"}
        <tr class="light categoryRow">
          <th class="categoryName">{$currentcategory}</th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
        </tr>
      {/if}
    {/if}

    <tr class="{cycle values="light,dark"} projectRow">
      <td class="projectName">
        <a href="{geturl project=$proj}" class="list {if $currentcategory != ''}indent{/if}">{$proj->GetProject()}</a>
      </td>
      <td class="projectDescription"><a href="{geturl project=$proj}" class="list">{$proj->GetDescription()}</a></td>
      {assign var=projecthead value=$proj->GetHeadCommit()}
      <td class="projectAge">
        {if $projecthead}
          {if $proj->GetAge() <= 0}
            <em class="empty">{t}No commits{/t}</em>
          {elseif $proj->GetAge() < 7200}   {* 60*60*2, or 2 hours *}
            <span class="agehighlight"><strong><em>{agestring age=$proj->GetAge()}</em></strong></span>
          {elseif $proj->GetAge() < 172800}   {* 60*60*24*2, or 2 days *}
            <span class="agehighlight"><em>{agestring age=$proj->GetAge()}</em></span>
          {else}
            <em>{agestring age=$proj->GetAge()}</em>
          {/if}
        {else}
            <em class="empty">{t}No commits{/t}</em>
        {/if}
      </td>
      {if $show_branch }
        {if $proj->repoTag == ''}
      <td class="projectBranch"><em>{$proj->repoRemote}/{$proj->repoBranch|escape:'html'}</em></td>
        {else}
      <td class="projectBranch"><em>{$proj->repoTag|escape:'html'}</em></td>
        {/if}
      {/if}
      {if $show_owner }
      <td class="projectOwner"><em>{$proj->GetOwner()|escape:'html'}</em></td>
      {/if}
      <td class="link">
        <a href="{geturl project=$proj}">{t}summary{/t}</a>
{if $projecthead}
      | <a href="{geturl project=$proj action=shortlog}">{t}shortlog{/t}</a>
      | <a href="{geturl project=$proj action=log}">{t}log{/t}</a>
      | <a href="{geturl project=$proj action=tree}">{t}tree{/t}</a>
      | <a href="{geturl project=$proj action=snapshot hash=HEAD}" class="snapshotTip">{t}snapshot{/t}</a>
{/if}
      </td>
    </tr>
  {foreachelse}
    {if $search}
    <div class="message">{t 1=$search}No matches found for "%1"{/t}</div>
    {else}
    <div class="message">{t}No projects found{/t}</div>
    {/if}
  {/foreach}

</table>

{/block}

{block name=footer}
  <a href="{geturl action=opml}" class="rss_logo">{t}OPML{/t}</a>
  <a href="{geturl action=projectindex}" class="rss_logo">{t}TXT{/t}</a>
{/block}

