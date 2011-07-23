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
{'<?xml version="1.0" encoding="utf-8"?>'}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
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
    <link rel="stylesheet" href="css/gitphp.css" type="text/css" />
    <link rel="stylesheet" href="css/{$stylesheet}" type="text/css" />
    {block name=css}
    {/block}
    {if $javascript}
    <script type="text/javascript">
      var GITPHP_RES_LOADING="{t escape='js'}Loading…{/t}";
      var GITPHP_RES_SNAPSHOT="{t escape='js'}snapshot{/t}";
      var GITPHP_SNAPSHOT_FORMATS = {ldelim}
      {foreach from=$snapshotformats key=format item=extension name=formats}
        "{$format}": "{$extension}"{if !$smarty.foreach.formats.last},{/if}
      {/foreach}
      {rdelim}
    </script>
    <link rel="stylesheet" href="css/ext/jquery.qtip.css" type="text/css" />
    <script type="text/javascript" src="js/ext/jquery-1.4.2.min.js"></script>
    <script type="text/javascript" src="js/ext/jquery.qtip.min.js"></script>
    {if file_exists('js/tooltips.min.js')}
    <script type="text/javascript" src="js/tooltips.min.js"></script>
    {else}
    <script type="text/javascript" src="js/tooltips.js"></script>
    {/if}
    {if file_exists('js/lang.min.js')}
    <script type="text/javascript" src="js/lang.min.js"></script>
    {else}
    <script type="text/javascript" src="js/lang.js"></script>
    {/if}
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
        <form action="{$SCRIPT_NAME}" method="get" id="frmLangSelect">
         <div>
	{foreach from=$requestvars key=var item=val}
	{if $var != "l"}
	<input type="hidden" name="{$var}" value="{$val}" />
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
    	<a href="http://xiphux.com/programming/gitphp/" target="_blank">GitPHP by Chris Han</a>
    </div>
  </body>
</html>
