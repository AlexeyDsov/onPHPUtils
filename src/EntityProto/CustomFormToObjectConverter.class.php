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

	final class CustomFormToObjectConverter extends ObjectBuilder
	{
		protected $getterName = 'FormGetter';
		protected $setterName = 'ObjectSetter';

		/**
		 * @return CustomFormToObjectConverter
		**/
		public static function create(EntityProto $proto)
		{
			return new self($proto);
		}

		/**
		 * @return CustomFormToObjectConverter
		**/
		public function setGetterName($getterName)
		{
			Assert::isString($getterName);
			Assert::isInstance($getterName, 'PrototypedGetter');

			$this->getterName = $getterName;
			return $this;
		}

		public function getGetterName()
		{
			return $this->getterName;
		}

		/**
		 * @return CustomFormToObjectConverter
		**/
		public function setSetterName($setterName)
		{
			Assert::isString($setterName);
			Assert::isInstance($setterName, 'PrototypedSetter');

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
		 * @return FormGetter
		**/
		protected function getGetter($object)
		{
			Assert::isNotNull($this->getterName, 'You must set getterName before to use this converter');
			return new $this->getterName($this->proto, $object);
		}

		/**
		 * @return ObjectSetter
		**/
		protected function getSetter(&$object)
		{
			Assert::isNotNull($this->setterName, 'You must set setterName before to use this converter');
			return new $this->setterName($this->proto, $object);
		}
	}
?>