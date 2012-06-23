{*
 *  projectmessage.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project-specific arning/error message template
 *
 *  Copyright (C) 2012 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

<div class="page_nav">
{include file='nav.tpl' current='summary'}
<br /><br />
</div>

{include file='title.tpl' target='summary'}

<div class="message {if $error}error{/if}">{$message|escape}</div>

{/block}
