<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey S. Denisov                               *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	/**
	 * @ingroup Primitives
	**/
	class PrimitiveFormCustom extends BasePrimitive
	{
		/**
		 * @param Form $form
		 * @return PrimitiveFormCustom
		 */
		public function setForm(Form $form)
		{
			$this->value = $form;
			return $this;
		}
		
		/**
		 * @throws WrongArgumentException
		 * @return PrimitiveForm
		**/
		public function dropForm(Form $form) {
			$this->value = null;
			return $this;
		}

		/**
		 * @throws WrongArgumentException
		 * @return PrimitiveForm
		**/
		public function setValue($value)
		{
			Assert::isInstance($value, 'Form');

			return parent::setValue($value);
		}

		/**
		 * @throws WrongArgumentException
		 * @return PrimitiveForm
		**/
		public function importValue($value)
		{
			if ($value !== null) {
				Assert::isTrue($value instanceof Form);
			}

			$this->value = $value;

			return ($value->getErrors() ? false : true);
		}

		public function exportValue()
		{
			if (!$this->value)
				return null;

			return $this->value->export();
		}

		public function getInnerErrors()
		{
			if ($this->value)
				return $this->value->getInnerErrors();

			return array();
		}

		public function import($scope)
		{
			return $this->actualImport($scope, true);
		}

		public function unfilteredImport($scope)
		{
			return $this->actualImport($scope, false);
		}
		
		public function clean() {
			$this->raw = null;
			$this->value->clean();
			$this->imported = false;
			
			return $this;
		}

		private function actualImport($scope, $importFiltering)
		{
			if (!$this->value)
				throw new WrongStateException(
					"use setForm before for primitive '{$this->name}'"
				);

			if (!isset($scope[$this->name]))
				return null;

			$this->rawValue = $scope[$this->name];

			if (!$importFiltering) {
				$this->value->
					disableImportFiltering()->
					import($this->rawValue)->
					enableImportFiltering();
			} else {
				$this->value->import($this->rawValue);
			}

			$this->imported = true;
			$this->value->checkRules();

			if ($this->value->getErrors())
				return false;

			return true;
		}
	}
?>