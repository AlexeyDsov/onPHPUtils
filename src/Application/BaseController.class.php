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

	namespace Onphp\Utils;

	abstract class BaseController implements \Onphp\Controller
	{
		/**
		 * @var \Onphp\Model
		 */
		protected $model = null;
		protected $methodMap = array();
		protected $defaultAction = null;
		protected $actionName = 'action';

		/**
		 * @var \Onphp\Utils\HeadHelper
		**/
		protected $meta	= null;

		public function __construct() {
			$this->model = \Onphp\Model::create();
			$this->setupMeta();
		}

		public function getModel() {
			return $this->model;
		}

		protected function getMav($tpl = 'index', $path = null) {
			return \Onphp\ModelAndView::create()->
				setModel($this->model)->
				setView($this->getViewTemplate($tpl, $path));
		}

		protected function getViewPath() {
			$className = get_class($this);
			return substr($className, 0, stripos($className, 'controller'));
		}

		protected function getViewTemplate($tpl, $path = null) {
			$path = ($path === null ? $this->getViewPath() : $path);
			return "{$path}/{$tpl}";
		}

		protected function getMavRedirectByUrl($url) {
			return \Onphp\ModelAndView::create()->setView(
				\Onphp\CleanRedirectView::create($url)
			);
		}

		protected function resolveAction(\Onphp\HttpRequest $request, \Onphp\Form $form = null) {
			if (empty($this->methodMap)) {
				throw new \Onphp\WrongStateException('You must specify $methodMap array');
			}

			if (!$form) {
				$form = \Onphp\Form::create();
			}

			$form->
				add(
					\Onphp\Primitive::choice($this->actionName)->
						setList($this->methodMap)->
						setDefault($this->defaultAction)
				)->
				import($request->getGet())->
				importMore($request->getPost())->
				importMore($request->getAttached());

			if ($form->getErrors()) {
				return \Onphp\ModelAndView::create()->
					setModel($this->model)->
					setView(\Onphp\View::ERROR_VIEW);
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

		protected function getControllerVar(\Onphp\HttpRequest $request) {
			$form = \Onphp\Form::create()->
				add(
					\Onphp\Primitive::string($this->ajaxVar)->
						setDefault('')
				)->
				importOne($this->ajaxVar, $request->getGet())->
				importOneMore($this->ajaxVar, $request->getAttached());
			$controller = $form->getSafeValue($this->ajaxVar);
			return $controller;
		}

		/**
		 * Дает возможность в наследниках модифицировать model в ModelAndView перед возвращением ее пользователю
		 * @param \Onphp\HttpRequest $request
		 * @param \Onphp\ModelAndView $mav
		 * @return \Onphp\ModelAndView 
		 */
		protected function prepairData(\Onphp\HttpRequest $request, \Onphp\ModelAndView $mav) {
			return $mav;
		}

		protected function setupMeta() {
			$this->meta = HeadHelper::create();
			$this->meta->setTitle('');
			$this->model->set('meta', $this->meta);

			return $this;
		}
	}
?>