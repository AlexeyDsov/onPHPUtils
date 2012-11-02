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

	class WebAppBufferHandler implements InterceptingChainHandler
	{
		/**
		 * @return \Onphp\Utils\WebAppBufferHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return \Onphp\Utils\WebAppBufferHandler
		 */
		public function run(InterceptingChain $chain)
		{
			ob_start();

			$chain->next();

			if (($pageContents = ob_get_contents()) !== '') {
				ob_end_flush();
			} else {
				ob_end_clean();
			}

			return $this;
		}
	}
?>