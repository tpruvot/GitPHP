{if $filediff->IsBinary()}
{t 1=$filediff->GetFromLabel($file) 2=$filediff->GetToLabel($file)}Binary files %1 and %2 differ{/t}
{else}
{$filediff->GetDiff($file, false)}
{/if}
