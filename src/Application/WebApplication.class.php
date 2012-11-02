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

	class WebApplication extends InterceptingChain implements IServiceLocatorSupport
	{	
		const OBJ_REQUEST = 'request';
		const OBJ_MAV = 'mav';
		const OBJ_CONTROLLER_NAME = 'controllerName';
		const OBJ_SERVICE_LOCATOR = 'serviceLocator';
		const OBJ_PATH_WEB = 'pathWeb';
		const OBJ_PATH_CONTROLLER = 'pathController';
		const OBJ_PATH_TEMPLATE = 'pathTemplate';
		const OBJ_PATH_TEMPLATE_DEFAULT = 'pathTemplateDefault';

		protected $vars = array();

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public static function create()
		{
			return new self();
		}

		public function __construct()
		{
			$request = \Onphp\HttpRequest::create()->
				setGet($_GET)->
				setPost($_POST)->
				setCookie($_COOKIE)->
				setServer($_SERVER)->
				setFiles($_FILES);

			if (!empty($_SESSION)) {
				$request->setSession($_SESSION);
			}

			$this->setRequest($request);

			return $this;
		}

		public function getVar($name)
		{
			if (!$this->hasVar($name)) {
				throw new \Onphp\MissingElementException("not found var '$name'");
			}
			return $this->vars[$name];
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setVar($name, $var)
		{
			if ($this->hasVar($name)) {
				throw new \Onphp\WrongStateException("var '$name' already setted");
			}
			$this->vars[$name] = $var;

			return $this;
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function dropVar($name)
		{
			if (!$this->hasVar($name)) {
				throw new \Onphp\MissingElementException("not found var '$name'");
			}
			unset($this->vars[$name]);

			return $this;
		}

		public function hasVar($name)
		{
			return array_key_exists($name, $this->vars);
		}

		/**
		 * @return \Onphp\HttpRequest
		 */
		public function getRequest()
		{
			return $this->getVar(self::OBJ_REQUEST);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setRequest(\Onphp\HttpRequest $request)
		{
			return $this->setVar(self::OBJ_REQUEST, $request);
		}

		/**
		 * @return \Onphp\ModelAndView
		 */
		public function getMav()
		{
			return $this->getVar(self::OBJ_MAV);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setMav(\Onphp\ModelAndView $mav)
		{
			return $this->setVar(self::OBJ_MAV, $mav);
		}

		public function getControllerName()
		{
			return $this->getVar(self::OBJ_CONTROLLER_NAME);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setControllerName($controllerName)
		{
			return $this->setVar(self::OBJ_CONTROLLER_NAME, $controllerName);
		}

		/**
		 * @return \Onphp\Utils\ServiceLocator
		 */
		public function getServiceLocator()
		{
			return $this->getVar(self::OBJ_SERVICE_LOCATOR);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setServiceLocator(IServiceLocator $serviceLocator)
		{
			return $this->setVar(self::OBJ_SERVICE_LOCATOR, $serviceLocator);
		}

		public function getPathWeb()
		{
			return $this->getVar(self::OBJ_PATH_WEB);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setPathWeb($pathWeb)
		{
			return $this->setVar(self::OBJ_PATH_WEB, $pathWeb);
		}

		public function getPathController()
		{
			return $this->getVar(self::OBJ_PATH_CONTROLLER);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setPathController($pathController)
		{
			return $this->setVar(self::OBJ_PATH_CONTROLLER, $pathController);
		}

		public function getPathTemplate()
		{
			return $this->getVar(self::OBJ_PATH_TEMPLATE);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setPathTemplate($pathTemplate)
		{
			return $this->setVar(self::OBJ_PATH_TEMPLATE, $pathTemplate);
		}

		public function getPathTemplateDefault()
		{
			return $this->getVar(self::OBJ_PATH_TEMPLATE_DEFAULT);
		}

		/**
		 * @return \Onphp\Utils\WebApplication
		 */
		public function setPathTemplateDefault($pathTemplateDefault)
		{
			return $this->setVar(self::OBJ_PATH_TEMPLATE_DEFAULT, $pathTemplateDefault);
		}
	}
?>