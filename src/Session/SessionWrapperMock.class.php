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

	/**
	 * Dynamic wrapper around session_*() functions.
	**/
	class SessionWrapperMock implements ISessionWrapper
	{
		/* Mock properties */
		private $storage = [];
		private $sessionName = null;
		private $sessionId = null;
		/* End mock properties */

		private $isStarted = false;

		public function start() {
			$this->isStarted = true;
			$this->sessionName = 'sessionName';
			$this->sessionId = rand(1000000, 9999999);
		}

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		/* void */ public function destroy() {
			if (!$this->isStarted)
				throw new SessionWrapperNotStartedException();

			$this->isStarted = false;
		}

		public function flush() {
			return $this->storage = [];
		}

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		/* void */ public function assign($var, $val) {
			if (!self::isStarted())
				throw new SessionWrapperNotStartedException();

			$this->storage[$var] = $val;
		}

		/**
		 * @throws \Onphp\WrongArgumentException
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		public function exist(/* ... */) {
			if (!self::isStarted())
				throw new SessionWrapperNotStartedException();

			if (!func_num_args())
				throw new \Onphp\WrongArgumentException('missing argument(s)');

			foreach (func_get_args() as $arg) {
				if (!isset($this->storage[$arg]))
					return false;
			}

			return true;
		}

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		public function get($var) {
			if (!self::isStarted())
				throw new SessionWrapperNotStartedException();

			return isset($this->storage[$var]) ? $this->storage[$var] : null;
		}

		public function &getAll() {
			return $this->storage;
		}

		/**
		 * @throws \Onphp\WrongArgumentException
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		/* void */ public function drop(/* ... */) {
			if (!self::isStarted())
				throw new SessionWrapperNotStartedException();

			if (!func_num_args())
				throw new \Onphp\WrongArgumentException('missing argument(s)');

			foreach (func_get_args() as $arg)
				unset($this->storage[$arg]);
		}

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		/* void */ public function dropAll() {
			if (!self::isStarted())
				throw new SessionWrapperNotStartedException();

			$this->storage = [];
		}

		public function isStarted() {
			return $this->isStarted;
		}

		/**
		 * assigns to $_SESSION scope variables defined in given array
		**/
		/* void */ public function arrayAssign($scope, $array) {
			\Onphp\Assert::isArray($array);

			foreach ($array as $var) {
				if (isset($scope[$var])) {
					$this->storage[$var] = $scope[$var];
				}
			}
		}

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		public function getName() {
			if (!self::isStarted())
				throw new SessionWrapperNotStartedException();

			return $this->sessionName;
		}

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		**/
		public function getId() {
			if (!self::isStarted())
				throw new SessionWrapperNotStartedException();

			return $this->sessionId;
		}

		/* void */ public function commit() {
			/* */
		}
	}
?>