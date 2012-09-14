<?php
	class FormErrorTextAggregator {

		/**
		 * @return FormErrorTextAggregator
		 */
		public static function create() {
			return new self;
		}
		
		public function getMessage(Form $form, $preField = null) {
			$msg = '';
			foreach ($form->getInnerErrors() as $field => $innerCode) {
				$msg .= ($msg ? "\n" : '')
					.$this->getMessageField($form, $field, $innerCode, $preField);
			}
			return $msg;
		}
		
		private function getMessageField(Form $form, $field, $innerCode, $preField = null) {
			$msg = '';
			if ($error = $form->getTextualErrorFor($field)) {
				$msg = ($preField ? "$preField." : '')."{$field}: ".$error;
			}
			
			if (is_array($innerCode)) {
				if ($subMsg = $this->getInnerMessage($form, $field, $preField)) {
					$msg .= ($msg ? "\n" : '').$subMsg;
				}
			}
			return $msg;
		}
		
		private function getInnerMessage(Form $form, $field, $preField = null) {
			$value = $form->getValue($field);
			
			if ($value instanceof Form) {
				return $this->getMessage(
					$form->getValue($value),
					($preField ? "$preField." : '').$field
				);
			} elseif (is_array($value) && count($value) && reset($value) instanceof Form) {
				$msg = '';
				foreach ($value as $key => $valueEl) {
					$msg .= ($msg ? "\n" : '')
						. $this->getMessage(
							$valueEl,
							($preField ? "$preField." : '').$field."[$key]"
						);
				}
				return $msg;
			}
		}
	}