<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	namespace Onphp\Utils;

	class InterceptingChain
	{
		protected $chain = array();

		protected $pos	= -1;

		/**
		 * @return \Onphp\Utils\InterceptingChain
		 */
		public static function create()
		{
			return new static;
		}

		/**
		 * @return \Onphp\Utils\InterceptingChain
		 */
		public function add(InterceptingChainHandler $handler)
		{
			$this->chain []= $handler;

			return $this;
		}

		public function getHandlers()
		{
			return $this->chain;
		}

		public function next()
		{
			$this->pos++;

			if (isset($this->chain[$this->pos])) {
				$handler = $this->chain[$this->pos];
				/* @var $handler \Onphp\Utils\InterceptingChainHandler */
				$handler->run($this);
				$this->checkHandlerResult($handler);
			}

			return $this;
		}

		/**
		 * @return \Onphp\Utils\InterceptingChain
		 */
		public function run()
		{
			$this->pos = -1;

			$this->next();

			return $this;
		}

		protected function checkHandlerResult(InterceptingChainHandler $handler)
		{
			return $this;
		}
	}
?>