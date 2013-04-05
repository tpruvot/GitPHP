{*
 *  opml.tpl
 *  gitphp: A PHP git repository browser
 *  Component: OPML template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<opml version="1.0">
  <head>
    <title>{$pagetitle} OPML Export</title>
  </head>
  <body>
    <outline text="git Atom feeds">

      {foreach from=$projectlist item=proj}
      <outline type="rss" text="{$proj->GetProject()}" title="{$proj->GetProject()|escape}" xmlUrl="{geturl fullurl=true project=$proj action=atom}" htmlUrl="{geturl fullurl=true project=$proj}" />

      {/foreach}
    </outline>
  </body>
</opml>
