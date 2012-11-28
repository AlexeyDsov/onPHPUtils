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

/**
 * Реализует отображение списков объектов.
 * В наследнике класса необходимо указать proto объекта и propertyList - настройки для получения списка
 */
class SimpleObjectFlowControllerHelper extends ToolkitControllerHelper
{
	use TServiceLocatorSupport;

	public function getEditCommandName()
	{
		return '\Onphp\Utils\TakeEditToolkitCommand';
	}

	/**
	 * @return \Onphp\EditorCommand
	 */
	public function getDropCommandName()
	{
		return '\Onphp\Utils\DropToolkitCommand';
	}

	/**
	 * @return \Onphp\EditorCommand
	 */
	public function getEditCommand()
	{
		$command = $this->getServiceLocator()->spawn($this->getEditCommandName());

		if ($command instanceof TakeEditToolkitCommand) {
			if ($callbackLog = $this->getCallbackLog()) {
				\Onphp\Assert::isTrue(is_callable($callbackLog), 'callbackLog must be callable');
				$command->setLogCallback($callbackLog);
			}
		}

		$editableFields = $this->getEditableFields();
		if ($command instanceof TakeEditToolkitCommandByFields && $editableFields) {
			$command->setEditableFieldList($editableFields);
		}

		return $command;
	}

	public function getDropCommand()
	{
		$command = $this->getServiceLocator()->spawn($this->getDropCommandName());

		if ($command instanceof DropToolkitCommand) {
			if ($callbackLog = $this->getCallbackLog()) {
				\Onphp\Assert::isTrue(is_callable($callbackLog), 'callbackLog must be callable');
				$command->setLogCallback($callbackLog);
			}
		}

		return $command;
	}

	public function getCallbackLog()
	{
		return null;
	}

	/**
	 * @return \Onphp\PrimitiveIdentifier
	 */
	public function getImportPrimitive()
	{
		$proto = \Onphp\ClassUtils::callStaticMethod($this->getClassName().'::proto');
		/* @var $proto \Onphp\AbstractProtoClass */
		$proto->getPropertyByName('id')->fillForm($form = \Onphp\Form::create());
		return $form->get('id');
	}

	public function inTransaction()
	{
		return false;
	}

	public function getInfoAction()
	{
		return 'info';
	}

	public function getEditAction()
	{
		return 'edit';
	}

	public function getSaveAction()
	{
		return 'save';
	}

	public function getDropAction()
	{
		return 'drop';
	}

	public function getCustomInfoFieldsData(\Onphp\IdentifiableObject $infoObject)
	{
		return array();
	}

	public function getCustomEditFieldsData(\Onphp\Form $form, \Onphp\IdentifiableObject $subject)
	{
		return array();
	}

	/**
	 * Возвращает порядок сортировки провертей объекта при его отображении
	 * Все не перечисленные параметры будут оказываться после перечисленных в порядке по умолчнию
	 * @return array
	 */
	public function getOrderFieldList()
	{
		return array();
	}

	/**
	 * @return string (null | 100 | 100%)
	 */
	public function getWindowWidth()
	{
		return null;
	}

	/**
	 * @return string (null | 100 | 100%)
	 */
	public function getWindowHeight()
	{
		return null;
	}

	public function getEditableFields()
	{
		return array_keys($this->getProto()->getPropertyList());
	}

	final protected function getEmptyFieldData() {
		return array('tpl' => 'Objects/SimpleObject/empty');
	}

	final protected function getListFieldData($nameList) {
		return array(
			'tpl' => 'Objects/SimpleObject/edit.table.listField',
			'nameList' => $nameList,
		);
	}
}
