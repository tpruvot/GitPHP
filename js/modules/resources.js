/*
 * GitPHP resources
 *
 * Passes resource strings from config to other modules
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(['module'],
	function (module) {
		return module.config().resources;
	}
);
