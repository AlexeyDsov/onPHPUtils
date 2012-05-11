<?php
/***************************************************************************
 *   Copyright (C) 2012 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

define('PATH_ONPHP_UTILS_SRC', dirname(__FILE__).DS);

ini_set(
	'include_path',
	get_include_path()
	. join(
		PATH_SEPARATOR,
		array(
			PATH_ONPHP_UTILS_SRC.'Access',
			PATH_ONPHP_UTILS_SRC.'Application',
			PATH_ONPHP_UTILS_SRC.'EntityProto',
			PATH_ONPHP_UTILS_SRC.'ListMakerHelper',
			PATH_ONPHP_UTILS_SRC.'ServiceLocator',
			PATH_ONPHP_UTILS_SRC.'ToolkitFlow',
			PATH_ONPHP_UTILS_SRC.'Translator',
			PATH_ONPHP_UTILS_SRC.'Utils',
		)
	)
	. PATH_SEPARATOR
);

?>