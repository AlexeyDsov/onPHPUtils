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

	/**
	 * Interface of dynamic wrapper around session_*() functions.
	**/
	namespace Onphp\Utils;

	interface ISessionWrapper
	{
		/**
		 * @return void
		 */
		public function start();

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return void
		**/
		public function destroy();

		/**
		 * @return void
		**/
		public function flush();

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return void
		**/
		public function assign($var, $val);

		/**
		 * @throws \Onphp\WrongArgumentException
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return boolean
		**/
		public function exist(/* ... */);

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return any
		**/
		public function get($var);

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return array
		**/
		public function &getAll();

		/**
		 * @throws \Onphp\WrongArgumentException
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return void
		**/
		public function drop(/* ... */);

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return void
		**/
		public function dropAll();

		/**
		 * @return boolean
		 */
		public function isStarted();

		/**
		 * assigns to $_SESSION scope variables defined in given array
		 * @return void
		**/
		public function arrayAssign($scope, $array);

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return string
		**/
		public function getName();

		/**
		 * @throws \Onphp\Utils\SessionWrapperNotStartedException
		 * @return string
		**/
		public function getId();
	}
?>