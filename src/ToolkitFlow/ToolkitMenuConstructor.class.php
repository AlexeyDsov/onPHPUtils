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
	 * Класс для создания верхнего меню в админке/тулките. Простейшее решение создания многоуровневых вкладок
	 */
	class ToolkitMenuConstructor
	{
		/**
		 * @var PermissionManager
		 */
		protected $permissionManager = null;
		/**
		 * @var IPermissionUser
		 */
		protected $user = null;

		/**
		 * @return ToolkitMenuConstructor
		 */
		public static function create()
		{
			return new self;
		}

		/**
		 * @param PermissionManager $permissionManager
		 * @return ToolkitMenuConstructor
		 */
		public function setPermissionManager(PermissionManager $permissionManager)
		{
			$this->permissionManager = $permissionManager;
			return $this;
		}

		/**
		 * @param IPermissionUser $user
		 * @return ToolkitMenuConstructor
		 */
		public function setUser(IPermissionUser $user)
		{
			$this->user = $user;
			return $this;
		}

		/**
		 * Создаем массив stdClass'ов, реализующих меню
		 * @return array
		 */
		final public function getMenuList()
		{
			$menuList = array();
			foreach ($this->getSimpleObjectList() as $objectName => $objectSetup) {
				if ($menuPart = $this->makeSimpleObjectMenu($objectName, $objectSetup)) {
					$menuList[$objectName] = $menuPart;
				}
			}
			
			foreach ($this->getCustomMenus() as $menu) {
				if ($menu) {
					$menuList[$menu->name] = $menu;
				}
			}

			return $this->postProcessMenu($menuList);
		}
		
		/**
		 * array(
		 *		'BusinessClassName' => array('Title1 in menu', 'Title2 in list menu'),
		 *		... 
		 * );
		 * @return array
		 */
		protected function getSimpleObjectList() {
			return array();
		}
		
		protected function getCustomMenus() {
			return array();
		}

		/**
		 * Mapping to move menus into submenus create submenues
		 * array(
		 *		'Server' => array(
		 *			'title' => 'Server',
		 *			'submenu' => array(
		 *				'ServerStatus',
		 *				'ServerChatCommand',
		 *				'Server',
		 *				'ServerConfig',
		 *				'ServerVarValue',
		 *			),
		 *		),
		 *	);
		 * @return array
		 */
		protected function getPostProcessMapping()
		{
			return array();
		}

		protected function checkCustomPermission($method, $class)
		{
			if (!$this->permissionManager || !$this->user) {
				return false;
			}

			if (!$this->permissionManager->hasPermission($this->user, $method, $class)) {
				return false;
			}

			return true;
		}

		private function postProcessMenu($menuList)
		{
			foreach ($this->getPostProcessMapping() as $menuName => $options) {

				$class = new stdClass();
				$class->name = $menuName;
				$class->title = $options['title'];

				$subMenuList = array();
				foreach ($options['submenu'] as $subMenuName) {
					if (isset($menuList[$subMenuName])) {
						$subMenu = $menuList[$subMenuName];
						if (empty($subMenuList)) {
							$class->url = $subMenu->url;
						}
						$subMenuList[$subMenuName] = $subMenu;
						unset($menuList[$subMenuName]);
					}
				}

				if (!empty($subMenuList)) {
					$class->submenu = $subMenuList;
					$menuList[$menuName] = $class;
				}
			}

			return $menuList;
		}

		/**
		 * Генерим простейшее меню, в
		 * @param string $name Латинское название объекта (префикс в названии контроллера)
		 * @param string $text Русское название объекта
		 * @return stdClass
		 */
		private function  makeSimpleObjectMenu($name, $text)
		{
			if (!$this->permissionManager || !$this->user) {
				return null;
			}

			if (!$this->permissionManager->hasPermission($this->user, 'info', $name)) {
				return null;
			}

			$class = new stdClass();
			$class->name = $name;
			$class->title = "Menu of $text[0]";
			$class->url = PATH_WEB_URL . "area={$name}List";

			$listSubclass = new stdClass();
			$listSubclass->name = "List";
			$listSubclass->title = "List of {$text[1]}";
			$listSubclass->url = PATH_WEB_URL . "area={$name}List";

			$class->submenu = array(
				$listSubclass,
			);
			return $class;
		}
	}
?>