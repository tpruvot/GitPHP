<?php
/**
 * File mime type strategy using Fileinfo
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\FileMimeType
 */
class GitPHP_FileMimeType_Fileinfo implements GitPHP_FileMimeTypeStrategy_Interface
{
	/**
	 * Gets the mime type for a blob
	 *
	 * @param GitPHP_Blob $blob blob
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
	 * Gets whether this mimetype strategy is valid
	 *
	 * @return bool true if valid
	 */
	public function Valid()
	{
		return function_exists('finfo_buffer');
	}

}
