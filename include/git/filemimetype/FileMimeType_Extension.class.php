<?php
/**
 * File mime type strategy guessing the file extension
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\FileMimeType
 */
class GitPHP_FileMimeType_Extension implements GitPHP_FileMimeTypeStrategy_Interface
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

		$file = $blob->GetName();

		if (empty($file))
			return '';

		$dotpos = strrpos($file, '.');
		if ($dotpos !== FALSE)
			$file = substr($file, $dotpos+1);
		switch ($file) {
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				return 'image/jpeg';
				break;
			case 'gif':
				return 'image/gif';
				break;
			case 'png';
				return 'image/png';
				break;
		}

		return '';
	}

	/**
	 * Gets whether this mimetype strategy is valid
	 *
	 * @return bool true if valid
	 */
	public function Valid()
	{
		return true;
	}

}
