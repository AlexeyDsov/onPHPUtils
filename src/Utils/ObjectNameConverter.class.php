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

	class ObjectNameConverter
	{
		/**
		 * @param IdentifiableObject $object
		 * @return string 
		 */
		public function get(IdentifiableObject $object) {
			if ($object instanceof NamedObject) {
				return "{$object->getName()} [{$object->getId()}]";
			} elseif ($object instanceof Enumeration || $object instanceof Enum) {
				return "{$object->getName()} [{$object->getId()}]";
			}
			
			return $object->getId();
		}
		
		protected function getWithPropertyId(IdentifiableObject $object, $propertyName) {
			Assert::isInstance($object, 'Prototyped');
			$property = $object->proto()->getPropertyByName($propertyName);
			/* @var $property LightMetaProperty */
			$getter = $property->getGetter();
			
			return $object->{$getter}()." [{$object->getId()}]";
		}
	}