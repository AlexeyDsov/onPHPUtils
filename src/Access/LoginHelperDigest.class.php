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
	 * http://www.faqs.org/rfcs/rfc2617.html
	 */
	namespace Onphp\Utils;

	class LoginHelperDigest
	{
		private $className = null;
		/**
		 * @var \Onphp\Utils\Authorisator
		 */
		private $authorisator = null;
		/**
		 * @var \Onphp\Utils\SessionWrapper
		 */
		private $session = null;
		
		/**
		 * @param string $className
		 * @return \Onphp\Utils\LoginHelperDigest 
		 */
		public function of($className) {
			\Onphp\Assert::isInstance($className, '\Onphp\Utils\ILoginUserDigest');
			$this->className = $className;
			return $this;
		}

		/**
		 * @param \Onphp\Utils\Authorisator $\Onphp\Utils\Authorisator
		 * @return \Onphp\Utils\LoginHelperDigest 
		 */
		public function setAuthorisator(Authorisator $authorisator) {
			$this->authorisator = $authorisator;
			$this->className = $authorisator->getUserClassName();
			return $this;
		}

		/**
		 * @param \Onphp\Utils\SessionWrapper $authorisator
		 * @return \Onphp\Utils\LoginHelperDigest 
		 */
		public function setSession(SessionWrapper $session) {
			$this->session = $session;
			return $this;
		}
		
		/**
		 * @param \Onphp\Utils\Authorisator $\Onphp\Utils\Authorisator 
		 * @return boolean true if already authorised, false if authorisation request was send
		 */
		public function authRequest() {
			if (!$this->authorisator->getUser()) {
				$realm = \Onphp\ClassUtils::callStaticMethod("{$this->className}::getRealm");
				if (!$this->session->isStarted()) {
					$this->session->start();
				}
				$this->session->assign($this->getLoginKeyParamName(), $loginKey = uniqid());
				
				$params = array(
					'realm' => $realm,
					'qop' => 'auth',
					'nonce' => $loginKey,
					'opaque' => md5($realm),
				);
				header($this->makeDigitAuthHeader($params));
				return false;
			}
			return true;
		}
		
		/**
		 * do unlogin operation with authorisator and session
		 */
		public function unlogin() {
			if ($user = $this->authorisator->getUser()) {
				/* @var $user \Onphp\Utils\ILoginUserDigest */
				$user->dao()->merge($user->setLoginKey(null));
				$this->authorisator->dropUser();
			}
			
			if ($this->session->isStarted()) {
				$this->session->assign($this->getLoginKeyParamName(), null);
			}
		}
		
		/**
		 * Вычисляет уникальный хэш по паролю и имени пользователя
		 * @param string $name
		 * @param string $password
		 * @return string 
		 */
		public function getHash($name, $password) {
			$parts = array(
				$name,
				\Onphp\ClassUtils::callStaticMethod("{$this->className}::getRealm"),
				$password
			);
			return md5(implode(':', $parts));
		}
		
		/**
		 * Находит пользователя по параметрам авторизации из request'а и сессии.
		 * @param \Onphp\HttpRequest $request
		 * @return \Onphp\Utils\ILoginUserDigest
		 */
		public function findUser(\Onphp\HttpRequest $request) {
			if (!$this->session->isStarted()) {
				return null;
			}
			if (!($digestParams = $this->parseDigestAuthResponse($request))) {
				return null;
			}
			
			$dao = \Onphp\ClassUtils::callStaticMethod("{$this->className}::dao");
			\Onphp\Assert::isInstance($dao, '\Onphp\Utils\ILoginUserDigestDAO');
			if (!($user = $dao->findByAuthParam($digestParams['username']))) {
				return null;
			}
			
			/* @var $user \Onphp\Utils\ILoginUserDigest */
			
			$sessionLoginKey = $this->session->get($this->getLoginKeyParamName());
			$userLoginKey = $user->getLoginKey();
			$currentLoginKey = $digestParams['nonce'];
			if (empty($currentLoginKey)) {
				return null;
			}
			
			if ($currentLoginKey != $userLoginKey && $currentLoginKey != $sessionLoginKey) {
				return null;
			}
			
			$A1 = $user->getPasswordHash();
			$A2 = md5($request->getServerVar('REQUEST_METHOD').':'.$digestParams['uri']);
			$validResponseParts = array(
				$A1,
				$digestParams['nonce'],
				$digestParams['nc'],
				$digestParams['cnonce'],
				$digestParams['qop'],
				$A2,
			);
			$validResponse = md5(implode(':', $validResponseParts));
			
			if ($digestParams['response'] != $validResponse) {
				return null;
			}
			
			if ($currentLoginKey != $userLoginKey) {
				$user = $user->dao()->merge($user->setLoginKey($currentLoginKey));
			}
			
			return $user;
		}
		
		protected function getLoginKeyParamName() {
			return 'loginKey'.$this->className;
		}
		
		private function makeDigitAuthHeader($params = array()) {
			$header = 'WWW-Authenticate: Digest ';
			$count = count($params); $i = 1;
			foreach ($params as $key => $value) {
				$header .= $key.'="'.$value.'"'.($i++ < $count ? ',' : '');
			}
			return $header;
		}
		
		private function parseDigestAuthResponse(\Onphp\HttpRequest $request) {
			if (!$request->hasServerVar('PHP_AUTH_DIGEST')) {
				return null;
			}
			
			$phpAuthDigest = $request->getServerVar('PHP_AUTH_DIGEST');
			
			// protect against missing data
			preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)'
				.'=[\'"]?([^\'",]+)@', $phpAuthDigest, $t); 
			$data = array_combine($t[1], $t[2]); 
			# all parts found? 
			return (count($data) == 7) ? $data : null; 
		}
	}
?>