<?php
	class SimplePhpViewParametrizedToolkit extends SimplePhpViewParametrized
	{
		protected function trans($phraseName)
		{
			return $this->escape(
				$this->has('translator')
					? $this->get('translator')->trans($phraseName)
					: $phraseName
			);
		}
		
		protected function getButtonsJson($buttonsOptions) {
			$buttons = array();
			foreach ($buttonsOptions as $name => $options) {
				$buttons[$this->trans($name)] = $options;
			}
			return json_encode($buttons);
		}
		
		protected function objectLink($object) {
			$this->view('Objects/SimpleObject/objectLink', array('object' => $object));
		}
		
		protected function getEnumerationNameList(AbstractProtoClass $proto, $options, $propertyName) {
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $proto);
			
			$class = $property->getClassName();
			if (ClassUtils::isInstanceOf($class, 'Enumeration')) {
				$anyId = ClassUtils::callStaticMethod("{$class}::getAnyId");
				$exemplar = new $class($anyId);
				/* @var $exemplar Enumeration */
				return $exemplar->getNameList();
			} elseif (ClassUtils::isInstanceOf($class, 'Enum')) {
				return ClassUtils::callStaticMethod("$class::getNameList");
			} else {
				throw new WrongStateException($class . ' Must be instance of Enumeration or Enum');
			}
		}
		
		protected function isPrimitiveEnumeration(AbstractProtoClass $proto, $options, $propertyName) {
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $proto);
			$propertyType = isset($options[ListMakerProperties::OPTION_PROPERTY_TYPE])
				? $options[ListMakerProperties::OPTION_PROPERTY_TYPE]
				: ($property ? $property->getType() : null);
			return $propertyType == 'enumeration' || $propertyType == 'enum';
		}

		protected function isTimePrimitive(Form $form, $propertyName, $filterName) {
			$timePrimitiveList = array('PrimitiveTimestamp', 'PrimitiveTimestampTZ');

			return in_array(get_class($form->getValue($propertyName)->get($filterName)), $timePrimitiveList);
		}

		protected function isDatePrimitive(Form $form, $propertyName, $filterName) {
			$datePrimitiveList = array('PrimitiveDate');

			return in_array(get_class($form->getValue($propertyName)->get($filterName)), $datePrimitiveList);
		}

		protected function getFilteredValue(Form $form, $propertyName, $filterName, $propertyData) {
			if (!isset($propertyData[$filterName])) {
				return '';
			}

			return $this->escape($propertyData[$filterName]);
		}
	}
?>