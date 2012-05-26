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
	 * @hack
	 * Хак что бы у классов находящихся в этом проекты было от кого наследоваться
	 * Этот класс должен быть в настоящем проекте и в списке автолоада должен идти первым
	 * 
	 * Сделано по дурацки, в будуйщем надо SimpleListController и SimpleObjectFlowController
	 *  подключать к контроллерам проекта иначе, а не через прямое наследование от них
	 */
	abstract class ToolkitBaseController extends BaseController implements IServiceLocatorSupport {
		
		protected $serviceLocator = null;
		
		public function setServiceLocator(IServiceLocator $serviceLocator) {
			$this->serviceLocator = $serviceLocator;
			return $this;
		}
		
		public function getServiceLocator() {
			return $this->serviceLocator;
		}
	}
