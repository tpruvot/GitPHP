<?php
class GitPHP_Mime
{

	/**
	 * Get the file mimetype using the file extension
	 *
	 * @return string mimetype
	 */
	public static function FileMime_Extension($file)
	{
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
	 * Get the file mimetype
	 *
	 * @param boolean $short true to only the type group
	 * @return string mime
	 */
	public static function FileMime($file, $short = false)
	{
		if (empty($mime))
				$mime = self::FileMime_Extension($file);

		if ((!empty($mime)) && $short) {
				$mime = strtok($mime, '/');
		}

		return $mime;
	}

}
?>
