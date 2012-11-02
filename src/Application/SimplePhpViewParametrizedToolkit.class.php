<?php
	namespace Onphp\Utils;

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
		
		protected function getEnumerationNameList(\Onphp\AbstractProtoClass $proto, $options, $propertyName) {
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $proto);
			
			$class = $property->getClassName();
			if (\Onphp\ClassUtils::isInstanceOf($class, '\Onphp\Enumeration')) {
				$anyId = \Onphp\ClassUtils::callStaticMethod("{$class}::getAnyId");
				$exemplar = new $class($anyId);
				/* @var $exemplar \Onphp\Enumeration */
				return $exemplar->getNameList();
			} elseif (\Onphp\ClassUtils::isInstanceOf($class, '\Onphp\Enum')) {
				return \Onphp\ClassUtils::callStaticMethod("$class::getNameList");
			} else {
				throw new \Onphp\WrongStateException($class . ' Must be instance of Enumeration or Enum');
			}
		}
		
		protected function isPrimitiveEnumeration(\Onphp\AbstractProtoClass $proto, $options, $propertyName) {
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $proto);
			$propertyType = isset($options[ListMakerProperties::OPTION_PROPERTY_TYPE])
				? $options[ListMakerProperties::OPTION_PROPERTY_TYPE]
				: ($property ? $property->getType() : null);
			return $propertyType == 'enumeration' || $propertyType == 'enum';
		}

		protected function isTimePrimitive(\Onphp\Form $form, $propertyName, $filterName) {
			$timePrimitiveList = array('\Onphp\PrimitiveTimestamp', '\Onphp\PrimitiveTimestampTZ');

			return in_array(get_class($form->getValue($propertyName)->get($filterName)), $timePrimitiveList);
		}

		protected function isDatePrimitive(\Onphp\Form $form, $propertyName, $filterName) {
			$datePrimitiveList = array('\Onphp\PrimitiveDate');

			return in_array(get_class($form->getValue($propertyName)->get($filterName)), $datePrimitiveList);
		}

		protected function getFilteredValue(\Onphp\Form $form, $propertyName, $filterName, $propertyData) {
			if (!isset($propertyData[$filterName])) {
				return '';
			}

			return $this->escape($propertyData[$filterName]);
		}
	}
?>