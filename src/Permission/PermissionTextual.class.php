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

	class PermissionTextual extends PermissionSimple {

		private $msg = null;

		public function __construct($isAllowed, $msg = null)
		{
			parent::__construct($isAllowed);
			$this->msg = $msg;
		}

		/**
		 * @return boolean
		 */
		public function getMsg()
		{
			return $this->msg;
		}
	}