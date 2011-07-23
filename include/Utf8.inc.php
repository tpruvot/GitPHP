<?php

function is_utf8($data) {

  return preg_match("/[\xc3\xc4\xc5]/",$data);

}

?>
