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

class SimpleListController extends BaseController implements IServiceLocatorSupport, IToolkitControllerHelperSupport
{
	use TServiceLocatorSupport;

	protected $methodMap = array(
//		'show' => 'showProcess',
		'list' => 'listProcess',
	);
	protected $defaultAction = 'list';

	/**
	 * @var SimpleListControllerHelper
	 */
	protected $helper = null;

	/**
	 * @param \Onphp\Utils\ToolkitControllerHelper $helper
	 * @return \Onphp\Utils\SimpleListController
	 */
	public function setHelper(ToolkitControllerHelper $helper)
	{
		\Onphp\Assert::isTrue($helper instanceof SimpleListControllerHelper);
		$this->helper = $helper;
		return $this;
	}

	/**
	 * @return \Onphp\ModelAndView
	**/
	public function handleRequest(\Onphp\HttpRequest $request)
	{
		\Onphp\Assert::isNotNull($this->helper, 'setHelper first');

		$className = $this->helper->getClassName();
		$permission = $this->helper->getObjectPermission($className, 'info');
		if (!$permission->isAllowed()) {
			$msg = ($permission instanceof PermissionTextual)
				? $permission->getMsg()
				: 'No permission for info '.$className;
			throw new PermissionException($msg);
		}


		$showAddButton = $this->helper->isObjectSupported($className, 'create');
		$model = $this->getModel();
		$model->set('showAddButton', $showAddButton);
		if ($showAddButton) {
			$addUrl = $this->helper->getLinker()->getUrl($className, array('action' => 'edit'), 'create');
			$model->set('addButtonUrl', $addUrl);
			$model->set('addButtonDialogId', $className);
		}

		$mav = $this->resolveAction($request);
		$mav->getModel()->merge($model);
		return $mav;
	}

	/**
	 * Возвращает MaV с результатами поиска
	 * @param $request HttpRequest
	 * @return \Onphp\ModelAndView
	**/
	protected function listProcess(\Onphp\HttpRequest $request) {
		$model = $this->searchProcess($request);
		return $this->getMav('list', null, $model);
	}

	/**
	 * Заполняет модель результатом поиска
	 *
	 * @param \Onphp\HttpRequest $request
	 * @return \Onphp\Model
	 */
	protected function searchProcess(\Onphp\HttpRequest $request) {
		$propertyList = $this->helper->getPropertyList();
		$proto = $this->getProto();

		$form = $this->helper->getListMakerFormBuilder($proto, $propertyList)->
			setDefaultLimit($this->helper->getPageLimit())->
			buildForm();
		$this->applySearchRules($form);
		$form->import($request->getGet())->checkRules();

		$model = \Onphp\Model::create()->
			set('form', $form)->
			set('propertyList', $propertyList)->
			set('listHeaderModel', $this->makeListHeaderModel($form, $propertyList))->
			set('preListTemplate', $this->getPreListTemplate())->
			set('postListTemplate', $this->getPostListTemplate());

		if ($form->getErrors()) {
			return $model;
		}

		$constructor = $this->helper->getListMakerConstructor($this->getClassName(), $propertyList);
		$queryResult = $constructor->getResult($form, $this->getPreparedCriteria());

		$model->
			set('limitName', $constructor->getLimitName())->
			set('offsetName', $constructor->getOffsetName())->
			set('queryResult', $queryResult)->
			set('pagerModel', $this->makePagerModel($queryResult, $form))->
			set('columnModel', $this->makeColumnModel($form, $propertyList))->
			set('rowParams', $this->getRowsParams($queryResult, $propertyList))->
			set('showInfo', $this->helper->showInfo());

		$model->get('listHeaderModel')->set('hideFilters', true);

		return $model;
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
			set('objectName', $this->getClassName());
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
			set('baseUrl', $this->helper->getBaseUrl())->
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
			set('baseUrl', $this->helper->getBaseUrl())->
			set('urlParams', $this->getUrlParams() + $columnParams)->
			set('formData', $form->export())->
			set('objectName', $this->getClassName());
	}

	protected function getRowParams(\Onphp\QueryResult $queryResult, array $propertyList, $propertyName) {
		return array();
	}

	/**
	 * Возвращает базовые параметры url'а для отображения текущего контроллера
	 * @return
	 */
	protected function getUrlParams() {
		$linker = $this->helper->getLinker();
		return $linker->getUrlParams($this->getClassName(), [], 'list');
	}

	/**
	 * Возвращает название класса со списком элементов которого будет работать контроллер
	 * @return string
	 */
	protected function getClassName() {
		return $this->helper->getClassName();
	}

	/**
	 * Возвращает Proto объекта по которому создается список
	 * @return \Onphp\AbstractProtoClass
	 */
	protected function getProto() {
		return \Onphp\ClassUtils::callStaticMethod("{$this->getClassName()}::proto");
	}

	/**
	 * Переопределенный метод возвращает путь до базовой директории шаблона
	 * @return string
	 */
	protected function getViewPath() {
		return 'Objects/SimpleObject';
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

	protected function getPreListTemplate() {
		return null;
	}

	protected function getPostListTemplate() {
		return null;
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
