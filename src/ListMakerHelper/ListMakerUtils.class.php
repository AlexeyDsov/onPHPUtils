<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	namespace Onphp\Utils;

	class ListMakerUtils
	{
		/**
		 * @return \Onphp\LightMetaProperty
		 */
		public static function getPropertyByName($objectLink, \Onphp\AbstractProtoClass $proto)
		{
			\Onphp\Assert::isString($objectLink);
			$pathParts = explode('.', $objectLink);
			$length = count($pathParts);
			if ($length == 0) {
				throw new \Onphp\WrongStateException('Object link must have minimum one object name on chain');
			}

			for ($i = 0; $i < $length; $i++) {
				if (!$proto->isPropertyExists($pathParts[$i])) {
					return null;
				}

				$property = $proto->getPropertyByName($pathParts[$i]);

				if ($i+1 < $length) {
					$className = $property->getClassName();
					if ($className === null) {
						return null;
					}
					$proto = \Onphp\ClassUtils::callStaticMethod($className.'::proto');
				}
			}

			return $property;
		}
	}
?>