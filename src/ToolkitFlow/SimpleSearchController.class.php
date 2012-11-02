<?php
/***************************************************************************
 *   Copyright (C) 2012 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	namespace Onphp\Utils;

	abstract class SimpleSearchController implements \Onphp\Controller, IServiceLocatorSupport {
		use TServiceLocatorSupport;
		
		public function handleRequest(\Onphp\HttpRequest $request) {
			$searchMap = $this->getSearchMap();
			
			$form = $this
				->getFormObject($searchMap)
				->import($request->getGet());
			if ($form->getErrors()) {
				return \Onphp\ModelAndView::create()->setView(\Onphp\EmptyView::create());
			}
			
			$propertyForm = $this
				->getFormProperty($searchMap, $form->getValue('object'))
				->import($request->getGet());
			if ($propertyForm->getErrors()) {
				return \Onphp\ModelAndView::create()->setView(\Onphp\EmptyView::create());
			}
			if (!$this->hasAccess($form->getValue('object'), $propertyForm->getValue('property'))) {
				\Onphp\HeaderUtils::sendHttpStatus(new \Onphp\HttpStatus(\Onphp\HttpStatus::CODE_403));
				return \Onphp\ModelAndView::create()->setView(\Onphp\EmptyView::create());
			}
			
			
			$searchResult = array_map(
				$this->getArrayConvertFunc(),
				$this->getListByParam(
					$request,
					$form->getValue('object'),
					$propertyForm->getValue('property'), 
					$form->getValue('search')
				)
			);
			
			return \Onphp\ModelAndView::create()
				->setView(\Onphp\JsonView::create()->setForceObject(false))
				->setModel(\Onphp\Model::create()->set('array', $searchResult));
		}
		
		/**
		 * @return array(
		 *		'ObjectName' => array('property1', 'property2', ...),
		 *		...
		 * );
		 */
		abstract protected function getSearchMap();
		
		/**
		 * @return \Onphp\Utils\ObjectNameConverter 
		 */
		protected function getNameConverter() {
			return new ObjectNameConverter();
		}
		
		/**
		 * @param string $class
		 * @param string $property
		 * @param string $search
		 * @return array 
		 */
		protected function getListByParam(\Onphp\HttpRequest $request, $class, $property, $search) {
			$criteria = $this->getListCriteria($class);
			foreach ($this->getExprForSearchCriteria($request, $class, $property, $search) as $expr) {
				$criteria->add($expr);
			}
			
			foreach ($this->getOrderForSearchCriteria($class, $property) as $order) {
				$criteria->addOrder($order);
			}
			$criteria->setLimit($this->getListLimit($class, $property));
			
			return $criteria->getList();
		}
		
		protected function getExprForSearchCriteria(\Onphp\HttpRequest $request, $class, $property, $search) {
			$expr = \Onphp\Expression::ilike(
				\Onphp\SQLFunction::create('lower', $property),
				\Onphp\DBValue::create(mb_strtolower($search).'%')
			);
			return array($expr);
		}
		
		protected function getOrderForSearchCriteria($class, $property) {
			return array(\Onphp\OrderBy::create($property)->asc());
		}
		
		/**
		 * @param string $class
		 * @return \Onphp\Criteria 
		 */
		protected function getListCriteria($class) {
			return \Onphp\Criteria::create(\Onphp\ClassUtils::callStaticMethod("{$class}::dao"));
		}
		
		protected function getArrayConvertFunc() {
			$nameConverter = $this->getNameConverter();
			return function($object) use ($nameConverter) {
				return array(
					'id' => $object->getId(),
					'value' => $nameConverter->get($object),
					'label' => $nameConverter->get($object)
				);
			};
		}
		
		protected function getListLimit($className, $property) {
			return 20;
		}
		
		protected function hasAccess($className, $property) {
			return $this->serviceLocator->get('linker')->isObjectSupported($className, 'info');
		}
		
		/**
		 * @return \Onphp\Form
		 */
		private function getFormObject($searchMap) {
			return \Onphp\Form::create()
				->add(
					\Onphp\Primitive::plainChoice('object')
						->setList(array_keys($searchMap))
						->required()
				)
				->add(\Onphp\Primitive::string('search')->required());
		}
		
		/**
		 * @return \Onphp\Form
		 */
		private function getFormProperty($searchMap, $class) {
			return \Onphp\Form::create()
				->add(
					\Onphp\Primitive::plainChoice('property')
						->setList($searchMap[$class])
						->required()
				);
		}
	}