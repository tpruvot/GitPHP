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
<html>
  <!-- gitphp web interface {$version}, (C) 2006-2011 Christopher Han <xiphux@gmail.com> -->
  <head>
    <title>
    {block name=title}
    {$pagetitle}
    {/block}
    </title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    {block name=feeds}
    {/block}
    {block name=links}
    {/block}
    {if file_exists('css/gitphp.min.css')}
    <link rel="stylesheet" href="{$baseurl}/css/gitphp.min.css" type="text/css" />
    {else}
    <link rel="stylesheet" href="{$baseurl}/css/gitphp.css" type="text/css" />
    {/if}
    {if file_exists("css/$stylesheet.min.css")}
    <link rel="stylesheet" href="{$baseurl}/css/{$stylesheet}.min.css" type="text/css" />
    {else}
    <link rel="stylesheet" href="{$baseurl}/css/{$stylesheet}.css" type="text/css" />
    {/if}
    <link rel="stylesheet" href="{$baseurl}/css/ext/jquery.qtip.min.css" type="text/css" />
    {block name=css}
    {/block}
    {if $javascript}
    {block name=javascript}
    <script src="{$baseurl}/js/ext/require.js"></script>
    {include file='jsconst.tpl'}
    <script type="text/javascript">
    var GitPHPJSPaths = {ldelim}
    {if $googlejs}
	jquery: 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min'
    {else}
	jquery: 'ext/jquery-1.7.1.min'
    {/if}
    {rdelim};
    {block name=javascriptpaths}
    {if file_exists('js/common.min.js')}
    GitPHPJSPaths.common = "common.min";
    {/if}
    {/block}

    var GitPHPJSModules = null;
    {block name=javascriptmodules}
    GitPHPJSModules = ['common'];
    {/block}

    require({ldelim}
    	baseUrl: GitPHP.BaseUrl + 'js',
	paths: GitPHPJSPaths,
	priority: ['jquery']
    {rdelim}, GitPHPJSModules);
    </script>
    {/block}
    {/if}
  </head>
  <body>
    <div class="page_header">
      <a href="http://git-scm.com" title="git homepage" rel="nofollow">
        <img src="{$baseurl}/images/git-logo.png" width="72" height="27" alt="git" class="logo" />
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
      <a href="{geturl}">{if $homelink}{$homelink}{else}{t}projects{/t}{/if}</a> /
      {/block}
    </div>
{block name=main}

{/block}
    <div class="page_footer">
      {block name=footer}
      {/block}
    </div>
    <div class="attr_footer">
    	<a href="http://www.gitphp.org/" target="_blank">GitPHP by Chris Han</a>
    </div>
  </body>
</html>
