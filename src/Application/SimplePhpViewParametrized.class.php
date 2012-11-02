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

	class SimplePhpViewParametrized extends CustomPhpView
	{
		/**
		 * @var \Onphp\Model
		 */
		protected $model = null;
		protected $params = array();

		public function render($model = null) {
			$this->model = $model;
			return parent::render($model);
		}

		/**
		 * @param string $name
		 * @return any
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
		 * @return \Onphp\Utils\SimplePhpViewParametrized
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
		 * @return \Onphp\Utils\SimplePhpViewParametrized
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
		 * Отрисовывает подшаблон $templateName со всеми параметрами модели текущего шаблона
		 *    + добавлением списка дополнительных параметров из второго аргумента
		 * @return null
		 */
		protected function template($templateName, array $params = array()) {
			if (!empty($params)) {
				$model = \Onphp\Model::create()->merge($this->model);
				foreach ($params as $paramName => $paramValue) {
					$model->set($paramName, $paramValue);
				}
				$this->partViewer->view($templateName, $model);
			} else {
				$this->partViewer->view($templateName);
			}
		}

		/**
		 * Сокращенный вызов подшаблона из шаблона, для сокращенного создания модели
		 *    можно передовать не объект Model, а ассоциативный массив
		 * @param string $templateName
		 * @param Model|array $model
		 */
		protected function view($templateName, /* Model */ $model = null) {
			if ($model && is_array($model)) {
				$model = $this->array2Model($model);
			} elseif ($model) {
				\Onphp\Assert::isInstance($model, '\Onphp\Model', '$model must be instance of Model or array or null');
			}
			$this->partViewer->view($templateName, $model);
		}

		/**
		 * Короткий вызов для htmlspecialchars в шаблоне + использование sprintf при передаче больше одного аргумента
		 * @param string $value
		 * @return string
		 */
		protected function escape($value/*,  sprintf params */) {
			if (func_num_args() > 1) {
				$value = call_user_func_array('sprintf', func_get_args());
			}
			return htmlspecialchars($value);
		}
		
		/**
		 * @param array $array
		 * @return \Onphp\Model
		 */
		private function array2Model(array $array) {
			$model = \Onphp\Model::create();
			foreach ($array as $key => $value) {
				$model->set($key, $value);
			}
			
			return $model;
		}
	}
?>