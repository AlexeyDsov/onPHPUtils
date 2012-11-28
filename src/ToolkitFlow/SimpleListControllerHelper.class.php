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
class SimpleListControllerHelper extends ToolkitControllerHelper
{
	use TServiceLocatorSupport;

	private $propertyList = [];

	/**
	 * @return array
	 */
	public function getPropertyList()
	{
		return $this->propertyList;
	}

	/**
	 * @param array $propertyList
	 * @return \Onphp\Utils\SimpleListControllerHelper
	 */
	public function setPropertyList(array $propertyList = [])
	{
		$this->propertyList = $propertyList;
		return $this;
	}

	/**
	 * @param \Onphp\AbstractProtoClass $proto
	 * @param array $propertyList
	 * @return \Onphp\Utils\ListMakerFormBuilder
	 */
	public function getListMakerFormBuilder(\Onphp\AbstractProtoClass $proto, array $propertyList)
	{
		return ListMakerFormBuilder::create($proto, $propertyList);
	}

	/**
	 * @param \Onphp\AbstractProtoClass $proto
	 * @param array $propertyList
	 * @return \Onphp\Utils\ListMakerConstructor
	 */
	public function getListMakerConstructor($className, array $propertyList)
	{
		return ListMakerConstructor::create($className, $propertyList);
	}

	public function showInfo()
	{
		return true;
	}

	public function getPageLimit()
	{
		return 20;
	}
}
