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

	final class TrustyFormToObjectConverter extends \Onphp\ObjectBuilder
	{
		/**
		 * @var \Onphp\Form
		 */
		protected $form = null;

		/**
		 * @return \Onphp\Utils\TrustyFormToObjectConverter
		**/
		public static function create(\Onphp\EntityProto $proto)
		{
			return new self($proto);
		}

		/**
		 * @return \Onphp\FormGetter
		**/
		protected function getGetter($object)
		{
			return new \Onphp\FormGetter($this->proto, $object);
		}

		/**
		 * @return \Onphp\ObjectSetter
		**/
		protected function getSetter(&$object)
		{
			return new \Onphp\ObjectSetter($this->proto, $object);
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
			\Onphp\Assert::isInstance($this->form, '\Onphp\Form', 'use setForm first');
			return $this->form->getPrimitiveList();
		}
	}
?>