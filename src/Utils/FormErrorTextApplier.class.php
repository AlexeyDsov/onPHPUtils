<?php
	class FormErrorTextApplier {

		/**
		 * @return FormErrorTextApplier
		 */
		public static function create() {
			return new self;
		}
		
		/**
		 * @param Form $form
		 */
		public function apply(Form $form) {
			foreach ($form->getInnerErrors() as $field => $code)
				$this->applyField($form, $field, $code);
		}
		
		private function applyField(Form $form, $field, $code) {
			if (is_array($code)) {
				$this->applySubField($form, $field);
			}
			
			$code = $form->getError($field) ?: Form::WRONG;
			
			if ($form->getTextualErrorFor($field))
				return;
			
			switch ($code) {
				case Form::WRONG:
					$label = "Wrong value"; break;
				case Form::MISSING:
					$label = "Missing value"; break;
				default:
					$label = "Custom error"; break;
			}
			$form->addCustomLabel($field, $code, $label);
		}
		
		private function applySubField(Form $form, $field) {
			$value = $form->getValue($field);
			if ($value instanceof Form) {
				$this->apply($value);
			} elseif (is_array($value) && count($value) && reset($value) instanceof Form) {
				foreach ($value as $valueEl)
					$this->apply($valueEl);
			}
		}
	}