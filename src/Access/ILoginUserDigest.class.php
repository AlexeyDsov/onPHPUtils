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
	interface ILoginUserDigest extends Identifiable, DAOConnected {
		
		/**
		 * Возвращает область авторизации
		 */
		public static function getRealm();
		
		/**
		 * Возвращает хэш пароля пользователя
		 */
		public function getPasswordHash();
		
		/**
		 * Устанавливает хэш пароля пользователя
		 */
		public function setPasswordHash($passwordHash);
		
		/**
		 * Возвращает текуйщий уникальный ключ авторизации
		 */
		public function getLoginKey();
		
		/**
		 * Устанавливает пользователю текущий уникальный ключ авториазции
		 */
		public function setLoginKey($loginKey);
	}