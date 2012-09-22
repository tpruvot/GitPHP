{*
 *  rss.tpl
 *  gitphp: A PHP git repository browser
 *  Component: RSS template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title>{$project->GetProject()}</title>
    <link>{geturl fullurl=true project=$project}</link>
    <description>{$project->GetProject()} log</description>
    <language>en</language>

    {foreach from=$log item=logitem}
      <item>
        <title>{$logitem->GetCommitterEpoch()|date_format:"%d %b %R"} - {$logitem->GetTitle()|escape:'html'}</title>
        <author>{$logitem->GetAuthor()|escape:'html'}</author>
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
