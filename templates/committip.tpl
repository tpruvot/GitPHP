{*
 *  committip.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit tooltip template
 *
 *  Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}
<div>
{t}author{/t}: {$commit->GetAuthorName()} (<time datetime="{$commit->GetAuthorEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{$commit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M:%S"}</time>)
<br />
{t}committer{/t}: {$commit->GetCommitterName()} (<time datetime="{$commit->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}">{$commit->GetCommitterEpoch()|date_format:"%Y-%m-%d %H:%M:%S"}</time>)
<br /><br />
{foreach from=$commit->GetComment() item=line}
{if strncasecmp(trim($line),'Signed-off-by:',14) == 0}
<span class="signedOffBy">{$line|escape}</span>
{else}
{$line|escape}
{/if}
<br />
{/foreach}
</div>
