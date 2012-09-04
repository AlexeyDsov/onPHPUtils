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

	class ListMakerFormBuilder {
		/**
		 * @var AbstractProtoClass
		 */
		protected $proto = null;
		protected $propertyList = array();

		protected $offsetName = 'offset';
		protected $limitName = 'limit';

		private $defaultLimit = 20;

		public function __construct(AbstractProtoClass $proto, array $propertyList) {
			$this->proto = $proto;
			$this->propertyList = $propertyList;
		}

		/**
		 * @return ListMakerFormBuilder
		 */
		public static function create(AbstractProtoClass $proto, array $propertyList) {
			return new static($proto, $propertyList);
		}

		/**
		 * @return string
		 */
		public function getOffsetName() {
			return $this->offsetName;
		}

		/**
		 * @var ListMakerFormBuilder
		 */
		public function setOffsetName($offsetName) {
			Assert::isString($offsetName);
			$this->offsetName = $offsetName;
			return $this;
		}

		/**
		 * @var ListMakerConstructor
		 */
		public function setLimitName($limitName) {
			Assert::isString($limitName);
			$this->limitName = $limitName;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getLimitName() {
			return $this->limitName;
		}

		/**
		 * @return ListMakerFormBuilder
		 */
		public function setDefaultLimit($limit) {
			Assert::isPositiveInteger($limit);
			$this->defaultLimit = $limit;
			return $this;
		}

		/**
		 * @return int
		 */
		public function getDefaultLimit() {
			return $rhis->defaultLimit;
		}

		/**
		 * @return ListMakerFormBuilder
		 */
		public function buildForm(Form $form = null) {
			if ($form === null) {
				$form = Form::create();
			}
			$this->
				initForm($form)->
				fillForm($form);

			return $form;
		}

		/**
		 * @param Form $form
		 * @return ListMakerFormBuilder
		 */
		protected function initForm(Form $form) {
			if ($form->getErrors() || $form->export()) {
				throw new WrongStateException('Form Already Imported');
			}

			return $this;
		}

		/**
		 * @param Form $form
		 * @return ListMakerFormBuilder
		 */
		protected function fillForm(Form $form) {
			$form->
				add(Primitive::integer($this->offsetName)->setMin(0)->setDefault(0))->
				add(Primitive::integer($this->limitName)->setMin(0)->setDefault($this->defaultLimit));
			
			foreach ($this->propertyList as $propertyName => $options) {
				if ($propertyForm = $this->makePropertyForm($propertyName)) {
					$propertyPrimitive = new PrimitiveFormCustom($propertyName);
					$propertyPrimitive->setForm($propertyForm);
					$form->add($propertyPrimitive);
				}
			}
			
			$this->addRuleDefaultOrder($form);
			
			return $this;
		}

		/**
		 * @param string $propertyName
		 * @return Form
		 */
		protected function makePropertyForm($propertyName) {
			$options = $this->propertyList[$propertyName];
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $this->proto);
			$propertyType = isset($options[ListMakerProperties::OPTION_PROPERTY_TYPE])
				? $options[ListMakerProperties::OPTION_PROPERTY_TYPE]
				: ($property ? $property->getType() : null);

			$prmitiveList = array();
			if (isset($options[ListMakerProperties::OPTION_FILTERABLE])) {
				$filters = $options[ListMakerProperties::OPTION_FILTERABLE];
				Assert::isArray($filters, "value for OPTION_FILTERABLE must be array");

				foreach ($filters as $filterName) {
					switch ($filterName) {
						case ListMakerProperties::OPTION_FILTERABLE_EQ:
						case ListMakerProperties::OPTION_FILTERABLE_GT:
						case ListMakerProperties::OPTION_FILTERABLE_GTEQ:
						case ListMakerProperties::OPTION_FILTERABLE_LT:
						case ListMakerProperties::OPTION_FILTERABLE_LTEQ:
						case ListMakerProperties::OPTION_FILTERABLE_ILIKE:
						case ListMakerProperties::OPTION_FILTERABLE_CONTAINS:
						case ListMakerProperties::OPTION_FILTERABLE_CONTAINS_EQ:
						case ListMakerProperties::OPTION_FILTERABLE_IS_CONTAINED_WITHIN:
						case ListMakerProperties::OPTION_FILTERABLE_IS_CONTAINED_WITHIN_EQ:
							$prmitiveList[] = $this->makePrimitiveComparison($filterName, $propertyType);
							break;
						case ListMakerProperties::OPTION_FILTERABLE_IS_NULL:
						case ListMakerProperties::OPTION_FILTERABLE_IS_NOT_NULL:
						case ListMakerProperties::OPTION_FILTERABLE_IS_TRUE:
						case ListMakerProperties::OPTION_FILTERABLE_IS_NOT_TRUE:
						case ListMakerProperties::OPTION_FILTERABLE_IS_FALSE:
						case ListMakerProperties::OPTION_FILTERABLE_IS_NOT_FALSE:
							$prmitiveList[] = $this->makePrimitiveTernaryLogic($filterName);
							break;
						case ListMakerProperties::OPTION_FILTERABLE_IN:
							$prmitiveList[] = $this->makePrimitiveIn($filterName, $propertyType);
							break;
						default:
							throw new UnimplementedFeatureException('Unkown filter name: '.$filterName);
					}
				}
			}

			if (isset($options[ListMakerProperties::OPTION_ORDERING])) {
				$prmitiveList[] = Primitive::integer('order')->setMin(1);
				$prmitiveList[] = Primitive::plainChoice('sort')->
					setList(array(ListMakerProperties::ORDER_ASC, ListMakerProperties::ORDER_DESC))->
					setDefault(
						$options[ListMakerProperties::OPTION_ORDERING] == ListMakerProperties::ORDER_DESC
							? ListMakerProperties::ORDER_DESC
							: ListMakerProperties::ORDER_ASC
					);
			}

			if (empty($prmitiveList)) {
				return null;
			}

			$form = Form::create();
			foreach ($prmitiveList as $primitive) {
				$form->add($primitive);
			}

			return $form;
		}

		protected function makePrimitiveComparison($filterName, $propertyType) {
			switch ($propertyType) {
				case 'identifier':
				case 'identifierList':
				case 'integerIdentifier':
				case 'enumeration':
				case 'enum':
				case 'integer':
					return Primitive::integer($filterName);
				case 'float':
					return Primitive::float($filterName);
				case 'timestamp':
					return Primitive::timestamp($filterName)->setSingle();
				case 'timestampTZ':
					$prm = Primitive::timestampTZ($filterName);
					if ($prm instanceof ComplexPrimitive)
						$prm->setSingle();
					return $prm;
				case 'date':
					return Primitive::date($filterName)->setSingle();
				case 'string':
				case 'scalarIdentifier':
				case 'inet':
				case 'httpUrl':
					return Primitive::string($filterName);
				case 'boolean':
					$errorMsg = "Для propertyType 'boolean' операции сравнения невозможны";
					throw new UnimplementedFeatureException($errorMsg);
				default:
					$errorMsg = "С данным типом LightMetaProperty не описана работа: '{$propertyType}'";
					throw new UnimplementedFeatureException($errorMsg);
			}
			Assert::isUnreachable();
		}

		protected function makePrimitiveTernaryLogic($filterName) {
			return Primitive::boolean($filterName);
		}

		protected function makePrimitiveIn($filterName, $propertyType) {
			switch ($propertyType) {
				case 'identifier':
				case 'identifierList':
				case 'integerIdentifier':
				case 'enumeration':
				case 'enum':
				case 'integer':
					$primitive = new PrimitiveArray($filterName);
					$filter = Filter::pcre()->setExpression('~[^\d]~iu', '');
					return $primitive->addImportFilter($filter);
				case 'timestamp':
				case 'date':
				case 'string':
				case 'scalarIdentifier':
					return new PrimitiveArray($filterName);
				case 'boolean':
					$errorMsg = "Для propertyType 'boolean' операции IN невозможны";
					throw new UnimplementedFeatureException($errorMsg);
				default:
					$errorMsg = "С данным типом LightMetaProperty не описана работа IN: {$propertyType}";
					throw new UnimplementedFeatureException($errorMsg);
			}
			Assert::isUnreachable();
		}
		
		private function addRuleDefaultOrder(Form $form) {
			$properties = array();
			$default = null;
			foreach ($this->propertyList as $propertyName => $options) {
				if (isset($options[ListMakerProperties::OPTION_ORDERING]))
					$properties[] = $propertyName;
				if (!$default && isset($options[ListMakerProperties::OPTION_DEFAULT_ORDER]))
					$default = $propertyName;
			}
			
			if (empty($properties))
				return;
			
			if (!$default) {
				$default = reset($properties);
			}
				
			$callback = CallbackLogicalObjectSuccess::create(function (Form $form) use ($properties, $default) {
				foreach ($properties as $property) {
					if ($form->getValue($property)->getValue('order'))
						return;
				}
				
				$defaultSort = $form->getValue($default)->get('sort')->getDefault();
				if (!$form->get($default)->isImported()) 
					$form->importOne($default, array($default => array('order' => 1, 'sort' => $defaultSort)));
				else
					$form->getValue($default)
						->importValue('order', $default)
						->importValue('sort', $defaultSort);
			});
			
			$form->addRule('_ruleDefaultOrder', $callback);
		}
	}
?>