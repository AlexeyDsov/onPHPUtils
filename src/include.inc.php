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

namespace Onphp\Utils;

use Onphp\AutoloaderClassPathCache;
use Onphp\NamespaceResolverOnPHP;

$autoload = function() {
	$newAutoload = function () {
		$onphpUtilsSrc = dirname(__FILE__).DIRECTORY_SEPARATOR;

		AutoloaderClassPathCache::create()
			->setNamespaceResolver(NamespaceResolverOnPHP::create())
			->addPaths(
				[
					$onphpUtilsSrc.'Access',
					$onphpUtilsSrc.'Application',
					$onphpUtilsSrc.'EntityProto',
					$onphpUtilsSrc.'ListMakerHelper',
					$onphpUtilsSrc.'Permission',
					$onphpUtilsSrc.'ServiceLocator',
					$onphpUtilsSrc.'Session',
					$onphpUtilsSrc.'ToolkitFlow',
					$onphpUtilsSrc.'Translator',
					$onphpUtilsSrc.'Utils',
				],
				'Onphp\Utils'
			)
			->register();
	};

	$newAutoload();
};
$autoload();

?>