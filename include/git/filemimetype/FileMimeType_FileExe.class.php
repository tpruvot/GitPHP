<?php
/**
 * File mime type strategy using file executable
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\FileMimeType
 */
class GitPHP_FileMimeType_FileExe implements GitPHP_FileMimeTypeStrategy_Interface
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

		$descspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w')
		);

		$proc = proc_open('file -b --mime -', $descspec, $pipes);
		if (is_resource($proc)) {
			fwrite($pipes[0], $data);
			fclose($pipes[0]);
			$mime = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);

			if ($mime && strpos($mime, '/')) {
				if (strpos($mime, ';')) {
					$mime = strtok($mime, ';');
				}
				return $mime;
			}
		}

		return false;
	}

	/**
	 * Gets whether this mimetype strategy is valid
	 *
	 * @return bool true if valid
	 */
	public function Valid()
	{
		return !GitPHP_Util::IsWindows();
	}

}
