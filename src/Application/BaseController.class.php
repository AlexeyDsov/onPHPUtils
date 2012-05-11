<?php
/***************************************************************************
 *   Copyright (C) 2011 by Sergey Sergeev, Alexandr Solomatin,             *
 *   Alexey Denisov                                                        *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	abstract class BaseController implements Controller, IServiceLocatorSupport
	{
		/**
		 * @var Model
		 */
		protected $model = null;
		protected $methodMap = array();
		protected $defaultAction = null;
		protected $actionName = 'action';

		/**
		 * @var HeadHelper
		**/
		protected $meta	= null;

		/**
		 * @var ServiceLocator
		 */
		protected $serviceLocator = null;

		public function __construct()
		{
			$this->model = Model::create();
			$this->setupMeta();
		}

		public function setServiceLocator(IServiceLocator $serviceLocator)
		{
			$this->serviceLocator = $serviceLocator;
			return $this;
		}

		/**
		 * @return ServiceLocator
		 */
		public function getServiceLocator()
		{
			return $this->serviceLocator;
		}

		protected function getMav($tpl = 'index', $path = null)
		{
			return ModelAndView::create()->
				setModel($this->model)->
				setView($this->getViewTemplate($tpl, $path));
		}

		protected function getViewPath()
		{
			$className = get_class($this);
			return substr($className, 0, stripos($className, 'controller'));
		}

		protected function getViewTemplate($tpl, $path = null)
		{
			$path = $path === null ? $this->getViewPath() : $path;
			return "{$path}/{$tpl}";
		}

		protected function getMavRedirectByUrl($url)
		{
			return ModelAndView::create()->setView(
				RedirectView::create($url)
			);
		}

		protected function resolveAction(HttpRequest $request, Form $form = null)
		{
			if (empty($this->methodMap)) {
				throw new WrongStateException();
			}

			if (!$form) {
				$form = Form::create();
			}

			$form->
				add(
					Primitive::choice($this->actionName)->
						setList($this->methodMap)->
						setDefault($this->defaultAction)
				)->
				import($request->getGet())->
				importMore($request->getPost())->
				importMore($request->getAttached());

			if ($form->getErrors()) {
				return $this->getMav404();
			}

			if (!$action = $form->getSafeValue($this->actionName)) {
				$action = $form->get($this->actionName)->getDefault();
			}

			$method = $this->methodMap[$action];
			$mav = $this->{$method}($request);

			if ($mav->viewIsRedirect()) {
				return $mav;
			}

			$mav = $this->prepairData($request, $mav);
			$mav->getModel()->set($this->actionName, $action);

			return $mav;
		}

		/**
		 * Дает возможность в наследниках модифицировать model в ModelAndView перед возвращением ее пользователю
		 * @param HttpRequest $request
		 * @param ModelAndView $mav
		 * @return ModelAndView 
		 */
		protected function prepairData(HttpRequest $request, ModelAndView $mav)
		{
			return $mav;
		}

		protected function setupMeta()
		{
			$this->meta = new HeadHelper();
			$this->meta->setTitle('');
			$this->model->set('meta', $this->meta);

			return $this;
		}
	}
?>