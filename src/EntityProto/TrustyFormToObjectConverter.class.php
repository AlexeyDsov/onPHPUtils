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

	final class TrustyFormToObjectConverter extends ObjectBuilder
	{
		/**
		 * @var Form
		 */
		protected $form = null;

		/**
		 * @return TrustyFormToObjectConverter
		**/
		public static function create(EntityProto $proto)
		{
			return new self($proto);
		}

		/**
		 * @return FormGetter
		**/
		protected function getGetter($object)
		{
			return new FormGetter($this->proto, $object);
		}

		/**
		 * @return ObjectSetter
		**/
		protected function getSetter(&$object)
		{
			return new ObjectSetter($this->proto, $object);
		}

		public function upperFill($object, &$result)
		{
			$this->form = $object;
			return parent::upperFill($object, $result);
		}

		public function make($object, $recursive = true) {
			$this->form = $object;
			return parent::make($object, $recursive);
		}

		protected function getFormMapping() {
			Assert::isInstance($this->form, 'Form', 'use setForm first');
			return $this->form->getPrimitiveList();
		}
	}
?>