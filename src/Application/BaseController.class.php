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
		protected $methodMap = array();
		protected $defaultAction = null;
		protected $actionName = 'action';

		public function __construct()
		{
		}

		public function getModel() {
			return \Onphp\Model::create();
		}

		protected function getMav($tpl = 'index', $path = null, \Onphp\Model $model = null)
		{
			return \Onphp\ModelAndView::create()->
				setModel($model ?: $this->getModel())->
				setView($this->getViewTemplate($tpl, $path));
		}

		protected function getViewPath()
		{
			$className = get_class($this);
			return substr($className, 0, stripos($className, 'controller'));
		}

		protected function getViewTemplate($tpl, $path = null)
		{
			$path = ($path === null ? $this->getViewPath() : $path);
			return "{$path}/{$tpl}";
		}

		protected function getMavRedirectByUrl($url)
		{
			return \Onphp\ModelAndView::create()->setView(
				\Onphp\CleanRedirectView::create($url)
			);
		}

		/**
		 * @param \Onphp\HttpRequest $request
		 * @param \Onphp\Form $form
		 * @return \Onphp\ModelAndView
		 * @throws \Onphp\WrongStateException
		 */
		protected function resolveAction(\Onphp\HttpRequest $request, \Onphp\Form $form = null)
		{
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
				$this->getMavResolveActionError();
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

		protected function getMavResolveActionError()
		{
			return \Onphp\ModelAndView::create()->
				setView(\Onphp\View::ERROR_VIEW);
		}

		/**
		 * Дает возможность в наследниках модифицировать model в ModelAndView перед возвращением ее из контроллера
		 * @param \Onphp\HttpRequest $request
		 * @param \Onphp\ModelAndView $mav
		 * @return \Onphp\ModelAndView
		 */
		protected function prepairData(\Onphp\HttpRequest $request, \Onphp\ModelAndView $mav)
		{
			$mav->getModel()->set('meta', $this->getHeadHelper());
			return $mav;
		}

		protected function getHeadHelper()
		{
			return HeadHelper::create()
				->setTitle('');
		}
	}
?>