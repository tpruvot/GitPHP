{*
 *  commitdiffplain.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Plaintext diff template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
From: {$commit->GetAuthor()}
Date: {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}
{if !$file}{* single commit diff *}
{assign var=tag value=$commit->GetContainingTag()}
{if $tag}
X-Git-Tag: {$tag->GetName()}
{/if}
X-Git-Url: {geturl escape=false fullurl=true project=$project action=commitdiff hash=$commit}
Subject: {foreach from=$commit->GetComment() item=line}
{$line}
{/foreach}
{else}{* file filter against two revisions *}
X-Git-Url: {geturl escape=false fullurl=true project=$project action=commitdiff hash=$commit file=$file}
Subject: [PATCH] git diff {$treediff->GetFromHash()}..{$treediff->GetToHash()} -- {$file}
{/if}
---

{foreach from=$treediff item=filediff}
{if !$filediff->IsBinary()}
{$filediff->GetDiff()}
{/if}
{/foreach}
