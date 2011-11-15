<?php
/**
 * FileMime_Extension
 *
 * Get the file mimetype using the file extension
 *
 * @access private
 * @return string mimetype
 */
function FileMime_Extension($file)
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
 * FileMime
 *
 * Get the file mimetype
 *
 * @access public
 * @param boolean $short true to only the type group
 * @return string mime
 */
function FileMime($file, $short = false)
{
		if (empty($mime))
				$mime = FileMime_Extension($file);

		if ((!empty($mime)) && $short) {
				$mime = strtok($mime, '/');
		}

		return $mime;
}

?>
