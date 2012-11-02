<?php
/***************************************************************************
 *   Copyright (C) 2011-2012 by Aleksey S. Denisov                         *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	/**
	 * @ingroup Flow
	**/
	namespace Onphp\Utils;

	class CustomPhpView extends \Onphp\EmptyView
	{
		protected $templatePath		= null;
		protected $partViewResolver	= null;

		/**
		 * @var \Onphp\PartViewer
		 */
		protected $partViewer = null;

		public function __construct($templatePath, \Onphp\ViewResolver $partViewResolver)
		{
			$this->templatePath = $templatePath;
			$this->partViewResolver = $partViewResolver;
		}

		/**
		 * @return \Onphp\SimplePhpView
		**/
		public function render(/* Model */ $model = null)
		{
			\Onphp\Assert::isTrue($model === null || $model instanceof \Onphp\Model);

			if ($model)
				extract($model->getList());

			$partViewer = new \Onphp\PartViewer($this->partViewResolver, $model);

			$this->preRender($partViewer);

			include $this->templatePath;

			$this->postRender($partViewer);

			return $this;
		}

		public function toString($model = null)
		{
			ob_start();
			try {
				$this->render($model);
			} catch (\Exception $e) {
				ob_end_clean();
				throw $e;
			}
			return ob_get_clean();
		}

		/**
		 * @return \Onphp\SimplePhpView
		**/
		protected function preRender(\Onphp\PartViewer $partViewer)
		{
			$this->partViewer = $partViewer;
			return $this;
		}

		/**
		 * @return \Onphp\SimplePhpView
		**/
		protected function postRender(\Onphp\PartViewer $partViewer)
		{
			$this->partViewer = null;
			return $this;
		}
	}
?>
