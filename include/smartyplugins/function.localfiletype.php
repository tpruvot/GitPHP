<?php
/**
 * Smarty function to get a localized file type
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Smarty
 *
 * @param array $params param array
 * @param Smarty_Internal_Template $template smarty template
 */
function smarty_function_localfiletype($params, Smarty_Internal_Template $template)
{
	if (empty($params['type'])) {
		trigger_error("localfiletype: missing 'type' parameter");
		return;
	}

	$type = $params['type'];

	$output = null;

	switch ($type) {
		case GitPHP_FilesystemObject::FileType:
			$output = __('file');
			break;
		case GitPHP_FilesystemObject::SymlinkType:
			$output = __('symlink');
			break;
		case GitPHP_FilesystemObject::DirectoryType:
			$output = __('directory');
			break;
		default:
			$output = __('unknown');
			break;
	}

	if (!empty($params['assign']))
		$template->assign($params['assign'], $output);
	else
		return $output;
}
