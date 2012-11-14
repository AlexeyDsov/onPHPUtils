<?php
/***************************************************************************
 *   Copyright (C) 2012 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

namespace Onphp\Utils;

class PermissionClassCheckerUser implements PermissionClassChecker
{
	/**
	 * @param \Onphp\Utils\IPermissionUser $user
	 * @param string $method
	 * @param string $className
	 * @return boolean if return null then need check with next checker
	 */
	public function hasPermissionClass(IPermissionUser $user, $method, $className)
	{
		return $user->hasAction($className.'.'.$method);
	}
}