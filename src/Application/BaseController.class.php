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

	abstract class BaseController implements Controller
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

		public function __construct() {
			$this->model = Model::create();
			$this->setupMeta();
		}

		public function getModel() {
			return $this->model;
		}

		protected function getMav($tpl = 'index', $path = null) {
			return ModelAndView::create()->
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
			return ModelAndView::create()->setView(
				CleanRedirectView::create($url)
			);
		}

		protected function resolveAction(HttpRequest $request, Form $form = null) {
			if (empty($this->methodMap)) {
				throw new WrongStateException('You must specify $methodMap array');
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
				return ModelAndView::create()->
					setModel($this->model)->
					setView(View::ERROR_VIEW);
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

		protected function getControllerVar(HttpRequest $request) {
			$form = Form::create()->
				add(
					Primitive::string($this->ajaxVar)->
						setDefault('')
				)->
				importOne($this->ajaxVar, $request->getGet())->
				importOneMore($this->ajaxVar, $request->getAttached());
			$controller = $form->getSafeValue($this->ajaxVar);
			return $controller;
		}

		/**
		 * Дает возможность в наследниках модифицировать model в ModelAndView перед возвращением ее пользователю
		 * @param HttpRequest $request
		 * @param ModelAndView $mav
		 * @return ModelAndView 
		 */
		protected function prepairData(HttpRequest $request, ModelAndView $mav) {
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