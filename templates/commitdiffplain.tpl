{*
 *  commitdiffplain.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Plaintext diff template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
From: {$commit->GetAuthor()}
Date: {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}
{assign var=tag value=$commit->GetContainingTag()}
{if $tag}
X-Git-Tag: {$tag->GetName()}
{/if}
X-Git-Url: {scripturl}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}
Subject: {foreach from=$commit->GetComment() item=line}
{$line}
{/foreach}
---

{foreach from=$treediff item=filediff}
{$filediff->GetDiff()}
{/foreach}
