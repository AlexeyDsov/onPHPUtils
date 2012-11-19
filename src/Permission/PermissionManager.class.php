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

	use \Onphp\Assert;
	use \Onphp\ClassUtils;
	use \Onphp\WrongStateException;

	class PermissionManager {

		private $checkers = [];

		/**
		 * Статическое создание объекта класса
		 * @return \Onphp\Utils\PermissionManager
		 */
		public static function create()
		{
			return new static;
		}

		/**
		 * @param string $name
		 * @param mixed $permissionChecker
		 * @return \Onphp\Utils\PermissionManager
		 * @throws WrongStateException
		 */
		public function add($name, $permissionChecker)
		{
			if (isset($this->checkers[$name])) {
				throw new WrongStateException('PermissionChecker with name "'.$name.'" already setted');
			}
			Assert::isTrue(
				$permissionChecker instanceof PermissionChecker
				|| $permissionChecker instanceof PermissionClassChecker
			);
			$this->checkers[$name] = $permissionChecker;

			return $this;
		}

		public function get($name)
		{
			if (isset($this->checkers[$name])) {
				return $this->checkers[$name];
			}
		}

		public function drop($name)
		{
			unset($this->checkers[$name]);
		}

		/**
		 * @param \Onphp\Utils\IPermissionUser $user
		 * @param string $method
		 * @param mixed $object can be classname or IdentifiableObject
		 * @return bool
		 */
		public function hasPermission(IPermissionUser $user, $method, $object) {
			return $this->getPermission($user, $method, $object)->isAllowed();
		}

		/**
		 * @param \Onphp\Utils\IPermissionUser $user
		 * @param string $method
		 * @param string $className
		 * @return boolean
		 */
		public function hasPermissionToClass(IPermissionUser $user, $method, $className)
		{
			return $this->getPermissionToClass($user, $method, $className)->isAllowed();
		}

		/**
		 * @param IPermissionUser $user
		 * @param string $method
		 * @param mixed $object
		 * @return Permission
		 */
		public function getPermission(IPermissionUser $user, $method, $object)
		{
			if (is_object($object)) {
				foreach ($this->checkers as $checker) {
					if ($checker instanceof PermissionChecker) {
						$result = $checker->hasPermission($user, $method, $object);
						if ($result === null) {
							continue;
						}
						return $this->formatPermission($result);
					}
				}
			}

			$className = ClassUtils::normalClassName($object);
			return $this->getPermissionToClass($user, $method, $className);
		}

		/**
		 * @param IPermissionUser $user
		 * @param string $method
		 * @param string $className
		 * @return Permission
		 */
		public function getPermissionToClass(IPermissionUser $user, $method, $className)
		{
			$className = $this->convertObjectName($className);
			foreach ($this->checkers as $checker) {
				if ($checker instanceof PermissionClassChecker) {
					$result = $checker->hasPermissionClass($user, $method, $className);
					if ($result === null) {
						continue;
					}
					return $this->formatPermission($result);
				}
			}
			return new PermissionSimple(false);
		}

		/**
		 * @param string $objectName
		 */
		protected function convertObjectName($objectName)
		{
			return $objectName;
		}

		/**
		 * @param mixed $result
		 * @return PermissionSimple
		 */
		protected function formatPermission($result)
		{
			if ($result === true || $result === false) {
				$result = new PermissionSimple($result);
			} elseif (!is_object($result)) {
				Assert::isUnreachable('Getted result not true, not false, not object');
			}
			Assert::isInstance($result, '\Onphp\Utils\Permission');
			return $result;
		}
	}