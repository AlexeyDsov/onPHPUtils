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

	namespace Onphp\Utils;

	class WebAppAjaxHandler implements InterceptingChainHandler {

		private static $ajaxRequestVar = 'HTTP_X_REQUESTED_WITH';
		private static $ajaxRequestValueList = array('XMLHttpRequest');
		private static $pjaxRequestVar = 'HTTP_X_PJAX';

		/**
		 * @return \Onphp\Utils\WebAppAjaxHandler
		 */
		public static function create() {
			return new self();
		}

		/**
		 * @return \Onphp\Utils\WebAppAjaxHandler
		 */
		public function run(InterceptingChain $chain) {
			/* @var $chain \Onphp\Utils\WebApplication */
			$isPjaxRequest = $this->isPjaxRequest($chain->getRequest());
			$isAjaxRequest = !$isPjaxRequest && $this->isAjaxRequest($chain->getRequest());

//			$chain->setVar('isPjax', $isPjaxRequest);
//			$chain->setVar('isAjax', $isAjaxRequest);
			$chain->getServiceLocator()->
				set('isPjax', $isPjaxRequest)->
				set('isAjax', $isAjaxRequest);

			$chain->next();

			return $this;
		}

		/**
		 * @return boolean
		 */
		private function isAjaxRequest(\Onphp\HttpRequest $request) {
			$form = \Onphp\Form::create()->
				add(
					\Onphp\Primitive::plainChoice(self::$ajaxRequestVar)->
						setList(self::$ajaxRequestValueList)
				)->
				add(
					\Onphp\Primitive::boolean('_isAjax')
				)->
				import($request->getServer())->
				importOneMore('_isAjax', $request->getGet());

			if ($form->getErrors()) {
				return false;
			}
			if ($form->getValue(self::$ajaxRequestVar)) {
				return true;
			}
			if ($form->getValue('_isAjax')) {
				return true;
			}
			return false;
		}

		/**
		 * @return boolean
		 */
		private function isPjaxRequest(\Onphp\HttpRequest $request) {
			$form = \Onphp\Form::create()->
				add(
					\Onphp\Primitive::boolean(self::$pjaxRequestVar)
				)->
				add(
					\Onphp\Primitive::boolean('_isPjax')
				)->
				import($request->getServer())->
				importOneMore('_isPjax', $request->getGet());

			if ($form->getErrors()) {
				return false;
			}
			return $form->getValue(self::$pjaxRequestVar) || $form->getValue('_isPjax');
		}
	}
?>