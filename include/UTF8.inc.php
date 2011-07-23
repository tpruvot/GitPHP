<?php

function is_utf8($data) {

	return preg_match("/[\xc3\xc4\xc5]/",$data);

}


function utf8_encode_if_required($data) {

	//check if utf8 is needed

	$accents = utf8_decode('/[çéèàâïôîûñß]/'); //utf8_decode could be needed if this source is utf8
	if (preg_match($accents, $data)) {
		$data = utf8_encode($data);
	}

	return $data;
}

?>
