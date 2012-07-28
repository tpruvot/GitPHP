{*
 * Projectbase
 *
 * Base template for all pages for a single project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}
{extends file='main.tpl'}

{block name=title}
{$pagetitle} :: {$project->GetProject()}{if $actionlocal}/{$actionlocal}{/if}
{/block}

{block name=feeds}
  <link rel="alternate" title="{$project->GetProject()|escape} log (Atom)" href="{geturl project=$project action=atom}" type="application/atom+xml" />
  <link rel="alternate" title="{$project->GetProject()|escape} log (RSS)" href="{geturl project=$project action=rss}" type="application/rss+xml" />
{/block}

{block name=header}
  <a href="{geturl}">{if $homelink}{$homelink}{else}{t}projects{/t}{/if}</a> / 
  <a href="{geturl project=$project}">{$project->GetProject()}</a>
  {if $actionlocal}
     / {$actionlocal}
  {/if}
  {if $enablesearch}
    <form method="get" action="index.php" enctype="application/x-www-form-urlencoded">
      <div class="search">
        <input type="hidden" name="p" value="{$project->GetProject()}" />
        <input type="hidden" name="a" value="search" />
        <input type ="hidden" name="h" value="{if $commit}{$commit->GetHash()}{else}HEAD{/if}" />
        <select name="st">
          <option {if $searchtype == 'commit'}selected="selected"{/if} value="commit">{t}commit{/t}</option>
          <option {if $searchtype == 'author'}selected="selected"{/if} value="author">{t}author{/t}</option>
          <option {if $searchtype == 'committer'}selected="selected"{/if} value="committer">{t}committer{/t}</option>
          {if $filesearch}
            <option {if $searchtype == 'file'}selected="selected"{/if} value="file">{t}file{/t}</option>
          {/if}
        </select> {t}search{/t}: <input type="text" name="s" {if $search}value="{$search}"{/if} />
      </div>
    </form>
  {/if}
{/block}

{block name=footer}
  <div class="page_footer_text">
  {if $project->GetWebsite()}
  <a href="{$project->GetWebsite()}">{$project->GetDescription()}</a>
  {else}
  {$project->GetDescription()}
  {/if}
  </div>
  <a href="{geturl project=$project action=rss}" class="rss_logo">{t}RSS{/t}</a>
  <a href="{geturl project=$project action=atom}" class="rss_logo">{t}Atom{/t}</a>
{/block}
