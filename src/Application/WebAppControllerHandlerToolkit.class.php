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

	class WebAppControllerHandlerToolkit extends WebAppControllerHandler
	{
		private $authorisatorName = null;
		private $baseUrl = null;
		
		/**
		 * @return WebAppControllerHandlerToolkit
		 */
		public static function create() {
			return new self();
		}

		/**
		 * @param string $authorisatorName
		 * @return WebAppControllerHandlerToolkit 
		 */
		public function setAuthorisatorName($authorisatorName) {
			$this->authorisatorName = $authorisatorName;
			return $this;
		}
		
		/**
		 * @param string $baseUrl 
		 * @return WebAppControllerHandlerToolkit 
		 */
		public function setBaseUrl($baseUrl) {
			$this->baseUrl = $baseUrl;
			return $this;
		}

		/**
		 * @param InterceptingChain $chain
		 * @return string
		 */
		protected function getController(InterceptingChain $chain) {
			/* @var $chain WebApplication */
			if (
				$chain->getRequest()->hasGetVar('_window')
				&& $chain->getRequest()->hasGetVar('_dialogId')
				&& !$chain->getVar('isPjax')
				&& !$chain->getVar('isAjax')
			) {
				$urlParts = $chain->getRequest()->getGet();
				$dialogId = $urlParts['_dialogId'];
				unset($urlParts['_dialogId'], $urlParts['_window']);
				$url = $this->baseUrl.http_build_query($urlParts);
				$get = array(
					'windowUrl' => $url,
					'dialogId' => $dialogId,
					'sign' => MainController::getSign($url.$dialogId),
				);
				$chain->getRequest()->setGet($get);
				$controllerName = $this->getDefaultController();
			} else {
				$controllerName = $chain->getControllerName();
			}

			$authorisator = $chain->getServiceLocator()->get($this->authorisatorName);
			/* @var $authorisator Authorisator */

			if (!$authorisator->getUser()) {
				if (!in_array($controllerName, $this->getNoUserAllowedControllerList())) {
					$controllerName = $this->getAccessDeniedController();
					$chain->
						dropVar(WebApplication::OBJ_CONTROLLER_NAME)->
						setControllerName($controllerName);
				}
			}

			return $chain->getServiceLocator()->spawn($controllerName);
		}

		protected function handleRequest(InterceptingChain $chain, Controller $controller) {
			/* @var $chain WebApplication */
			try {
				return parent::handleRequest($chain, $controller);
			} catch (PermissionException $e) {
				$controller = $chain->getServiceLocator()
					->spawn($this->getAccessDeniedController());
				return parent::handleRequest($chain, $controller);
			}
		}
		
		/**
		 * Возвращает список контроллеров разрешенных для неавторизованного пользователя
		 * @return array
		 */
		protected function getNoUserAllowedControllerList() {
			return array(
				$this->getAccessDeniedController(),
				'ErrorController',
				'LoginController',
				$this->getDefaultController(),
			);
		}
		
		protected function getDefaultController() {
			return 'MainController';
		}
		
		protected function getAccessDeniedController() {
			return 'AccessDeniedController';
		}
	}
?>