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

	class MultiPrefixPhpViewResolverParametrized extends \Onphp\MultiPrefixPhpViewResolver {

		protected $params = array();

		/**
		 * @return \Onphp\Utils\MultiPrefixPhpViewResolverParametrized
		**/
		public static function create() {
			return new self;
		}

		/**
		 * @param string $name
		 * @return \Onphp\Utils\MultiPrefixPhpViewResolverParametrized
		 */
		public function get($name) {
			if (!$this->has($name)) {
				throw new \Onphp\MissingElementException("not setted value with name '$name'");
			}
			return $this->params[$name];
		}

		/**
		 * @param string $name
		 * @param mixed $value
		 * @return \Onphp\Utils\MultiPrefixPhpViewResolverParametrized
		 */
		public function set($name, $value) {
			if ($this->has($name)) {
				throw new \Onphp\WrongStateException("value with name '$name' already setted ");
			}
			$this->params[$name] = $value;
			return $this;
		}

		/**
		 * @param string $name
		 * @return \Onphp\Utils\MultiPrefixPhpViewResolverParametrized
		 */
		public function drop($name) {
			if (!$this->has($name)) {
				throw new \Onphp\MissingElementException("not setted value with name '$name'");
			}
			unset($this->params[$name]);
			return $this;
		}

		/**
		 * @param string $name
		 * @return boolean
		 */
		public function has($name) {
			\Onphp\Assert::isScalar($name);
			return array_key_exists($name, $this->params);
		}

		/**
		 * @return \Onphp\View
		**/
		protected function makeView($path) {
			$view = parent::makeView($path);
			if ($view instanceof SimplePhpViewParametrized) {
				foreach ($this->params as $key => $value) {
					$view->set($key, $value);
				}
			}
			return $view;
		}
	}
?>