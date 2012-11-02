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

	class PermissionManager {

		/**
		 * Статическое создание объекта класса
		 * @return \Onphp\Utils\PermissionManager
		 */
		public static function create() {
			return new self;
		}

		/**
		 * Возвращает признак
		 * @param \Onphp\Utils\IPermissionUser $user
		 * @param string $method
		 * @param mixed $object can be classname or IdentifiableObject
		 * @return bool
		 */
		public function hasPermission(IPermissionUser $user, $method, $object) {
			return $this->hasPermissionToClass($user, $method, $object);
		}
		
		public function hasPermissionToClass(IPermissionUser $user, $method, $object) {
			return $user->hasAction($this->getObjectName($object).'.'.$method);
		}
		
		/**
		 * @param mixed $object string|object
		 */
		final public function getObjectName($object) {
			\Onphp\Assert::isTrue(is_object($object) || is_string($object), '$object is not an object or string');
			if (is_object($object)) {
				\Onphp\Assert::isInstance($object, '\Onphp\IdentifiableObject', '$object is not IdentifiableObject');
			}
			
			return $this->convertObjectName(is_object($object) ? get_class($object) : $object);
		}
		
		/**
		 * @param string $objectName 
		 */
		protected function convertObjectName($objectName) {
			return $objectName;
		}
	}