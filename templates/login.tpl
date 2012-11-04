{*
 * Login template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
{extends file='main.tpl'}

{block name=header}
  <a href="{geturl}">{if $homelink}{$homelink}{else}{t}projects{/t}{/if}</a> / {if $actionlocal}{$actionlocal}{/if}
{/block}

{block name=main}
{if $loginerror}
<div class="loginError error">
{$loginerror}
</div>
{/if}
<div class="loginForm">
  <form method="post" action="{geturl action=login}">
    <div class="field">
      <label for="username">{t}username:{/t}</label>
      <input type="text" name="username" id="username" {if $username}value="{$username}"{/if} autofocus />
    </div>
    <div class="field">
      <label for="password">{t}password:{/t}</label>
      <input type="password" name="password" id="password" />
    </div>
    {if $redirect}
    <input type="hidden" name="redirect" value="{$redirect|escape}" />
    {/if}
    <div class="submit">
    <input type="submit" value="{t}login{/t}" />
    </div>
  </form>
</div>
{/block}
