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

	final class CustomFormToObjectConverter extends \Onphp\ObjectBuilder
	{
		protected $getterName = '\Onphp\FormGetter';
		protected $setterName = '\Onphp\ObjectSetter';

		/**
		 * @return \Onphp\Utils\CustomFormToObjectConverter
		**/
		public static function create(\Onphp\EntityProto $proto)
		{
			return new self($proto);
		}

		/**
		 * @return \Onphp\Utils\CustomFormToObjectConverter
		**/
		public function setGetterName($getterName)
		{
			\Onphp\Assert::isString($getterName);
			\Onphp\Assert::isInstance($getterName, '\Onphp\PrototypedGetter');

			$this->getterName = $getterName;
			return $this;
		}

		public function getGetterName()
		{
			return $this->getterName;
		}

		/**
		 * @return \Onphp\Utils\CustomFormToObjectConverter
		**/
		public function setSetterName($setterName)
		{
			\Onphp\Assert::isString($setterName);
			\Onphp\Assert::isInstance($setterName, '\Onphp\PrototypedSetter');

			$this->setterName = $setterName;
			return $this;
		}

		public function getSetterName()
		{
			return $this->setterName;
		}

		public function cloneInnerBuilder($property)
		{
			return parent::cloneInnerBuilder($property)->
				setGetterName($this->getGetterName())->
				setSetterName($this->getSetterName());
		}

		/**
		 * @return \Onphp\FormGetter
		**/
		protected function getGetter($object)
		{
			\Onphp\Assert::isNotNull($this->getterName, 'You must set getterName before to use this converter');
			return new $this->getterName($this->proto, $object);
		}

		/**
		 * @return \Onphp\ObjectSetter
		**/
		protected function getSetter(&$object)
		{
			\Onphp\Assert::isNotNull($this->setterName, 'You must set setterName before to use this converter');
			return new $this->setterName($this->proto, $object);
		}
	}
?>