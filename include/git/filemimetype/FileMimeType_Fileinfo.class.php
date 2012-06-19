<?php
/**
 * GitPHP FileMimeType_Fileinfo
 *
 * File mime type strategy using Fileinfo
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * FileMimeType_Fileinfo class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileMimeType_Fileinfo implements GitPHP_FileMimeTypeStrategy_Interface
{
	/**
	 * GetMime
	 *
	 * Gets the mime type for a blob
	 *
	 * @access public
	 * @param mixed $blob blob
	 * @return string mime type
	 */
	public function GetMime($blob)
	{
		if (!$blob)
			return false;

		$data = $blob->GetData();
		if (empty($data))
			return false;

		$mime = '';
		$finfo = @finfo_open(FILEINFO_MIME, GitPHP_Config::GetInstance()->GetValue('magicdb', null));
		if ($finfo) {
			$mime = finfo_buffer($finfo, $data, FILEINFO_MIME);
			if ($mime && strpos($mime, '/')) {
				if (strpos($mime, ';')) {
					$mime = strtok($mime, ';');
				}
			}
			finfo_close($finfo);
		}

		return $mime;
	}

	/**
	 * Valid
	 *
	 * Gets whether this mimetype strategy is valid
	 *
	 * @access public
	 * @return bool true if valid
	 */
	public function Valid()
	{
		return function_exists('finfo_buffer');
	}

}
