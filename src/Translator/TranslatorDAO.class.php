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

	class TranslatorDAO implements ITranslator
	{
		/**
		 * @var IPhraseContainerDao
		 */
		private $dao = null;
		
		/**
		 * @return TranslatorDAO
		 */
		public static function create() {
			return new self;
		}
		
		/**
		 * @param IPhraseContainerDao $dao
		 * @return TranslatorDAO 
		 */
		public function setClassName(IPhraseContainerDAO $dao) {
			$this->dao = $dao;
			return $this;
		}
		
		public function trans($phraseName) {
			$translate = $this->dao->translate($phraseName);
			return ($translate !== null)
				? $translate
				: $this->proccessNoTranslation($phraseName);
		}
		
		protected function proccessNoTranslation($phraseName) {
			return $phraseName;
		}
	}
?>