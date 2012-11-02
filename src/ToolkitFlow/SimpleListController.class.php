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

	/**
	 * Реализует отображение списков объектов.
	 * В наследнике класса необходимо указать proto объекта и propertyList - настройки для получения списка
	 */
	namespace Onphp\Utils;

	abstract class SimpleListController extends ToolkitBaseController {

		protected $methodMap = array(
			'show' => 'showProcess',
			'list' => 'listProcess',
		);
		protected $defaultAction = 'show';

		/**
		 * @return \Onphp\ModelAndView
		**/
		public function handleRequest(\Onphp\HttpRequest $request) {
			$className = $this->getObjectName();
			if (!$this->serviceLocator->get('linker')->isObjectSupported($className, 'info')) {
				throw new PermissionException('No permission for info '.$className);
			}
			
			$showAddButton = $this->serviceLocator->get('linker')->isObjectSupported($this->getObjectName(), 'add');
			$this->model->set('showAddButton', $showAddButton);
			if ($showAddButton) {
				$addUrl = $this->serviceLocator->get('linker')->getUrl($className, array('action' => 'edit'), 'add');
				$this->model->set('addButtonUrl', $addUrl);
				$this->model->set('addButtonDialogId', $className);
			}

			return $this->resolveAction($request);
		}

		/**
		 * Возвращает настройки получения списка
		 * @return array
		 */
		abstract protected function getPropertyList();

		/**
		 * Возвращает MaV для отображения условий поиска
		 * @param $request HttpRequest
		 * @return \Onphp\ModelAndView
		**/
		protected function showProcess(\Onphp\HttpRequest $request) {
			$propertyList = $this->getPropertyList();
			$proto = $this->getProto();

			$form = $this->getListMakerFormBuilder($proto, $propertyList)->
				setDefaultLimit($this->getPageLimit())->
				buildForm();
			
			$this->model->
				set('form', $form)->
				set('propertyList', $propertyList)->
				set('listHeaderModel', $this->makeListHeaderModel($form, $propertyList))->
				set('preListTemplate', $this->getPreListTemplate())->
				set('postListTemplate', $this->getPostListTemplate());

			return $this->getMav('list');
		}

		/**
		 * Возвращает MaV с результатами поиска
		 * @param $request HttpRequest
		 * @return \Onphp\ModelAndView
		**/
		protected function listProcess(\Onphp\HttpRequest $request) {
			$this->searchProcess($request);
			return $this->getMav('list');
		}

		/**
		 * Заполняет модель результатом поиска
		 *
		 * @param \Onphp\HttpRequest $request
		 * @return \Onphp\Model
		 */
		protected function searchProcess(\Onphp\HttpRequest $request) {
			$propertyList = $this->getPropertyList();
			$proto = $this->getProto();

			$form = $this->getListMakerFormBuilder($proto, $propertyList)->
				setDefaultLimit($this->getPageLimit())->
				buildForm();
			$this->applySearchRules($form);
			$form->import($request->getGet())->checkRules();

			$this->model->
				set('form', $form)->
				set('propertyList', $propertyList)->
				set('listHeaderModel', $this->makeListHeaderModel($form, $propertyList))->
				set('preListTemplate', $this->getPreListTemplate())->
				set('postListTemplate', $this->getPostListTemplate());

			if ($form->getErrors()) {
				return $this->model;
			}

			$constructor = $this->getListMakerConstructor($proto, $propertyList);
			$queryResult = $constructor->getResult($form, $this->getPreparedCriteria());

			$this->model->
				set('limitName', $constructor->getLimitName())->
				set('offsetName', $constructor->getOffsetName())->
				set('queryResult', $queryResult)->
				set('pagerModel', $this->makePagerModel($queryResult, $form))->
				set('columnModel', $this->makeColumnModel($form, $propertyList))->
				set('rowParams', $this->getRowsParams($queryResult, $propertyList))->
				set('showInfo', $this->showInfo());

			$this->model->get('listHeaderModel')->set('hideFilters', true);

			return $this->model;
		}

		/**
		 * Возвращает подмодель с данными для фильтров поиска
		 * @param \Onphp\Form $\Onphp\Form
		 * @param array $propertyList
		 * @return \Onphp\Model
		 */
		protected function makeListHeaderModel(\Onphp\Form $form, array $propertyList) {
			return \Onphp\Model::create()->
				set('form', $form)->
				set('propertyList', $propertyList)->
				set('proto', $this->getProto())->
				set('urlParams', $this->getUrlParams())->
				set('hideFilters', false)->
				set('objectName', $this->getObjectName());
		}

		/**
		 * Возвращает подмодель с данными для пейджера
		 * @param \Onphp\QueryResult $\Onphp\QueryResult
		 * @param \Onphp\Form $\Onphp\Form
		 * @return \Onphp\Model
		 */
		protected function makePagerModel(\Onphp\QueryResult $queryResult, \Onphp\Form $form) {
			return \Onphp\Model::create()->
				set('totalCount', $queryResult->getCount())->
				set('offset', $form->getSafeValue('offset'))->
				set('limit', $form->getSafeValue('limit'))->
				set('baseUrl', PATH_WEB_URL)->
				set('urlParams', $this->getUrlParams() + $form->export());
		}

		/**
		 * Возвращает подмодель с данными для рендеринга колонок сортировки
		 * @param \Onphp\Form $\Onphp\Form
		 * @param array $propertyList
		 * @return \Onphp\Model
		 */
		protected function makeColumnModel(\Onphp\Form $form, array $propertyList) {
			$columnParams = $form->export();
			foreach (array_keys($columnParams) as $propertyName) {
				unset($columnParams[$propertyName]['order']);
				unset($columnParams[$propertyName]['sort']);
			}

			return \Onphp\Model::create()->
				set('propertyList', $propertyList)->
				set('baseUrl', PATH_WEB_URL)->
				set('urlParams', $this->getUrlParams() + $columnParams)->
				set('formData', $form->export())->
				set('objectName', $this->getObjectName());
		}
		
		protected function getRowParams(\Onphp\QueryResult $queryResult, array $propertyList, $propertyName) {
			return array();
		}

		/**
		 * Возвращает базовые параметры url'а для отображения текущего контроллера
		 * @return array
		 */
		protected function getUrlParams() {
			\Onphp\Assert::isTrue((bool)preg_match('~^(.*)Controller$~', get_class($this), $matches));
			return array(
				'area' => $matches[1],
				'action' => 'list',
			);
		}

		/**
		 * Возвращает название класса со списком элементов которого будет работать контроллер
		 * @return string
		 */
		protected function getObjectName() {
			$className = get_class($this);
			return substr($className, 0, stripos($className, 'listcontroller'));
		}

		/**
		 * Возвращает Proto объекта по которому создается список
		 * @return \Onphp\AbstractProtoClass
		 */
		protected function getProto() {
			return \Onphp\ClassUtils::callStaticMethod("{$this->getObjectName()}::proto");
		}

		/**
		 * Переопределенный метод возвращает путь до базовой директории шаблона
		 * @return string
		 */
		protected function getViewPath() {
			return 'Objects/'.($this->isStandartView() ? 'SimpleObject' : $this->getObjectName());
		}
		
		/**
		 * @return null|Criteria
		 */
		protected function getPreparedCriteria() {
			return null;
		}
		
		/**
		 * @param \Onphp\Form $\Onphp\Form 
		 */
		protected function applySearchRules(\Onphp\Form $form) {
			/* implement in child if needed */
		}
		
		/**
		 * @return boolean 
		 */
		protected function isStandartView() {
			return true;
		}
		
		/**
		 * @return boolean 
		 */
		protected function showInfo() {
			return true;
		}
		
		/**
		 * @return int 
		 */
		protected function getPageLimit() {
			return 20;
		}
		
		protected function getPreListTemplate() {
			return null;
		}
		
		protected function getPostListTemplate() {
			return null;
		}
		
		/**
		 * @param \Onphp\AbstractProtoClass $proto
		 * @param array $propertyList
		 * @return \Onphp\Utils\ListMakerFormBuilder
		 */
		protected function getListMakerFormBuilder(\Onphp\AbstractProtoClass $proto, array $propertyList) {
			return ListMakerFormBuilder::create($proto, $propertyList);
		}
		
		/**
		 * @param \Onphp\AbstractProtoClass $proto
		 * @param array $propertyList
		 * @return \Onphp\Utils\ListMakerConstructor
		 */
		protected function getListMakerConstructor(\Onphp\AbstractProtoClass $proto, array $propertyList) {
			return ListMakerConstructor::create($proto, $propertyList);
		}

		protected function prepairData(\Onphp\HttpRequest $request, \Onphp\ModelAndView $mav) {
			$mav = parent::prepairData($request, $mav);
			if ($currentMenu = $this->getCurrentMenu($request, $mav)) {
				$mav->getModel()->set('currentMenu', $currentMenu);
			}
			return $mav;
		}
		
		protected function getCurrentMenu(\Onphp\HttpRequest $request, \Onphp\ModelAndView $mav) {
			return '';
		}
		
		private function getRowsParams(\Onphp\QueryResult $queryResult, array $propertyList) {
			$rowsParams = array();
			foreach (array_keys($propertyList) as $propertyName) {
				$rowsParams[$propertyName] = $this->getRowParams($queryResult, $propertyList, $propertyName);
			}
			return $rowsParams;
		}
	}
?>