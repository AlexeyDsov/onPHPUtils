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

	class WebAppAuthorisatorHandlerHttpDigest extends WebAppAuthorisatorHandler
	{
		/**
		 * @return WebAppAuthorisatorHandlerHttpDigest
		 */
		public static function create() {
			return new self();
		}

		/**
		 * Настраивает авторизатор и находит ему пользователя по настройкам серверных параметров
		 * @param Authorisator $authorisator
		 * @return $this;
		 */
		protected function setupAuthorisator(InterceptingChain $chain, Authorisator $authorisator) {
			parent::setupAuthorisator($chain, $authorisator);

			if (!$authorisator->getUser()) {
				if ($user = $this->findUser($chain, $authorisator)) {
					$authorisator->setUser($user);
				}
			}

			return $this;
		}

		/**
		 * Возвращает данные уникально идентифицирующие пользователя
		 * @param InterceptingChain $chain
		 * @return string
		 */
		protected function getUniqUserData(InterceptingChain $chain) {
			$request = $chain->getRequest();
			/* @var $request HttpRequest */
			return parent::getUniqUserData($chain)
				. ($request->hasServerVar('PHP_AUTH_USER') ? $request->getServerVar('PHP_AUTH_USER') : '')
				. ($request->hasServerVar('PHP_AUTH_PW') ? $request->getServerVar('PHP_AUTH_PW') : '');
		}

		/**
		 * Находит админа по параметрам Http Digest и возвращает если нашел
		 * @param InterceptingChain $chain
		 * @return ILoginUserDigest
		 */
		protected function findUser(WebApplication $chain, Authorisator $authorisator) {
			$session = $chain->getServiceLocator()->get('session');
			$loginHelper = $chain->getServiceLocator()->spawn('LoginHelperDigest');
			/* @var $loginHelper LoginHelperDigest */
			$loginHelper->setAuthorisator($authorisator);
			$loginHelper->setSession($session);
			
			return $loginHelper->findUser($chain->getRequest());
		}
	}
?>