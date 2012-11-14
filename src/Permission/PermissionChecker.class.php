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

interface PermissionChecker
{
	/**
	 * @param \Onphp\Utils\IPermissionUser $user
	 * @param string $method
	 * @param object $object
	 * @return boolean if return null then need check with next checker
	 */
	public function hasPermission(IPermissionUser $user, $method, $object);
}