/*
 * GitPHP Javascript debug menu
 *
 * Javascript for expandable debug output
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2013 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery"],
  function($) {
    $('span.debug_toggle').click(
      function() {
        $(this).siblings('div.debug_bt').toggle('fast');
      }
    );
  }
);
