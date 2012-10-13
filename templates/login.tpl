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
<div class="loginerror">
{$loginerror}
</div>
{/if}
<form method="post" action="{geturl action=login}">
<table>
  <tr>
    <td><label for="username">Username:</label></td>
    <td><input type="text" name="username" {if $username}value="{$username}"{/if} /></td>
  </tr>
  <tr>
    <td><label for="password">Password:</label></td>
    <td><input type="password" name="password" /></td>
  </tr>
  <tr>
    <td></td>
    <td><input type="submit" value="Login" /></td>
  </tr>
</table>
</form>
{/block}
