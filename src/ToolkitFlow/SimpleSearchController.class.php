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

	abstract class SimpleSearchController implements Controller, IServiceLocatorSupport {
		use TServiceLocatorSupport;
		
		public function handleRequest(HttpRequest $request) {
			$searchMap = $this->getSearchMap();
			
			$form = $this
				->getFormObject($searchMap)
				->import($request->getGet());
			if ($form->getErrors()) {
				return ModelAndView::create()->setView(EmptyView::create());
			}
			
			$propertyForm = $this
				->getFormProperty($searchMap, $form->getValue('object'))
				->import($request->getGet());
			if ($propertyForm->getErrors()) {
				return ModelAndView::create()->setView(EmptyView::create());
			}
			if (!$this->hasAccess($form->getValue('object'), $propertyForm->getValue('property'))) {
				HeaderUtils::sendHttpStatus(new HttpStatus(HttpStatus::CODE_403));
				return ModelAndView::create()->setView(EmptyView::create());
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
			
			return ModelAndView::create()
				->setView(JsonView::create()->setForceObject(false))
				->setModel(Model::create()->set('array', $searchResult));
		}
		
		/**
		 * @return array(
		 *		'ObjectName' => array('property1', 'property2', ...),
		 *		...
		 * );
		 */
		abstract protected function getSearchMap();
		
		/**
		 * @return ObjectNameConverter 
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
		protected function getListByParam(HttpRequest $request, $class, $property, $search) {
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
		
		protected function getExprForSearchCriteria(HttpRequest $request, $class, $property, $search) {
			$expr = Expression::ilike(
				SQLFunction::create('lower', $property),
				DBValue::create(mb_strtolower($search).'%')
			);
			return array($expr);
		}
		
		protected function getOrderForSearchCriteria($class, $property) {
			return array(OrderBy::create($property)->asc());
		}
		
		/**
		 * @param string $class
		 * @return Criteria 
		 */
		protected function getListCriteria($class) {
			return Criteria::create(ClassUtils::callStaticMethod("{$class}::dao"));
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
		 * @return Form
		 */
		private function getFormObject($searchMap) {
			return Form::create()
				->add(
					Primitive::plainChoice('object')
						->setList(array_keys($searchMap))
						->required()
				)
				->add(Primitive::string('search')->required());
		}
		
		/**
		 * @return Form
		 */
		private function getFormProperty($searchMap, $class) {
			return Form::create()
				->add(
					Primitive::plainChoice('property')
						->setList($searchMap[$class])
						->required()
				);
		}
	}