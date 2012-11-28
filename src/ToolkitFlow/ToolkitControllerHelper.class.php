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
class ToolkitControllerHelper implements IServiceLocatorSupport
{
	use TServiceLocatorSupport;

	private $className = null;
	private $baseUrl = null;

	/**
	 * @param string $className
	 * @return ToolkitControllerHelper
	 */
	public function setClassName($className)
	{
		$this->className = $className;
		return $this;
	}

	public function getClassName()
	{
		return $this->className;
	}

	/**
	 * @return \Onphp\AbstractProtoClass
	 */
	public function getProto()
	{
		return \Onphp\ClassUtils::callStaticMethod($this->className.'::proto');
	}

	public function getBaseUrl()
	{
		return $this->baseUrl;
	}

	/**
	 * @param string $baseUrl
	 * @return \Onphp\Utils\ToolkitControllerHelper
	 */
	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = $baseUrl;
		return $this;
	}

	/**
	 * @param type $object
	 * @param type $method
	 * @return bool
	 */
	public function isObjectSupported($object, $method)
	{
		$this->getLinker()->isObjectSupported($object, $method);
	}

	/**
	 * @param type $object
	 * @param type $method
	 * @return Permission
	 */
	public function getObjectPermission($object, $method)
	{
		return $this->getLinker()->getObjectPermission($object, $method);
	}

	/*
	 * @return ToolkitLinkUtils
	 */
	public function getLinker()
	{
		return $this->getServiceLocator()->get('linker');
	}

	/**
	 * @return boolean
	 */
	public function showCreateButton()
	{
		return true;
	}
}
