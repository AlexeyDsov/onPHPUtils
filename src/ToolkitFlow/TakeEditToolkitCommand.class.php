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
	namespace Onphp\Utils;

	class TakeEditToolkitCommand extends TakeEditTemplateCommand implements IServiceLocatorSupport {
		
		use TServiceLocatorSupport;
		
		/**
		 * @var \Closure
		 */
		protected $logCallback = null;
		
		/**
		 * @param \Closure $logCallback
		 * @return \Onphp\Utils\TakeEditToolkitCommand 
		 */
		public function setLogCallback(\Closure $logCallback) {
			$this->logCallback = $logCallback;
			return $this;
		}

		/**
		 * Выполнение сохранения изменений объекта в базу и логиирование изменений
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\IdentifiableObject $subject
		 * @return \Onphp\IdentifiableObject
		 */
		final protected function takeObject(\Onphp\Form $form, \Onphp\IdentifiableObject $subject) {
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
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\IdentifiableObject $oldObject
		 * @return array
		 */
		protected function getLogOldData(\Onphp\Form $form, \Onphp\IdentifiableObject $oldObject) {
			return $this->getLogObjectData($form, $oldObject);
		}

		/**
		 * Возвращает ассоциативный массив соотвествующий текущим параметрам объекта после изменения
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\IdentifiableObject $subject
		 * @return array
		 */
		protected function getLogNewData(\Onphp\Form $form, \Onphp\IdentifiableObject $subject) {
			return $this->getLogObjectData($form, $subject);
		}

		/**
		 * Возвращает ассоциативный массив соотвествующий текущим параметрам объекта
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\IdentifiableObject $subject
		 * @return type
		 */
		protected function getLogObjectData(\Onphp\Form $form, \Onphp\IdentifiableObject $subject) {
			$newSubjectForm = $subject->proto()->makeForm();
			\Onphp\FormUtils::object2form($subject, $newSubjectForm);
			return $newSubjectForm->export();
		}

		/**
		 * @param array $data
		 * @param \Onphp\IdentifiableObject $subject
		 * @return \Onphp\Utils\TakeEditToolkitCommand
		 */
		protected function logData($data, \Onphp\IdentifiableObject $subject) {
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
						if ($oldValue instanceof \Onphp\Stringable) {
							$oldValue = $oldValue->toString();
						}
						if ($newValue instanceof \Onphp\Stringable) {
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
				|| ($value instanceof \Onphp\Stringable)
				|| ($value === null)
				;
		}
	}