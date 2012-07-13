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
	 * Комманда для редактирования объектов через toolkit.
	 * Логгирует состояние объекта до и после сохранения.
	 */
	class TakeEditToolkitCommand extends TakeEditTemplateCommand implements IServiceLocatorSupport {
		
		use TServiceLocatorSupport;
		
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
		 * Выполнение сохранения изменений объекта в базу и логиирование изменений
		 * @param Form $form
		 * @param IdentifiableObject $subject
		 * @return IdentifiableObject
		 */
		final protected function takeObject(Form $form, IdentifiableObject $subject) {
			$logData = array('command' => get_class($this), 'formData' => $form->export());
			if ($oldObject = $form->getValue('id')) {
				$oldObjectDump = $this->getLogOldData($form, $oldObject);
			}

			$subject = parent::takeObject($form, $subject);

			$newObjectDump = $this->getLogNewData($form, $subject);
			if (isset($oldObjectDump)) {
				$objectDiff = $this->getDiffData($newObjectDump, $oldObjectDump);
			}

			$logData = array(
				'command' => get_class($this),
				'objectDiff' => isset($objectDiff) ? $objectDiff : 'not exists',
				'oldObjectDump' => isset($oldObjectDump) ? $oldObjectDump : 'not exists',
				'newObjectDump' => $newObjectDump,
			);

			$this->logData($logData, $subject);

			return $subject;
		}

		/**
		 * Возвращает ассоциативный массив соотвествующий текущим параметрам объекта до изменения
		 * @param Form $form
		 * @param IdentifiableObject $oldObject
		 * @return array
		 */
		protected function getLogOldData(Form $form, IdentifiableObject $oldObject) {
			return $this->getLogObjectData($form, $oldObject);
		}

		/**
		 * Возвращает ассоциативный массив соотвествующий текущим параметрам объекта после изменения
		 * @param Form $form
		 * @param IdentifiableObject $subject
		 * @return array
		 */
		protected function getLogNewData(Form $form, IdentifiableObject $subject) {
			return $this->getLogObjectData($form, $subject);
		}

		/**
		 * Возвращает ассоциативный массив соотвествующий текущим параметрам объекта
		 * @param Form $form
		 * @param IdentifiableObject $subject
		 * @return type
		 */
		protected function getLogObjectData(Form $form, IdentifiableObject $subject) {
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

		final protected function getDiffData($newData, $oldData) {
			$diff = array();
			foreach ($newData as $newKey => $newValue) {
				if (isset($oldData[$newKey])) {
					$oldValue = $oldData[$newKey];
					unset($oldData[$newKey]);
					if ($oldValue === $newValue) {
						continue;
					} elseif ($this->isStringable($oldValue) && $this->isStringable($newValue)) {
						if ($oldValue instanceof Stringable) {
							$oldValue = $oldValue->toString();
						}
						if ($newValue instanceof Stringable) {
							$newValue = $newValue->toString();
						}
						$diff[$newKey.'+/-'] = $newValue.'/'.$oldValue;
					} elseif (is_array($oldValue) && is_array($newValue)) {
						if ($this->isIndexizeArray($oldValue) && $this->isIndexizeArray($newValue)) {
							$oldValue = !empty($oldValue) ? array_combine($oldValue, $oldValue) : array();
							$newValue = !empty($newValue) ? array_combine($newValue, $newValue) : array();
						}
						$diff[$newKey.'+/-'] = $this->getDiffData($newValue, $oldValue);
					} else {
						$diff[$newKey.'+/-'] = 'someobject';
					}

				} else {
					$diff[$newKey.'+'] = $newValue;
				}
			}
			foreach ($oldData as $oldKey => $oldValue) {
				$diff[$oldKey.'-'] = $oldValue;
			}
			return $diff;
		}

		private function isIndexizeArray($array) {
			$i = 0;
			foreach ($array as $key => $value) {
				if ($key != $i++) {
					return false;
				} elseif (!is_scalar($value)) {
					return false;
				}
			}
			return true;
		}

		private function isStringable($value) {
			return is_scalar($value)
				|| ($value instanceof Stringable)
				|| ($value === null)
				;
		}
	}