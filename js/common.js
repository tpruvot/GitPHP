/*
 * GitPHP Javascript common loader
 * 
 * Initializes script modules used across all pages
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "module", "modules/getproject", "modules/lang", "modules/tooltip.snapshot", "modules/tooltip.commit", "modules/tooltip.tag", 'modules/loginpopup', 'modernizr'],
	function($, module, project, lang, tooltipSnapshot, tooltipCommit, tooltipTag, loginpopup) {
		$(function() {
			lang($('div.lang_select'));
			tooltipSnapshot($('a.snapshotTip'));
      if (project) {
        tooltipCommit($('a.commitTip'));
        tooltipTag($('a.tagTip'));
      }
      if (!Modernizr.input.autofocus) {
        $('input[autofocus]').filter(':first').focus();
      }
      loginpopup('a.loginLink');
		});
    if (module.config().debug) {
      require(['modules/debug']);
    }
	}
);
