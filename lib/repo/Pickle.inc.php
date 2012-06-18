<?php
	/**
	 * TODO: find python-pickle to PHP unserialize class :/
	 *       this func works for a basic 'ini' config file
	 *       by tanguy.pruvot at gmail.com
	 *
	 * convert python pickle to php data
	 *
	 * $repopickle_config : $project->GetPath() . '/.repopickle_config'
	 */
	function ReadRepoPickleConfig($repopickle_config)
	{
		$config = file_get_contents($repopickle_config);

		if (substr($config,0,1) != "\x80") { // PROTO 2
			return array();
		}

		$f = explode("\x71",$config); // BINPUT (followed by 1-byte index)

		array_shift($f); //skip first
		array_pop($f);   //  and last items

		$secure = 255;
		$keys = array();
		$values = array();
		foreach ($f as $k => $item) {

			$a = unpack('C*', $item);
			$n = array_shift($a);
			$op = reset($a); // get first item

			$next_is_value = ($op == 0x5D); // EMPTY_LIST ie "=>"
			if ($next_is_value) {
				$key = $k - 1;
				continue;
			}
			$t = array_shift($a);

		append_remain:
			if ($t == 0x61 or $t == 0x28) { // APPEND or MARK
				$t = array_shift($a);
				$item =substr($item, 1);
			}

			$new = $item;
			if ($t == 0x55) { //'U' SHORT_BINSTRING (<= 255)
				$sz = array_shift($a);
				$new = substr($item,3,$sz);
				$remain = substr($item, 3+$sz);
			}

			if (isset($key)) {
				$values[$key] = $new;
				unset($key);
			} else {
				$keys[$k] = $new;
			}

			if (!empty($remain) && $secure > 0) {
				$item = $remain;
				$secure--;
				goto append_remain;
			}
		}
		$repo_config = array_combine($keys, $values);
		return $repo_config;
	}
?>