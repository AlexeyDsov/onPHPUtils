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
 * Класс для отображения данных об объекте и редактировании их
 */
namespace Onphp\Utils;

class SimpleObjectFlowController extends BaseController implements IServiceLocatorSupport, IToolkitControllerHelperSupport {
	use TServiceLocatorSupport;

	/**
	 * Список методов, реализуемых контроллером
	 * @var array
	 */
	protected $methodMap = array(
		'info' => 'infoProcess',
		'edit' => 'editProcess',
		'save' => 'saveProcess',
		'drop' => 'dropProcess',
	);
	protected $defaultAction = 'info';

	/**
	 * @var SimpleObjectFlowControllerHelper
	 */
	protected $helper = null;

	/**
	 * @param \Onphp\Utils\ToolkitControllerHelper $helper
	 * @return \Onphp\Utils\SimpleListController
	 */
	public function setHelper(ToolkitControllerHelper $helper)
	{
		\Onphp\Assert::isTrue($helper instanceof SimpleObjectFlowControllerHelper);
		$this->helper = $helper;
		return $this;
	}

	/**
	 * Определяет, какое действие должен выполнить контроллер, вызывает его и возвращает результат
	 * @param $request HttpRequest
	 * @return \Onphp\ModelAndView
	**/
	public function handleRequest(\Onphp\HttpRequest $request)
	{
		return $this->resolveAction($request);
	}

	/**
	 * Возвращает модель для отображения информации об объекте
	 * @param $request HttpRequest
	 * @return \Onphp\ModelAndView
	**/
	protected function infoProcess(\Onphp\HttpRequest $request)
	{
		$prm = $this->helper->getImportPrimitive();
		$prm->import($request->getGet());
		$infoObject = $prm->getValue();

		if (!$infoObject) {
			return $this->getMav('index', 'NotFound');
		}

		$infoAction = $this->getInfoAction();
		if (!$this->getLinker()->isObjectSupported($infoObject, $infoAction)) {
			throw new PermissionException("No permission for action '{$infoAction}' with object '{$this->getClassName()}'");
		}

		$model = \Onphp\Model::create()->
			set('infoObject', $infoObject)->
			set('customInfoFieldsData', $this->helper->getCustomInfoFieldsData($infoObject))->
			set('orderFunction', $this->getFunctionListOrder())->
			set('buttonUrlList', $this->getButtonUrlList($infoObject))->
			set('windowOnce', $this->getWindowOnce());
		return $this->getMav('info', null, $model);
	}

	/**
	 * Возвращает модель с данными для редактирования объекта (форму)
	 * @param $request HttpRequest
	 * @return \Onphp\ModelAndView
	**/
	protected function editProcess(\Onphp\HttpRequest $request)
	{
		$prm = $this->helper->getImportPrimitive();
		$result = $prm->import($request->getGet());
		if ($result === false) {
			return $this->getMav('index', 'NotFound'); //id setted but nothing found
		}

		$action = $this->getEditAction($prm->getValue());
		if (!$this->getLinker()->isObjectSupported($prm->getValue() ?: $this->getClassName(), $action)) {
			throw new PermissionException('No permission for edit '.$this->getClassName());
		}

		$form = $this->getObjectProto()->makeForm()->drop('id')->add($prm);
		$subject = \Onphp\ClassUtils::callStaticMethod("{$this->getClassName()}::create");

		$command = $this->helper->getEditCommand();
		/* @var $command \Onphp\EditorCommand */
		$mav = $command->run($subject, $form, $request);

		return $this->getEditMav($form, $subject, $mav->getModel());
	}

	/**
	 * Валидирует данные для сохранения в объект,
	 * если данные валидны - выполняет операцию сохранения объекта и возвращает редирект на просмотр объекта
	 * если данные не валидны - отмечает не валидные примитивы в форме
	 *  и возвращает форму для продолжения редактирования
	 * @param $request HttpRequest
	 * @return \Onphp\ModelAndView
	**/
	protected function saveProcess(\Onphp\HttpRequest $request)
	{
		$prm = $this->helper->getImportPrimitive();
		$result = $prm->import($request->getGet());
		if ($result === false) {
			return $this->getMav('index', 'NotFound'); //id setted but nothing found
		}

		$action = $this->getSaveAction($prm->getValue());
		if (!$this->getLinker()->isObjectSupported($prm->getValue() ?: $this->getClassName(), $action)) {
			throw new PermissionException('No permission for edit '.$this->getClassName());
		}

		$form = $this->getObjectProto()->makeForm()->drop('id')->add($prm);
		$subject = \Onphp\ClassUtils::callStaticMethod("{$this->getClassName()}::create");

		$command = $this->helper->getEditCommand();
		/* @var $command \Onphp\EditorCommand */
		if ($this->helper->inTransaction() || $command instanceof CommandInTransaction) {
			$command = new \Onphp\CarefulDatabaseRunner($command);
		}

		$mav = $command->run($subject, $form, $request);

		if ($mav->getView() != \Onphp\EditorController::COMMAND_SUCCEEDED) {
			if ($command instanceof \Onphp\CarefulCommand) {
				$command->rollback();
			}
			FormErrorTextApplier::create()->apply($form);
			return $this->getEditMav($form, $subject, $mav->getModel());
		}

		if ($command instanceof \Onphp\CarefulCommand) {
			$command->commit();
		}

		if ($this->serviceLocator->get('isAjax')) {
			$isNew = (bool) $request->hasGetVar('id') ? $request->getGetVar('id') : false;
			$model = \Onphp\Model::create()->
				set('isNew', $isNew)->
				set('infoObject', $subject)->
				set('infoUrl', $this->getUrlInfo($subject))->
				set('closeDialog', $this->toCloseDialog($subject))
				;
			return $this->getMav('edit.success', null, $model);
		}

		return $this->getMavRedirectByUrl($this->getUrlInfo($subject));
	}

	protected function dropProcess(\Onphp\HttpRequest $request)
	{
		$prm = $this->helper->getImportPrimitive();
		$result = $prm->import($request->getGet());
		if (!$result) {
			return $this->getMav('index', 'NotFound'); //id setted but nothing found
		}
		$subject = $prm->getValue();

		$action = $this->getDropAction();
		if (!$this->getLinker()->isObjectSupported($subject, $action)) {
			throw new PermissionException("No permission for {$action} with {$this->getClassName()}");
		}

		$confirmed = $request->hasGetVar('confirm');

		if (!$confirmed) {
			$model = \Onphp\Model::create()->
				set('infoObject', $subject)->
				set('dropUrl', $this->getUrlDrop($subject, true))->
				set('infoUrl', $this->getUrlInfo($subject));
			return $this->getMav('drop.confirm', null, $model);
		}

		$command = $this->helper->getDropCommand();
		/* @var $command \Onphp\DropCommand */
		$mav = $command->run($subject, $form, $request);

		if ($mav->getView() != \Onphp\EditorController::COMMAND_SUCCEEDED) {
			return $this->getEditMav($form, $subject, $mav->getModel());
		}

		if ($this->serviceLocator->get('isAjax')) {
			$model = \Onphp\Model::create()->
				set('infoObject', $subject)->
				set('infoUrl', $this->getUrlInfo($subject))->
				set('id', $request->getGetVar('id'));
			return $this->getMav('drop.success', null, $model);
		}

		return $this->getMavRedirectByUrl($this->getUrlInfo($subject));
	}

	protected function getEditMav(\Onphp\Form $form, \Onphp\IdentifiableObject $subject, \Onphp\Model $commandModel)
	{
		$infoObject = $form->getValue('id') ?: $subject;
		$model = \Onphp\Model::create()->
			set('form', $form)->
			set('infoObjectPrototype', $subject)->
			set('infoObject', $infoObject)->
			set('commandModel', $commandModel)->
			set('customEditFieldsData', $this->helper->getCustomEditFieldsData($form, $subject))->
			set('orderFunction', $this->getFunctionListOrder())->
			set('infoUrl', $this->getUrlInfo($infoObject))->
			set('takeUrl', $this->getUrlSave($infoObject))->
			set('closeDialog', $this->toCloseDialog($infoObject))->
			set('windowOnce', $this->getWindowOnce())
			;
		$linker = $this->getLinker();
		if ($linker->isObjectSupported($infoObject, $this->getDropAction())) {
			$model->set('dropUrl', $this->getUrlDrop($infoObject));
		}

		return $this->getMav('edit', null, $model);
	}

	/**
	 * Возвращает имя класса бизнес объекта с которым работает данный контроллер
	 * По умолчанию для удобства это обрезанное название текущего контроллера (убрана часть controller)
	 * @return string
	 */
	protected function getClassName() {
		return $this->helper->getClassName();
	}

	/**
	 * Возвращает прото объекта, с которым происходит работа в текущем контроллере
	 * @return \Onphp\AbstractProtoClass
	 */
	protected function getObjectProto() {
		return $this->helper->getProto();
	}

	/**
	 * Возвращает дефолтный путь к директории с шаблонами
	 * @return string
	 */
	protected function getViewPath() {
		return 'Objects/SimpleObject';
	}

	/**
	 * Возвращает массив ассоциативный названий-действий
	 * - url'ов действий которые можно делать пользователю с объектом
	 * @param type $infoObject
	 */
	protected function getButtonUrlList(\Onphp\IdentifiableObject $infoObject) {
		$linker = $this->getLinker();
		/* @var $linker \Onphp\Utils\ToolkitLinkUtils */
		$buttonList = array();
		if ($linker->isObjectSupported($infoObject, $this->getEditAction($infoObject))) {
			$buttonList['Edit'] = array(
				'window' => true,
				'url' => $this->getUrlEdit($infoObject),
			);
		}
		if ($linker->isObjectSupported($infoObject, $this->getDropAction())) {
			$buttonList['Drop'] = array(
				'window' => true,
				'url' => $this->getUrlDrop($infoObject),
			);
		}

		if ($logClass = $this->getLogClassName()) {
			if ($linker->isObjectSupported($this->getLogClassName(), $this->getInfoAction())) {
				$buttonList['Logs'] = array(
					'window' => false,
					'url' => $linker->getUrlLog($infoObject),
				);
			}
		}

		return $buttonList;
	}

	protected function getLogClassName() {
		return null;
	}

	protected function getUrlParams() {
		return array();
	}

	/**
	 * Возвращает url для просмотра свойств объекта
	 * @param \Onphp\IdentifiableObject $infoObject
	 * @return string
	 */
	protected function getUrlInfo(\Onphp\IdentifiableObject $infoObject) {
		return $this->getLinker()->getUrl($infoObject, array('action' => 'info') + $this->getUrlParams(), $this->getInfoAction());
	}

	/**
	 * Возвращает url для формы-редактирования объекта
	 * @param \Onphp\IdentifiableObject $infoObject
	 * @return string
	 */
	protected function getUrlEdit(\Onphp\IdentifiableObject $infoObject) {
		return $this->getLinker()->getUrl($infoObject, array('action' => 'edit') + $this->getUrlParams(), $this->getEditAction($infoObject));
	}

	/**
	 * Возвращает url для операции сохранения новых свойств из формы объекта
	 * @param \Onphp\IdentifiableObject $infoObject
	 * @return string
	 */
	protected function getUrlSave(\Onphp\IdentifiableObject $infoObject) {
		return $this->getLinker()->getUrl($infoObject, array('action' => 'save') + $this->getUrlParams(), $this->getSaveAction($infoObject));
	}

	protected function getUrlDrop(\Onphp\IdentifiableObject $infoObject, $confirm = false) {
		$urlParams = array('action' => 'drop') + $this->getUrlParams();
		if ($confirm)
			$urlParams['confirm'] = '1';

		return $this->getLinker()->getUrl($infoObject, $urlParams, $this->getDropAction());
	}

	/**
	 * @return \Onphp\Utils\ToolkitLinkUtils
	 */
	protected function getLinker() {
		return $this->helper->getLinker();
	}

	protected function toCloseDialog(\Onphp\IdentifiableObject $subject) {
		return false;
	}

	protected function getCallbackLog() {
		return null;
	}

	protected function getInfoAction()
	{
		return $this->helper->getInfoAction();
	}

	protected function getEditAction($object = null)
	{
		return $this->helper->getEditAction();
	}

	protected function getSaveAction($object = null)
	{
		return $this->helper->getSaveAction();
	}

	protected function getDropAction()
	{
		return $this->helper->getDropAction();
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

	private function getWindowOnce() {
		$options = array();
		if ($size = $this->helper->getWindowWidth()) {
			$options['width'] = $size;
		}
		if ($size = $this->helper->getWindowHeight()) {
			$options['width'] = $size;
		}
		return $options;
	}

	/**
	 * Возвращает анонимную функцию для сортировки ассоциативной массива в необходимом порядке
	 * @return
	 */
	private function getFunctionListOrder() {
		$indexList = $this->helper->getOrderFieldList();

		return function(array $dataList) use ($indexList) {
			$resultList = array();
			foreach ($indexList as $indexName) {
				if (array_key_exists($indexName, $dataList)) {
					$resultList[$indexName] = $dataList[$indexName];
					unset($dataList[$indexName]);
				}
			}
			$resultList += $dataList;
			return $resultList;
		};
	}
}
