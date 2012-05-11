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
	abstract class TakeEditTemplateCommand implements EditorCommand {

		protected $actionMethod = 'action';

		/**
		 * Заполняет форму и сохраняет объект по форме
		 * @return ModelAndView
		**/
		final public function run(Prototyped $subject, Form $form, HttpRequest $request) {
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

				return ModelAndView::create()->setModel($this->getModel($subject, $form));
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
						return ModelAndView::create()->
							setView(EditorController::COMMAND_FAILED)->
							setModel($this->getModel($subject, $form));
					}
					return ModelAndView::create()->
						setModel($this->getModel($subject, $form))->
						setView(EditorController::COMMAND_SUCCEEDED);
				} else {
					$this->prepairErrorsForm($subject, $form, $request);
					return ModelAndView::create()->
						setModel($this->getModel($subject, $form))->
						setView(EditorController::COMMAND_FAILED);
				}
			} else {
				throw new WrongStateException("Неожиданный {$this->actionMethod}  = ".$action);
			}
			throw new WrongStateException('Выполнение функции должно окончится одним из return, выше в коде');
		}

		/**
		 * Базовая настройка формы
		 * @return TakeEditTemplateCommand
		 */
		protected function prepairForm(Prototyped $subject, Form $form, HttpRequest $request) {
			$form->importOne('id', $request->getGet())->importOneMore('id', $request->getPost());
			return $this;
		}

		/**
		 * Подготовка формы для редактирования объекта (уже с id)
		 * @return TakeEditTemplateCommand
		 */
		protected function prepairEditForm(IdentifiableObject $object, Form $form, HttpRequest $request) {
			FormUtils::object2form($object, $form);
			return $this;
		}

		/**
		 * Подготовка формы для редактирования нового объекта (без id)
		 * @return TakeEditTemplateCommand
		 */
		protected function prepairEditNewForm(IdentifiableObject $subject, Form $form, HttpRequest $request) {
			return $this;
		}

		/**
		 * Импортирование/подготовка/доп.валидация формы перед сохранением объекта
		 * @return TakeEditTemplateCommand
		 */
		protected function prepairFormTakeImport(IdentifiableObject $subject, Form $form, HttpRequest $request) {
			$form->importMore($request->getPost())->checkRules();
			return $this;
		}

		/**
		 * Выполнение сохранения изменений объекта в базу
		 * @param Form $form
		 * @param IdentifiableObject $subject
		 * @return IdentifiableObject
		 */
		protected function takeObject(Form $form, IdentifiableObject $subject) {
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
		 * @param Form $form
		 * @param IdentifiableObject $subject
		 * @return IdentifiableObject
		 */
		protected function prepairSubjectByForm(Form $form, IdentifiableObject $subject) {
			FormUtils::form2object($form, $subject, true);
			return $subject;
		}

		/**
		 * Выполняет дополнительные операции после сохранения/обновления основного объекта
		 * @param Form $form
		 * @param IdentifiableObject $subject
		 * @return TakeEditTemplateCommand
		 */
		protected function postTakeActions(Form $form, IdentifiableObject $subject) {
			return $this;
		}

		/**
		 * Получение нового идентификатора объекта
		 * @param IdentifiableObject $subject
		 * @return IdentifiableObject
		 */
		protected function fillNewId(IdentifiableObject $subject) {
			return $subject->setId(
				DBPool::getByDao($subject->dao())->obtainSequence(
					$subject->dao()->getSequence()
				)
			);
		}

		/**
		 * Заполнение формы ошибками в случае если данные не прошли валидацию
		 * @param Prototyped $subject
		 * @param Form $form
		 * @param HttpRequest $request
		 * @return TakeEditTemplateCommand
		 */
		protected function prepairErrorsForm(Prototyped $subject, Form $form, HttpRequest $request) {
			return $this;
		}

		/**
		 * @return Model
		 */
		protected function getModel(Prototyped $subject, Form $form) {
			return Model::create();
		}

		/**
		 * Определяет edit или take сейчас будет выполняться
		 * @return string
		 */
		protected function resolveActionForm(HttpRequest $request) {
			$actionList = array('edit', 'take');

			$form = Form::create()->
				add(
					Primitive::plainChoice($this->actionMethod)->
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