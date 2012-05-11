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

	class Authorisator
	{
		/**
		 * @var SessionWrapper
		 */
		protected $session = null;
		protected $userClassName = null;
		protected $userIdParamName = 'userId';

		protected $preloadedUserId = false;
		protected $userId = null;
		protected $hash = null;

		/**
		 * @return Authorisator
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return SessionWrapper
		 */
		public function getSession()
		{
			return $this->session;
		}

		/**
		 * @return Authorisator
		 */
		public function setSession(SessionWrapper $session)
		{
			$this->session = $session;
			return $this;
		}

		public function getUserClassName()
		{
			return $this->userClassName;
		}

		/**
		 * @param string $userClassName
		 * @return Authorisator
		 */
		public function setUserClassName($userClassName)
		{
			$this->userClassName = $userClassName;
			return $this;
		}

		public function getUserIdParamName()
		{
			return $this->userIdParamName;
		}

		/**
		 * @param string $userClassName
		 * @return Authorisator
		 */
		public function setUserIdParamName($userIdParamName)
		{
			$this->userIdParamName = $userIdParamName;
			return $this;
		}

		/**
		 * @param string $uniqData
		 * @return AuthorisatorWithUniqData
		 */
		public function setUniqData($uniqData)
		{
			Assert::isString($uniqData);
			$this->hash = md5($uniqData);
			return $this;
		}

		/**
		 * @param string $hash
		 * @return AuthorisatorWithUniqData
		 */
		public function setHash($hash)
		{
			Assert::isString($hash);
			$this->hash = $hash;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getHash()
		{
			return $this->hash;
		}

		/**
		* @return Identifiable
		*/
		public function getUser()
		{
			Assert::isNotNull($this->session, 'session must be setted');
			Assert::isNotEmpty($this->userClassName, 'userClassName must be setted');

			if (!$this->session->isStarted()) {
				$this->preloadedUserId = false;
				return $this->userId = null;
			}

			$hash = $this->session->get($this->getHashParamName());
			if ($hash != $this->hash) {
				return null;
			}

			if ($this->preloadedUserId === true) {
				if ($this->userId !== null) {
					return ClassUtils::callStaticMethod("{$this->userClassName}::dao")->
						getById($this->userId);
				}
				return null;
			}
			$this->preloadedUserId = true;

			$form = Form::create()->add(
				Primitive::identifier($this->userIdParamName)->
					of($this->userClassName)->
					required()
			);

			$form->import($this->session->getAll());

			if ($form->getErrors()) {
				return $this->userId = null;
			}

			$user = $form->getValue($this->userIdParamName);
			$this->userId = $user->getId();
			return $user;
		}

		/**
		 * @param Identifiable $user
		 * @return Authorisator
		 */
		public function setUser(Identifiable $user)
		{
			Assert::isNotNull($this->session, 'session must be setted');
			Assert::isNotEmpty($this->userClassName, 'userClassName must be setted');
			Assert::isInstance($user, $this->userClassName);

			if (!$this->session->isStarted()) {
				$this->session->start();
			}

			$userId = $user->getId();
			$this->session->assign($this->userIdParamName, $userId);
			$this->userId = $userId;
			$this->preloadedUserId = true;
			$this->session->assign($this->getHashParamName(), $this->hash);

			return $this;
		}

		/**
		 * @return Authorisator
		 */
		public function dropUser()
		{
			Assert::isNotNull($this->session);
			if ($this->session->isStarted()) {
				$this->session->drop($this->userIdParamName);
			}
			$this->userId = null;
			$this->preloadedUserId = true;
			$this->session->drop($this->getHashParamName());

			return $this;
		}

		/**
		 * @return string
		 */
		protected function getHashParamName()
		{
			return $this->getUserIdParamName().'hash';
		}
	}
?>