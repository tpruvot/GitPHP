{*
 *  atom.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Atom feed template
 *
 *  Copyright (C) 2010 Christian Weiske <cweiske@cweiske.de>
 *}
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="en">
  <title>{$project->GetProject()}</title>
  <subtitle type="text">{$project->GetProject()} log</subtitle>
  <link href="{geturl fullurl=true project=$project}"/>
  <link rel="self" href="{geturl fullurl=true project=$project action=atom}"/>
  <id>{geturl fullurl=true project=$project}</id>
  {if $log->GetHead()}
  <updated>{$log->GetHead()->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}</updated>
  {/if}

{foreach from=$log item=logitem}
  <entry>
    <id>{geturl fullurl=true project=$project action=commit hash=$logitem}</id>
    <title>{$logitem->GetTitle()|escape:'html'}</title>
    <author>
      <name>{$logitem->GetAuthorName()|escape:'html'}</name>
    </author>
    <published>{$logitem->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}</published>
    <updated>{$logitem->GetCommitterEpoch()|date_format:"%Y-%m-%dT%H:%M:%S+00:00"}</updated>
    <link rel="alternate" href="{geturl fullurl=true project=$project action=commit hash=$logitem}"/>
    <summary>{$logitem->GetTitle()|escape:'html'}</summary>
    <content type="xhtml">
      <div xmlns="http://www.w3.org/1999/xhtml">
        <p>
        {foreach from=$logitem->GetComment() item=line}
          {$line|htmlspecialchars}<br />
        {/foreach}
        </p>
        <ul>
        {foreach from=$logitem->DiffToParent($gitexe) item=diffline}
          <li>{$diffline->GetToFile()|htmlspecialchars}</li>
        {/foreach}
        </ul>
      </div>
    </content>
  </entry>
{/foreach}

</feed>
