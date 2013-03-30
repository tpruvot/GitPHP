{*
 * Main
 *
 * Main page template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}
<!DOCTYPE html>
<html lang="{$currentprimarylocale}">
  <!-- gitphp web interface {$version}, (C) 2006-2013 Christopher Han <xiphux@gmail.com>, Tanguy Pruvot <tpruvot@github> -->
  <head>
    <title>
    {block name=title}
    {$pagetitle}
    {/block}
    </title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    {block name=feeds}
    {/block}
    {if file_exists('css/gitphp.min.css')}
    <link rel="stylesheet" href="css/gitphp.min.css" type="text/css" />
    {else}
    <link rel="stylesheet" href="css/gitphp.css" type="text/css" />
    {/if}
    {if file_exists("css/$stylesheet.min.css")}
    <link rel="stylesheet" href="css/{$stylesheet}.min.css" type="text/css" />
    {else}
    <link rel="stylesheet" href="css/{$stylesheet}.css" type="text/css" />
    {/if}
    {block name=css}
    {/block}
    <link rel="stylesheet" href="css/ext/jquery.qtip.css" type="text/css" />
    {if $extracss}
    <style type="text/css">
    {$extracss}
    </style>
    {/if}
    {if $javascript}
    <script type="text/javascript">
	var GitPHPJSPaths = {
		jquery: [
		{if $googlejs}
		'https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min',
		{/if}
		'ext/jquery.min'
		],
		qtip: 'ext/jquery.qtip'
	};

	{block name=javascriptpaths}
	{/block}

	var reqcfg = {
		baseUrl: '{$baseurl}/js',
		paths: GitPHPJSPaths,
		config: {
			'modules/snapshotformats': {
				formats: {
					{foreach from=$snapshotformats key=format item=extension name=formats}
					"{$format}": "{$extension}"{if !$smarty.foreach.formats.last},{/if}
					{/foreach}
				}
			},
			{if $project}
			'modules/getproject': {
				project: '{$project->GetProject()}'
			},
			{/if}
			'modules/geturl': {
				baseurl: '{$baseurl}/'
			},
			'modules/resources': {
				resources: {
					Loading: "{t escape='js'}Loading…{/t}",
					LoadingBlameData: "{t escape='js'}Loading blame data…{/t}",
					Snapshot: "{t escape='js'}snapshot{/t}",
					NoMatchesFound: '{t escape=no}No matches found for "%1"{/t}'
				}
			}
		}
	};

	{if file_exists('js/common.min.js')}
	reqcfg.paths.common = "common.min";
	{/if}
	reqcfg.deps = ['common'];

	var GitPHPJSModules = null;
	{block name=javascriptmodules}
	{/block}

    </script>
    <script type="text/javascript" src="{$baseurl}/js/ext/require.js"></script>
    <script type="text/javascript">
	reqcfg.deps = reqcfg.deps.concat(GitPHPJSModules);
	require.config(reqcfg);
    </script>
    {block name=javascript}
    {/block}
    {/if}
  </head>
  <body>
    <div class="page_header">
      <a href="http://git-scm.com" title="git homepage">
        <img src="images/git-logo.png" width="72" height="27" alt="git" class="logo" />
      </a>
      {if $supportedlocales}
      <div class="lang_select">
        <form action="{$requesturl}" method="get" id="frmLangSelect">
         <div>
	{foreach from=$requestvars key=var item=val}
	{if $var != "l"}
	<input type="hidden" name="{$var}" value="{$val|escape}" />
	{/if}
	{/foreach}
	<label for="selLang">{t}language:{/t}</label>
	<select name="l" id="selLang">
	  {foreach from=$supportedlocales key=locale item=language}
	    <option {if $locale == $currentlocale}selected="selected"{/if} value="{$locale}">{if $language}{$language} ({$locale}){else}{$locale}{/if}</option>
	  {/foreach}
	</select>
	<input type="submit" value="{t}set{/t}" id="btnLangSet" />
         </div>
	</form>
      </div>
      {/if}
      {block name=header}
      <a href="index.php">{if $homelink}{$homelink}{else}{t}projects{/t}{/if}</a> /
      {/block}
    </div>
{block name=main}

{/block}
    <div class="page_footer">
      {block name=footer}
      {/block}
    </div>
    <div class="attr_footer">
        <a href="https://github.com/tpruvot/GitPHP" target="_blank">GitPHP branch by Tanguy Pruvot</a> based on <a href="http://source.gitphp.org/">original version by Chris Han</a>
    </div>
{if $debug}
    <div class="debug_footer">
    <!-- keep unclosed for debug log -->
{else}
  </body>
</html>
{/if}
