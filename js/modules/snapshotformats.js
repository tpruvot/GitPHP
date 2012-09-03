/*
 * GitPHP snapshot formats
 *
 * Passes snapshot formats from config to other modules
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(['module'],
	function (module) {
		return module.config().formats || null;
	}
);
