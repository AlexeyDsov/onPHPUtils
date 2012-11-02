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

	class ObjectNameConverter
	{
		/**
		 * @param \Onphp\IdentifiableObject $object
		 * @return string 
		 */
		public function get(\Onphp\IdentifiableObject $object) {
			if ($object instanceof \Onphp\NamedObject) {
				return "{$object->getName()} [{$object->getId()}]";
			} elseif ($object instanceof \Onphp\Enumeration || $object instanceof \Onphp\Enum) {
				return "{$object->getName()} [{$object->getId()}]";
			}
			
			return $object->getId();
		}
		
		protected function getWithPropertyId(\Onphp\IdentifiableObject $object, $propertyName) {
			\Onphp\Assert::isInstance($object, '\Onphp\Prototyped');
			$property = $object->proto()->getPropertyByName($propertyName);
			/* @var $property \Onphp\LightMetaProperty */
			$getter = $property->getGetter();
			
			return $object->{$getter}()." [{$object->getId()}]";
		}
	}