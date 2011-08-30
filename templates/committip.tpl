{*
 *  committip.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit tooltip template
 *
 *  Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}
<div class="commit_tip">
<nobr>{t}author{/t}: {$commit->GetAuthor()} ({$commit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M:%S"})
</nobr>
<br />
<nobr>
{t}committer{/t}: {$commit->GetCommitter()} ({$commit->GetCommitterEpoch()|date_format:"%Y-%m-%d %H:%M:%S"})
</nobr>
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
