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

	namespace Onphp\Utils;

	class WebAppControllerHandler implements InterceptingChainHandler
	{
		/**
		 * @return \Onphp\Utils\WebAppControllerHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return \Onphp\Utils\WebAppControllerHandler
		 */
		public function run(InterceptingChain $chain)
		{
			$controller = $this->getController($chain);

			$modelAndView = $this->handleRequest($chain, $controller);
			$this->prepairModelAndView($chain, $modelAndView);

			$chain->setMav($modelAndView);

			$chain->next();

			return $this;
		}

		/**
		 * @return \Onphp\ModelAndView
		 */
		protected function handleRequest(InterceptingChain $chain, \Onphp\Controller $controller) {
			$modelAndView = $controller->handleRequest($chain->getRequest());

			if (!$modelAndView instanceof \Onphp\ModelAndView) {
				throw new \Onphp\WrongStateException(
					'Controller \''.get_class($controller).'\' instead ModelAndView return null'
				);
			}

			return $modelAndView;
		}

		/**
		 * По параметрам из chain создаем контроллер
		 * @param \Onphp\Utils\InterceptingChain $chain
		 * @return \Onphp\Controller
		 */
		protected function getController(InterceptingChain $chain) {
			$controllerName = $chain->getControllerName();
			return $chain->getServiceLocator()->spawn($controllerName);
		}

		/**
		 * @return \Onphp\Utils\WebAppControllerHandler
		 */
		protected function prepairModelAndView(InterceptingChain $chain, \Onphp\ModelAndView $modelAndView) {
			$controllerName = $chain->getControllerName();

			if (!$modelAndView->getView()) {
				$modelAndView->setView($controllerName);
			}

			return $this;
		}
	}
?>