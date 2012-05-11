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

	class WebAppAuthorisatorHandler implements InterceptingChainHandler
	{
		protected $authorisatorList = array();

		/**
		 * @return WebAppAuthorisatorHandler
		 */
		public static function create() {
			return new self();
		}

		/**
		 * @return WebAppAuthorisatorHandler
		 */
		public function run(InterceptingChain $chain) {
			$serviceLocator = $chain->getServiceLocator();
			foreach ($this->authorisatorList as $authrisatorName => $authorisator) {
				$this->setupAuthorisator($chain, $authorisator);
				$serviceLocator->set($authrisatorName, $authorisator);
			}

			$chain->next();

			return $this;
		}

		/**
		 * @return WebAppAuthorisatorHandler
		 */
		public function addAuthorisator($nameInLocator, Authorisator $authorisator) {
			$this->authorisatorList[$nameInLocator] = $authorisator;
			return $this;
		}

		/**
		 * @param Authorisator $authorisator
		 * @return $this;
		 */
		protected function setupAuthorisator(InterceptingChain $chain, Authorisator $authorisator) {
			$authorisator->setSession($chain->getServiceLocator()->get('session'));
			if ($userData = $this->getUniqUserData($chain)) {
				$authorisator->setUniqData($userData);
			}

			return $this;
		}

		/**
		 * @param InterceptingChain $chain
		 * @return string
		 */
		protected function getUniqUserData(InterceptingChain $chain) {
			$request = $chain->getRequest();
			/* @var $request HttpRequest */
			$remoteIp = $request->getServerVar('REMOTE_ADDR');
			$remoteUserAgent = $request->getServerVar('HTTP_USER_AGENT');
			return $remoteIp.$remoteUserAgent;
		}
	}
?>