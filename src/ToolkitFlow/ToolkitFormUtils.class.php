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

	namespace Onphp\Utils;

	class ToolkitFormUtils extends \Onphp\StaticFactory {

		/**
		 * Копирует из $old объекта в $prototype все параметры кроме перечисленных в списке $workParams
		 * @param \Onphp\Prototyped $prototype
		 * @param \Onphp\Prototyped $old
		 * @param type $workParams
		 * @return type 
		 */
		public static function setUpProtoSubject(\Onphp\Prototyped $prototype, \Onphp\Prototyped $old, $workParams)
		{
			self::copyOldToSubject($prototype, $old);
			foreach ($workParams as $path) {
				self::dropSubjectProperty($prototype, $path);
			}

			return true;
		}

		/**
		 * Сбрасывает ошибки у примитивов формы не перечисленных в списке второым аргументом
		 * @param \Onphp\Form $\Onphp\Form
		 * @param type $primitiveNameList 
		 */
		public static function markGoodValuesNotInList(\Onphp\Form $form, $primitiveNameList)
		{
			$primitiveAList = array();
			foreach ($primitiveNameList as $primitiveName) {
				$primitiveAList[$primitiveName] = true;
			}
			$errors = $form->getErrors();
			foreach ($form->getPrimitiveList() as $primitiveName => $primitive) {
				if (!isset($primitiveAList[$primitiveName])) {
					if (isset($errors[$primitiveName]) && $errors[$primitiveName] == \Onphp\Form::MISSING) {
						$form->markGood($primitiveName);
					}
				}
			}
		}

		/**
		 * Сбрасывает значения примитивов формы у параметров не перечисленных в списке вторым аргументом
		 * @param \Onphp\Form $\Onphp\Form
		 * @param type $primitiveNameList
		 * @return type 
		 */
		public static function dropFormValuesNotInList(\Onphp\Form $form, $primitiveNameList)
		{
			$primitiveAList = array();
			foreach ($primitiveNameList as $primitiveName) {
				$primitiveAList[$primitiveName] = true;
			}
			foreach ($form->getPrimitiveList() as $primitiveName => $primitive) {
				if (!isset($primitiveAList[$primitiveName])) {
					$primitive->dropValue();
				} else {
					if (
						$primitive instanceof \Onphp\PrimitiveHstore
						|| $primitive instanceof \Onphp\PrimitiveForm
					) {
						$subPrimitiveNameList = array();
						foreach ($primitiveNameList as $subPrimitiveName) {
							if (mb_strpos($subPrimitiveName, $primitiveName.'.') === 0) {
								$subPrimitiveNameList[] = mb_substr(
									$subPrimitiveName,
									mb_strlen($primitiveName) + 1
								);
							}
						}

						if ($primitive instanceof \Onphp\PrimitiveFormsList) {
							if ($subFormList = $primitive->getValue()) {
								foreach ($subFormList as $subForm) {
									self::dropFormValuesNotInList(
										$subForm,
										$subPrimitiveNameList
									);
								}
							}
						} elseif ($primitive instanceof \Onphp\PrimitiveHstore || $primitive instanceof \Onphp\PrimitiveForm) {
							if (
								$subForm = (
									$primitive instanceof \Onphp\PrimitiveHstore
										? $primitive->getInnerForm()
										: $primitive->getValue()
								)
							) {
								self::dropFormValuesNotInList(
									$subForm,
									$subPrimitiveNameList
								);
							}
						}
					}
				}
			}

			return null;
		}

		protected static function copyOldToSubject(\Onphp\Prototyped $prototype, \Onphp\Prototyped $old)
		{
			\Onphp\Assert::isInstance($prototype, $old);
			$proto = $prototype->proto();
			foreach ($proto->getPropertyList() as $propertyName => $lightMeta) {
				if (($subOld = $old->{$lightMeta->getGetter()}()) !== null) {
					if ($lightMeta->getClassName() == '\Onphp\Hstore') {
						$prototype->{$lightMeta->getSetter()}(clone $subOld);
					} elseif ($lightMeta instanceof \Onphp\InnerMetaProperty) {
						if (($subPrototype = $prototype->{$lightMeta->getGetter()}()) === null) {
							$subPrototype = $subOld->create();
							$prototype->{$lightMeta->getSetter()}($subPrototype);
						}
						self::copyOldToSubject($subPrototype, $subOld);
					} elseif (
						$lightMeta->getRelationId() == 2
						|| $lightMeta->getRelationId() == 3
					) {
						/* с контейнором OneToMany или ManyToMany ничего не делаем */
					} else {
						$prototype->{$lightMeta->getSetter()}($subOld);
					}

				}
			}

			return true;
		}

		protected static function dropSubjectProperty(\Onphp\Prototyped $prototype, $path)
		{
			if ($prototype->proto()->isPropertyExists($path)) {
				//удаление обычных свойств и объектов
				$lightMeta = $prototype->proto()->getPropertyByName($path);
				if ($lightMeta->getClassName() === null || $path == 'id') {
					$prototype->{$lightMeta->getSetter()}(null);
				} else {
					$relationId = $lightMeta->getRelationId();
					if ($relationId === null || $relationId == 1) {
						$prototype->{$lightMeta->getDropper()}();
					}
				}
			} elseif (($dpos = mb_strpos($path, ':')) !== false) {
				//удаление свойств и объектов в ValueObject'е
				$paramName = mb_substr($path, 0, $dpos);
				if ($prototype->proto()->isPropertyExists($paramName)) {
					$lightMeta = $prototype->proto()->getPropertyByName($paramName);
					if ($subPrototype = $prototype->{$lightMeta->getGetter()}()) {
						self::dropSubjectProperty($subPrototype, mb_substr($path, $dpos + 1));
					}
				}
			} elseif (($dpos = mb_strpos($path, '.')) !== false) {
				//удаление свойств и объектов в Hstore'е
				$paramName = mb_substr($path, 0, $dpos);
				$subParamName = mb_substr($path, $dpos + 1);
				if ($prototype->proto()->isPropertyExists($paramName)) {
					$lightMeta = $prototype->proto()->getPropertyByName($paramName);
					if ($lightMeta->getClassName() == '\Onphp\Hstore') {
						if ($hstore = $prototype->{$lightMeta->getGetter()}()) {
							if ($hstore->isExists($subParamName)) {
								$hstore->drop($subParamName);
							}
						}
					}
				}
			}

			return true;
		}
	}
?>