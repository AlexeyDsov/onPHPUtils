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
	 * Комманда для редактирования объектов через toolkit со списком разрешенных для редактирования полей
	 */
	class TakeEditToolkitCommandByFields extends TakeEditToolkitCommand {

		/**
		 * Список разрешенных для редактирования полей-пропертей
		 * @var array
		 */
		protected $editableFieldList = array();

		/**
		 * Устаналивает список редактируемых через комманду пропертей объекта
		 * @param array $fieldList
		 */
		public function setEditableFieldList(array $fieldList) {
			$this->editableFieldList = $fieldList;
			if (!array_search('id', $fieldList)) {
				$this->editableFieldList[] = 'id';
			}

			return $this;
		}

		/**
		 * Базовая настройка формы - копирует в прототип нередактируемые свойства
		 * @return TakeEditTemplateCommand
		 */
		protected function prepairForm(Prototyped $subject, Form $form, HttpRequest $request) {
			Assert::isNotEmpty($this->editableFieldList, "call before self::setEditableFieldList");
			parent::prepairForm($subject, $form, $request);

			if ($old = $form->getValue('id')) {
				ToolkitFormUtils::setUpProtoSubject($subject, $old, $this->editableFieldList);
			}

			return $this;
		}

		/**
		 * Импортирование/подготовка/доп.валидация формы перед сохранением объекта
		 *   Удаление из полной формы полей, не разрешенных для редактирования
		 * @return TakeEditTemplateCommand
		 */
		protected function prepairFormTakeImport(IdentifiableObject $subject, Form $form, HttpRequest $request) {
			parent::prepairFormTakeImport($subject, $form, $request);

			ToolkitFormUtils::dropFormValuesNotInList($form, $this->editableFieldList);
			ToolkitFormUtils::markGoodValuesNotInList($form, $this->editableFieldList);

			return $this;
		}
	}