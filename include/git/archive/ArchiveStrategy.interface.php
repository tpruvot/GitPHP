<?php
/**
 * Interface for archive creation strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Archive
 */
interface GitPHP_ArchiveStrategy_Interface
{
	/**
	 * Set executable for this archive
	 *
	 * @param GitPHP_GitExe $exe git exe
	 */
	public function SetExe($exe);

	/**
	 * Open a descriptor for this archive
	 *
	 * @param GitPHP_Archive $archive archive
	 * @return boolean true on success
	 */
	public function Open($archive);

	/**
	 * Read a chunk of the archive data
	 *
	 * @param int $size size of data to read
	 * @return string|boolean archive data or false
	 */
	public function Read($size = 1048576);

	/**
	 * Close archive descriptor
	 */
	public function Close();

	/**
	 * Gets the file extension for this format
	 *
	 * @return string extension
	 */
	public function Extension();

	/**
	 * Gets the mime type for this format
	 *
	 * @return string mime type
	 */
	public function MimeType();

	/**
	 * Gets whether this archiver is valid
	 *
	 * @return boolean true if valid
	 */
	public function Valid();
}
