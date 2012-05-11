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

	class TranslatorTrasparentToolkit implements ITranslator
	{
		/**
		 * @return TranslatorTrasparentToolkit
		 */
		public static function create() {
			return new self;
		}
		
		public function trans($phraseName) {
			if (preg_match('~.\.property\.([\w\d]+)$~ius', $phraseName, $matches)) {
				return $matches[1];
			}
			return $phraseName;
		}
	}
?>