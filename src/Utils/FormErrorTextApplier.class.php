<?php
	namespace Onphp\Utils;

	class FormErrorTextApplier {

		/**
		 * @return \Onphp\Utils\FormErrorTextApplier
		 */
		public static function create() {
			return new self;
		}
		
		/**
		 * @param \Onphp\Form $\Onphp\Form
		 */
		public function apply(\Onphp\Form $form) {
			foreach ($form->getInnerErrors() as $field => $code)
				$this->applyField($form, $field, $code);
		}
		
		private function applyField(\Onphp\Form $form, $field, $code) {
			if (is_array($code)) {
				$this->applySubField($form, $field);
			}
			
			$code = $form->getError($field) ?: \Onphp\Form::WRONG;
			
			if ($form->getTextualErrorFor($field))
				return;
			
			switch ($code) {
				case \Onphp\Form::WRONG:
					$label = "Wrong value"; break;
				case \Onphp\Form::MISSING:
					$label = "Missing value"; break;
				default:
					$label = "Custom error"; break;
			}
			$form->addCustomLabel($field, $code, $label);
		}
		
		private function applySubField(\Onphp\Form $form, $field) {
			$value = $form->getValue($field);
			if ($value instanceof \Onphp\Form) {
				$this->apply($value);
			} elseif (is_array($value) && count($value) && reset($value) instanceof \Onphp\Form) {
				foreach ($value as $valueEl)
					$this->apply($valueEl);
			}
		}
	}