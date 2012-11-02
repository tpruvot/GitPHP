{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='main.tpl'}

{block name=javascript}
require.deps = ['projectlist'];
{if file_exists('js/projectlist.min.js')}
require.paths.projectlist = "projectlist.min";
{/if}
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
{t}Search projects{/t}: <input type="search" name="s" class="projectSearchBox" {if $search}value="{$search}"{/if} /> <a href="{geturl}" class="clearSearch" {if !$search}style="display: none;"{/if}>X</a> {if $javascript}<img src="images/search-loader.gif" class="searchSpinner" style="display: none;" alt="{t}Loading…{/t}" />{/if}
</form>
</div>

<table class="projectList">
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
        {if $sort == "owner"}
          <th>{t}Owner{/t}</th>
        {else}
          <th><a class="header" href="{geturl sort=owner}">{t}Owner{/t}</a></th>
        {/if}
        {if $sort == "age"}
          <th>{t}Last Change{/t}</th>
        {else}
          <th><a class="header" href="{geturl sort=age}">{t}Last Change{/t}</a></th>
        {/if}
        <th>{t}Actions{/t}</th>
      </tr>
    {/if}

    {if $currentcategory != $proj->GetCategory()}
      {assign var=currentcategory value=$proj->GetCategory()}
      {if $currentcategory != ''}
        <tr class="light categoryRow">
          <th class="categoryName">{$currentcategory}</th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
        </tr>
      {/if}
    {/if}

    <tr class="{cycle values="light,dark"} projectRow {if $loginenabled && !$proj->UserCanAccess($loggedinuser)}disabled{/if}">
      <td class="projectName">
        {if !$loginenabled || $proj->UserCanAccess($loggedinuser)}
        <a href="{geturl project=$proj}" class="list {if $currentcategory != ''}indent{/if}"><span>{$proj->GetProject()}</span></a>
        {else}
        <span {if $currentcategory != ''}class="indent"{/if}>{$proj->GetProject()}</span>
        {/if}
      </td>
      <td class="projectDescription">
        {if !$loginenabled || $proj->UserCanAccess($loggedinuser)}
        <a href="{geturl project=$proj}" class="list"><span>{$proj->GetDescription()|escape}</span></a>
        {else}
        <span>{$proj->GetDescription()|escape}</span>
        {/if}
      </td>
      <td class="projectOwner"><em>{$proj->GetOwner()|escape:'html'}</em></td>
      {assign var=projecthead value=$proj->GetHeadCommit()}
      <td class="projectAge">
        {if $projecthead}
          {if $proj->GetAge() < 7200}   {* 60*60*2, or 2 hours *}
            <span class="agehighlight"><strong><em><time datetime="{$proj->GetEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{agestring age=$proj->GetAge()}</time></em></strong></span>
          {elseif $proj->GetAge() < 172800}   {* 60*60*24*2, or 2 days *}
            <span class="agehighlight"><em><time datetime="{$proj->GetEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{agestring age=$proj->GetAge()}</time></em></span>
          {else}
            <em><time datetime="{$proj->GetEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{agestring age=$proj->GetAge()}</time></em>
          {/if}
	{else}
	  <em class="empty">{t}No commits{/t}</em>
	{/if}
      </td>
      <td class="link">
        {if !$loginenabled || $proj->UserCanAccess($loggedinuser)}
        <a href="{geturl project=$proj}">{t}summary{/t}</a>
	{if $projecthead}
	| 
	<a href="{geturl project=$proj action=shortlog}">{t}shortlog{/t}</a> | 
	<a href="{geturl project=$proj action=log}">{t}log{/t}</a> | 
	<a href="{geturl project=$proj action=tree}">{t}tree{/t}</a> | 
	<a href="{geturl project=$proj action=snapshot hash=HEAD}" class="snapshotTip">{t}snapshot{/t}</a>
	{/if}
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

