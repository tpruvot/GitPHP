{*
 * Path
 *
 * Path template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
<div class="page_path">
	{if $pathobject}
		{assign var=pathobjectcommit value=$pathobject->GetCommit()}
		{assign var=pathobjecttree value=$pathobjectcommit->GetTree()}
		<a href="{geturl project=$project action=tree hashbase=$pathobjectcommit hash=$pathobjecttree}"><strong>[{$project->GetProject()}]</strong></a> / 
		{foreach from=$pathobject->GetPathTree() item=pathtreepiece}
			<a href="{geturl project=$project action=tree hashbase=$pathobjectcommit hash=$pathtreepiece file=$pathtreepiece->GetPath()}"><strong>{$pathtreepiece->GetName()|escape}</strong></a> / 
		{/foreach}
		{if $pathobject instanceof GitPHP_Blob}
			{if $target == 'blobplain'}
				<a href="{geturl project=$project action=blob hash=$pathobject file=$pathobject->GetPath() output=plain}"><strong>{$pathobject->GetName()|escape}</strong></a>
			{elseif $target == 'blob'}
				<a href="{geturl project=$project action=blob hash=$pathobject hashbase=$pathobjectcommit file=$pathobject->GetPath()}"><strong>{$pathobject->GetName()|escape}</strong></a>
			{else}
				<strong>{$pathobject->GetName()|escape}</strong>
			{/if}
		{elseif $pathobject->GetName()}
			{if $target == 'tree'}
				<a href="{geturl project=$project action=tree hashbase=$pathobjectcommit hash=$pathobject file=$pathobject->GetPath()}"><strong>{$pathobject->GetName()|escape}</strong></a> / 
			{else}
				<strong>{$pathobject->GetName()|escape}</strong> / 
			{/if}
		{/if}
	{else}
		&nbsp;
	{/if}
</div>
