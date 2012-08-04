{*
 * Tree list
 *
 * Tree filelist template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

{foreach from=$tree->GetContents() item=treeitem}
  <tr class="{cycle values="light,dark"}">
    <td class="monospace perms">{$treeitem->GetModeString()}</td>
    {if $treeitem instanceof GitPHP_Blob}
      <td class="filesize">{$treeitem->GetSize()}</td>
      <td></td>
      <td class="list fileName">
        <a href="{geturl project=$project action=blob hash=$treeitem hashbase=$commit file=$treeitem->GetPath()}" class="list">{$treeitem->GetName()}</a>
      </td>
      <td class="link">
        <a href="{geturl project=$project action=blob hash=$treeitem hashbase=$commit file=$treeitem->GetPath()}">{t}blob{/t}</a>
	 | 
	<a href="{geturl project=$project action=history hash=$commit file=$treeitem->GetPath()}">{t}history{/t}</a>
	 | 
	<a href="{geturl project=$project action=blob hash=$treeitem file=$treeitem->GetPath() output=plain}">{t}plain{/t}</a>
      </td>
    {elseif $treeitem instanceof GitPHP_Tree}
      <td class="filesize"></td>
      <td class="expander"></td>
      <td class="list fileName">
        <a href="{geturl project=$project action=tree hash=$treeitem hashbase=$commit file=$treeitem->GetPath()}" class="treeLink">{$treeitem->GetName()}</a>
      </td>
      <td class="link">
        <a href="{geturl project=$project action=tree hash=$treeitem hashbase=$commit file=$treeitem->GetPath()}">{t}tree{/t}</a>
	 | 
	<a href="{geturl project=$project action=snapshot hash=$treeitem file=$treeitem->GetPath()}" class="snapshotTip">{t}snapshot{/t}</a>
      </td>
    {/if}
  </tr>
{/foreach}
