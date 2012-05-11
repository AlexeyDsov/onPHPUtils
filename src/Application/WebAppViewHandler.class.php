<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	class WebAppViewHandler implements InterceptingChainHandler
	{
		/**
		 * Templater name
		 * @var string
		 */
		const VIEW_CLASS_NAME_DEFAULT = 'SimplePhpView';
		
		/**
		 * HTTP заголовки ответа
		 * @var array<assoc>
		 */
		private $headers = array();
		
		/**
		 * @return WebAppViewHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * Выполняем ренедринг шаблона по ModelAndView из $chain'а
		 * @param InterceptingChain $chain
		 * @return WebAppViewHandler
		 */
		public function run(InterceptingChain $chain)
		{
			$view	= $chain->getMav()->getView();
			$model 	= $chain->getMav()->getModel();

			if (!$view instanceof View) {
				$viewName = $view;
				$viewResolver = $this->getViewResolver($chain, $model);
				$view = $viewResolver->resolveViewName($viewName);
			}

			foreach ($this->headers as $name => $value) {
				header("$name: $value");
			}

			if ($chain->getMav()->viewIsNormal()) {
				$this->updateNonRedirectModel($chain, $model);
			}
			$view->render($model);

			$chain->next();

			return $this;
		}

		/**
		 * Добавляет заголовок, если уже такой есть - перезаписывает
		 *
		 * @param string $name имя заголовка
		 * @param string $value значение заголовка
		 * @return WebAppViewHandler
		 */
		public function addHeader($name, $value) {
			$this->headers[$name] = $value;
			return $this;
		}

		/**
		 * Получаем простой ViewResolver
		 * @param InterceptingChain $chain
		 * @param Model $model
		 * @return ViewResolver
		 */
		protected function getViewResolver(InterceptingChain $chain, Model $model) {
			return PhpViewResolver::create($chain->getPathTemplateDefault(), EXT_TPL);
		}

		/**
		 * Обновляем и дополняем модель перед передачей во view
		 * @param Model $model
		 * @return WebAppViewHandler
		 */
		protected function updateNonRedirectModel(InterceptingChain $chain, Model $model) {
			return $this;
		}

		/**
		 * Getting class' name for template
		 * @return WebAppViewHandler
		 */
		protected function getViewClassName() {
			return self::VIEW_CLASS_NAME_DEFAULT;
		}
	}
?>