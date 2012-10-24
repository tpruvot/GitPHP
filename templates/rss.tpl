{*
 *  rss.tpl
 *  gitphp: A PHP git repository browser
 *  Component: RSS template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{$project->GetProject()}</title>
    <link>{geturl fullurl=true project=$project}</link>
    <atom:link rel="self" href="{geturl fullurl=true project=$project action=rss}" type="application/rss+xml" />
    <description>{$project->GetProject()} log</description>
    <language>en</language>

    {foreach from=$log item=logitem}
      <item>
        <title>{$logitem->GetCommitterEpoch()|date_format:"%d %b %R"} - {$logitem->GetTitle()|escape:'html'}</title>
        <author>{$logitem->GetAuthorEmail()|escape:'html'} ({$logitem->GetAuthorName()|escape:'html'})</author>
        <pubDate>{$logitem->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}</pubDate>
        <guid isPermaLink="true">{geturl fullurl=true project=$project action=commit hash=$logitem}</guid>
        <link>{geturl fullurl=true project=$project action=commit hash=$logitem}</link>
        <description>{$logitem->GetTitle()|escape:'html'}</description>
        <content:encoded>
          <![CDATA[
          {foreach from=$logitem->GetComment() item=line}
            {$line}<br />
          {/foreach}
          {foreach from=$logitem->DiffToParent($gitexe) item=diffline}
            {$diffline->GetToFile()}<br />
          {/foreach}
          ]]>
        </content:encoded>
      </item>
    {/foreach}

  </channel>
</rss>
