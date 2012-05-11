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

	/**
	 * Реализует методы класса пользователя, необходимые для получения разрешенного ему списка действий
	 */
	interface ILoginUserDigestDAO {
		
		/**
		 * Возвращает пользователя по уникальному параметру (имя или например email)
		 * В случае отсутсвия пользователя возаращает null
		 * @return ILoginUserDigestDAO | null
		 */
		public function findByAuthParam($name);
	}