<?php
namespace Onphp\Utils;

class ObjectNullGetter extends \Onphp\PrototypedGetter {
	
	public function get($name) {
		return null;
	}
}
?>