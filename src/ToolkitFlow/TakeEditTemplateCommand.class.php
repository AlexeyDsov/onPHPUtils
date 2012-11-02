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
	 * Комманда заполняющая форму для редактирования объекта и обновляющая измененные поля объекта
	 */
	namespace Onphp\Utils;

	abstract class TakeEditTemplateCommand implements \Onphp\EditorCommand {

		protected $actionMethod = 'action';

		/**
		 * Заполняет форму и сохраняет объект по форме
		 * @return \Onphp\ModelAndView
		**/
		final public function run(\Onphp\Prototyped $subject, \Onphp\Form $form, \Onphp\HttpRequest $request) {
			$this->prepairForm($subject, $form, $request);
			$action = $this->resolveActionForm($request);

			if ($action == 'edit') {
				//действие edit - заполнение формы из объекта в базе, если он есть или какими-то дефолтными значениями
				if ($object = $form->getValue('id')) {
					$this->prepairEditForm($object, $form, $request);
				} else {
					$this->prepairEditNewForm($subject, $form, $request);
				}
				$form->dropAllErrors();

				return \Onphp\ModelAndView::create()->setModel($this->getModel($subject, $form));
			} elseif ($action == 'take') {
				//действие take - заполняем форму из реквеста,
				//если ошибок нет - переносим данные в объект и сохраняем его
				$this->prepairFormTakeImport($subject, $form, $request);
				if (!$form->getValue('id')) {
					$form->markGood('id');
				}

				if (!$errors = $form->getErrors()) {
					try {
						$this->takeObject($form, $subject);
					} catch (TakeEditTemplateCommandException $e) {
						$this->prepairErrorsForm($subject, $form, $request);
						return \Onphp\ModelAndView::create()->
							setView(\Onphp\EditorController::COMMAND_FAILED)->
							setModel($this->getModel($subject, $form));
					}
					return \Onphp\ModelAndView::create()->
						setModel($this->getModel($subject, $form))->
						setView(\Onphp\EditorController::COMMAND_SUCCEEDED);
				} else {
					$this->prepairErrorsForm($subject, $form, $request);
					return \Onphp\ModelAndView::create()->
						setModel($this->getModel($subject, $form))->
						setView(\Onphp\EditorController::COMMAND_FAILED);
				}
			} else {
				throw new \Onphp\WrongStateException("Неожиданный {$this->actionMethod}  = ".$action);
			}
			throw new \Onphp\WrongStateException('Выполнение функции должно окончится одним из return, выше в коде');
		}

		/**
		 * Базовая настройка формы
		 * @return \Onphp\Utils\TakeEditTemplateCommand
		 */
		protected function prepairForm(\Onphp\Prototyped $subject, \Onphp\Form $form, \Onphp\HttpRequest $request) {
			$form->importOne('id', $request->getGet())->importOneMore('id', $request->getPost());
			return $this;
		}

		/**
		 * Подготовка формы для редактирования объекта (уже с id)
		 * @return \Onphp\Utils\TakeEditTemplateCommand
		 */
		protected function prepairEditForm(\Onphp\IdentifiableObject $object, \Onphp\Form $form, \Onphp\HttpRequest $request) {
			\Onphp\FormUtils::object2form($object, $form);
			return $this;
		}

		/**
		 * Подготовка формы для редактирования нового объекта (без id)
		 * @return \Onphp\Utils\TakeEditTemplateCommand
		 */
		protected function prepairEditNewForm(\Onphp\IdentifiableObject $subject, \Onphp\Form $form, \Onphp\HttpRequest $request) {
			return $this;
		}

		/**
		 * Импортирование/подготовка/доп.валидация формы перед сохранением объекта
		 * @return \Onphp\Utils\TakeEditTemplateCommand
		 */
		protected function prepairFormTakeImport(\Onphp\IdentifiableObject $subject, \Onphp\Form $form, \Onphp\HttpRequest $request) {
			$form->importMore($request->getPost())->checkRules();
			return $this;
		}

		/**
		 * Выполнение сохранения изменений объекта в базу
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\IdentifiableObject $subject
		 * @return \Onphp\IdentifiableObject
		 */
		protected function takeObject(\Onphp\Form $form, \Onphp\IdentifiableObject $subject) {
			$subject = $this->prepairSubjectByForm($form, $subject);
			if ($form->getValue('id')) {
				$subject = $subject->dao()->merge($subject, false);
			} else {
				$subject = $subject->dao()->import($this->fillNewId($subject));
			}
			$this->postTakeActions($form, $subject);

			return $subject;
		}

		/**
		 * Импортирование данных из формы в объект
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\IdentifiableObject $subject
		 * @return \Onphp\IdentifiableObject
		 */
		protected function prepairSubjectByForm(\Onphp\Form $form, \Onphp\IdentifiableObject $subject) {
			\Onphp\FormUtils::form2object($form, $subject, true);
			return $subject;
		}

		/**
		 * Выполняет дополнительные операции после сохранения/обновления основного объекта
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\IdentifiableObject $subject
		 * @return \Onphp\Utils\TakeEditTemplateCommand
		 */
		protected function postTakeActions(\Onphp\Form $form, \Onphp\IdentifiableObject $subject) {
			return $this;
		}

		/**
		 * Получение нового идентификатора объекта
		 * @param \Onphp\IdentifiableObject $subject
		 * @return \Onphp\IdentifiableObject
		 */
		protected function fillNewId(\Onphp\IdentifiableObject $subject) {
			return $subject->setId(
				\Onphp\DBPool::getByDao($subject->dao())->obtainSequence(
					$subject->dao()->getSequence()
				)
			);
		}

		/**
		 * Заполнение формы ошибками в случае если данные не прошли валидацию
		 * @param \Onphp\Prototyped $subject
		 * @param \Onphp\Form $\Onphp\Form
		 * @param \Onphp\HttpRequest $request
		 * @return \Onphp\Utils\TakeEditTemplateCommand
		 */
		protected function prepairErrorsForm(\Onphp\Prototyped $subject, \Onphp\Form $form, \Onphp\HttpRequest $request) {
			return $this;
		}

		/**
		 * @return \Onphp\Model
		 */
		protected function getModel(\Onphp\Prototyped $subject, \Onphp\Form $form) {
			return \Onphp\Model::create();
		}

		/**
		 * Определяет edit или take сейчас будет выполняться
		 * @return string
		 */
		protected function resolveActionForm(\Onphp\HttpRequest $request) {
			$actionList = array('edit', 'take');

			$form = \Onphp\Form::create()->
				add(
					\Onphp\Primitive::plainChoice($this->actionMethod)->
						setList($actionList)->
						setDefault('edit')->
						required()
				)->
				import($request->getGet())->
				importMore($request->getPost());

			if ($form->getErrors()) {
				return 'edit';
			}

			return $form->getSafeValue($this->actionMethod);
		}
	}