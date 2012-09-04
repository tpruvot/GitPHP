{*
 * JSConst
 *
 * Javascript constants template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
<script type="text/javascript">

var GitPHP = GitPHP || {ldelim}{rdelim};

GitPHP.Resources = {ldelim}
	Loading: "{t escape='js'}Loading…{/t}",
	LoadingBlameData: "{t escape='js'}Loading blame data…{/t}",
	Snapshot: "{t escape='js'}snapshot{/t}",
	NoMatchesFound: '{t escape=no}No matches found for "%1"{/t}'
{rdelim};

{if $project}
GitPHP.Project = '{$project->GetProject()}';
{/if}
		
</script>
