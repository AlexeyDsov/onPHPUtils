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

	class WebAppViewHandlerToolkit extends WebAppViewHandler
	{
		private $authorisatorName = null;
		
		/**
		 * @return WebAppViewHandlerToolkit
		 */
		public static function create() {
			return new self();
		}
		
		/**
		 * @param string $authorisatorName
		 * @return WebAppViewHandlerToolkit 
		 */
		public function setAuthorisatorName($authorisatorName) {
			$this->authorisatorName = $authorisatorName;
			return $this;
		}

		/**
		 * @param InterceptingChain $chain
		 * @param Model $model
		 * @return ViewResolver
		 */
		protected function getViewResolver(InterceptingChain $chain, Model $model) {
			$isPjax = $chain->hasVar('isPjax') ? $chain->getVar('isPjax') : false;
			$isAjax = $chain->hasVar('isAjax') ? $chain->getVar('isAjax') : false;
			
			$resolver = MultiPrefixPhpViewResolverParametrized::create()->
				setViewClassName($this->getViewClassName())->
				addFirstPrefix($chain->getPathTemplateDefault())->
				set('isPjax', $isPjax)->
				set('isAjax', $isAjax)->
				set('serviceLocator', $chain->getServiceLocator())->
				set('linker', $chain->getServiceLocator()->get('linker'))->
				set('translator', $chain->getServiceLocator()->get('translator'))->
				set('permissionManager', $chain->getServiceLocator()->get('permissionManager'))->
				set('nameConverter', $chain->getServiceLocator()->spawn($this->getNameConverterClass()));
			
			if (!$isAjax && ($menuList = $this->getMenuList($chain))) {
				$resolver->set('menuList', $menuList);
			}
			
			return $resolver;
		}

		/**
		 * @param InterceptingChain $chain
		 * @return ToolkitMenuConstructor
		 */
		protected function getMenuList(InterceptingChain $chain)
		{
			$serviceLocator = $chain->getVar('serviceLocator');
			$user = $serviceLocator->get($this->authorisatorName)->getUser();

			if ($user) {
				return $serviceLocator->spawn($this->getMenuContructor())->
					setPermissionManager($serviceLocator->get('permissionManager'))->
					setUser($user)->
					getMenuList();
			}

			return null;
		}

		/**
		 * Getting class' name for template
		 * @return WebAppViewHandler
		 */
		protected function getViewClassName()
		{
			return 'SimplePhpViewParametrizedToolkit';
		}
		
		protected function getMenuContructor() {
			return 'ToolkitMenuConstructor';
		}
		
		/**
		 * @return string
		 */
		protected function getNameConverterClass() {
			return 'ObjectNameConverter';
		}
	}
?>