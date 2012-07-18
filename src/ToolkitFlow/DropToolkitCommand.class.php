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
	 * Удаляет объект, но прежде логгирует это удаление
	 */
	class DropToolkitCommand extends DropCommand {
		
		/**
		 * @var Closure
		 */
		protected $logCallback = null;
		
		/**
		 * @param Closure $logCallback
		 * @return TakeEditToolkitCommand 
		 */
		public function setLogCallback(Closure $logCallback) {
			$this->logCallback = $logCallback;
			return $this;
		}
		
		/**
		 * @return ModelAndView
		**/
		public function run(Prototyped $subject, Form $form, HttpRequest $request) {
			if ($object = $form->getValue('id')) {
				$this->logObject($object);
			}
			return parent::run($subject, $form, $request);
		}

		/**
		 * Выполнение сохранения изменений объекта в базу и логиирование изменений
		 * @param IdentifiableObject $object
		 * @return IdentifiableObject
		 */
		final protected function logObject(IdentifiableObject $object) {
			$logData = array(
				'command' => get_class($this),
				'dropData' => $this->getLogObjectData($object),
			);

			$this->logData($logData, $object);
		}

		/**
		 * Возвращает ассоциативный массив соотвествующий текущим параметрам объекта
		 * @param IdentifiableObject $subject
		 * @return array
		 */
		protected function getLogObjectData(IdentifiableObject $subject) {
			$newSubjectForm = $subject->proto()->makeForm();
			FormUtils::object2form($subject, $newSubjectForm);
			return $newSubjectForm->export();
		}

		/**
		 * @param array $data
		 * @param IdentifiableObject $subject
		 * @return TakeEditToolkitCommand
		 */
		protected function logData($data, IdentifiableObject $subject) {
			if ($this->logCallback) {
				$this->logCallback->__invoke($data, $subject);
			}
			return $this;
		}
	}