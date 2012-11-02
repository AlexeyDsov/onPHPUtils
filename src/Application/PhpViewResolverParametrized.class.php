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

	class PhpViewResolverParametrized implements \Onphp\ViewResolver {
		
		protected $params = array();

		private $prefix		= null;
		private $postfix	= null;

		/**
		 * @return \Onphp\Utils\PhpViewResolverParametrized
		**/
		public static function create($prefix = null, $postfix = null) {
			return new self($prefix, $postfix);
		}

		public function __construct($prefix = null, $postfix = null) {
			$this->prefix	= $prefix;
			$this->postfix	= $postfix;
		}

		public function getPrefix() {
			return $this->prefix;
		}

		/**
		 * @return \Onphp\Utils\PhpViewResolverParametrized
		**/
		public function setPrefix($prefix) {
			$this->prefix = $prefix;

			return $this;
		}

		public function getPostfix() {
			return $this->postfix;
		}

		/**
		 * @return \Onphp\Utils\PhpViewResolverParametrized
		**/
		public function setPostfix($postfix) {
			$this->postfix = $postfix;

			return $this;
		}

		/**
		 * @param string $name
		 * @return \Onphp\Utils\PhpViewResolverParametrized
		 */
		public function get($name) {
			if (!$this->has($name)) {
				throw new \Onphp\MissingElementException("not setted value with name '$name'");
			}
			return $this->params[$name];
		}

		/**
		 * @param string $name
		 * @param any $value
		 * @return \Onphp\Utils\PhpViewResolverParametrized
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
		 * @return \Onphp\Utils\PhpViewResolverParametrized
		 */
		public function drop($name) {
			if (!$this->has($name)) {
				throw new \Onphp\MissingElementException("not setted value with name '$name'");
			}
			unset($this->params[$name]);
			return $this;
		}

		/**
		 * @param type $name
		 * @return boolean
		 */
		public function has($name) {
			\Onphp\Assert::isScalar($name);
			return array_key_exists($name, $this->params);
		}

		/**
		 * @return \Onphp\Utils\SimplePhpViewParametrized
		**/
		public function resolveViewName($viewName) {
			$view = new SimplePhpViewParametrized(
				$this->prefix.$viewName.$this->postfix,
				$this
			);
			foreach ($this->params as $name => $value) {
				$view->set($name, $value);
			}
			return $view;
		}

		public function viewExists($viewName) {
			return is_readable($this->prefix.$viewName.$this->postfix);
		}
	}
?>