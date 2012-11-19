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

	class PermissionSimple implements Permission {

		private $isAllowed = null;

		public function __construct($isAllowed)
		{
			$this->isAllowed = ($isAllowed == true);
		}

		/**
		 * @return boolean
		 */
		public function isAllowed()
		{
			return $this->isAllowed;
		}
	}