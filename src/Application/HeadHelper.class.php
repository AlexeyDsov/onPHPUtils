<?php
/***************************************************************************
*   Copyright (C) 2008 by Sergey S. Sergeev                                *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	class HeadHelper implements Stringable
	{
		const MAX_LENGTH_CONTEXT	= 200; // максимальная длина описания элемента метаинформации

		protected $description  = null;    // краткое описание страницы для поисковиков <meta name="description" content="">
		protected $index		= true;	   // флаг индексации поисковыми системами
		protected $rss			= array(); // массив двухмерных массивов - title и href тэга рсс файла <link rel="alternate" type="application/rss+xml" title="" href="" />
		protected $properties	= array(); //
		protected $prefix		= "\t";
		protected $metaList = array();

		/**
		 * @return HeadHelper
		 */
		public static function create()
		{
			return new self;
		}

		public function reset()
		{
			$this->properties = array();
		}

		public function setPrefix($string)
		{
			$this->prefix = $string;

			return $this;
		}

		/**
		 * @return MetaHelper
		 */
		public function setTitle($title)
		{
			return
				$this->set(
					'title',
					$this->lengthFilter(
						$this->escape($title),
						self::MAX_LENGTH_CONTEXT
					)
				);

			return $this;
		}

		public function getTitle()
		{
			return $this->get('title');
		}

		/**
		 * @return MetaHelper
		 */
		public function setDescription($description)
		{
			return
				$this->set(
					'description',
					$this->lengthFilter(
						$this->escape($description),
						self::MAX_LENGTH_CONTEXT
					)
				);

			return $this;
		}

		public function getDescription()
		{
			return $this->get('description');
		}

		/**
		 * Флаг индексации поисковиками
		 *
		 * @return MetaHelper
		 */
		public function setIndex($boolean)
		{
			$this->index = ($boolean === true);

			return $this;
		}

		public function isIndex()
		{
			return $this->index;
		}

		public function getIndex()
		{
			return $this->index;
		}

		/**
		 * @return MetaHelper
		 */
		public function setRss($title, $href)
		{
			$this->rss[] = array('title' => $this->escape($title), 'href' => $href);

			return $this;
		}

		/**
		 * @return array
		 */
		public function getRss()
		{
			return $this->rss;
		}

		/**
		 * @return HeadHelper
		 */
		public function addMetaHttpEquiv($httpEquiv, $content)
		{
			$this->metaList[] = array('http-equiv' => $httpEquiv, 'content' => $content);
			return $this;
		}

		/**
		 * @return HeadHelper
		 */
		public function addMetaName($name, $content)
		{
			$this->metaList[] = array('name' => $name, 'content' => $content);
			return $this;
		}

		public function toString()
		{
			$string = "{$this->prefix}<title>{$this->getTitle()}</title>\n";

			if ($this->getDescription()) {
				$string .= "{$this->prefix}<meta name=\"description\" content=\"{$this->getDescription()}\" />\n";
			}

			if ($this->isIndex()) {
				$string .= "{$this->prefix}<meta name=\"robots\" content=\"all\" />\n";
			} else {
				$string .= "{$this->prefix}<meta name=\"robots\" content=\"noindex,nofollow\" />\n";
			}

			foreach ($this->metaList as $metaData) {
				$metaParams = '';
				foreach ($metaData as $metaParam => $metaValue) {
					$metaParams .= $metaParam.'="'.Filter::htmlSpecialChars()->apply($metaValue).'" ';
				}
				$string .= "$this->prefix<meta {$metaParams} />\n";
			}

			if ($list = $this->getRss()) {
				foreach ($list as $rss) {
					$string .= "{$this->prefix}<link rel=\"alternate\" type=\"application/rss+xml\" title=\"{$rss['title']}\" href=\"{$rss['href']}\" />\n";
				}
			}

			return $string;
		}

		/********************* Protected Methods *********************/

		protected function getEntityHtmlFilter()
		{
	       return
	       		PCREFilter::create()->
	       		setExpression(
	       			array (
	       				'@(&|&amp;)(nbsp|&#160);@isu',
		       			'@(&|&amp;)(quot|&#34);@isu',
		       			'@(&|&amp;)(apos|&#39);@is',
		       			'@(&|&amp;)(laquo|&#171;);@isu',
		       			'@(&|&amp;)(raquo|&#187;);@isu',
		                '@(&|&amp;)(lt|#60);@isu',
		                '@(&|&amp;)(gt|#62);@isu',
		                '@(&|&amp;)(copy|#169);@isu',
		                '@(&|&amp;)(amp|#38);@isu',
		                '@(&|&amp;)(lsquo|#8216);@isu',
		                '@(&|&amp;)(rsquo|#8217);@isu',
		                '@(&|&amp;)(ldquo|#8220);@isu',
		                '@(&|&amp;)(rdquo|#8221);@isu',
		                '@(&|&amp;)?(sbquo);@isu',
		                '@(&|&amp;)#(\d+);@isu',
		                '@#(\d+);@isu',
		                '@(&|&amp;)?(mdash);@isu',
		                '@(&|&amp;)?(ndash);@isu',
	                ),
	       			array(
	       				' ',
		       			'',
		       			'',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '',
		                '-',
		                '–',
	       			)
	       		);
		}

		/**
		 * @return PCREFilter
		 */
		protected function getSpaceFilter()
		{
			return
				PCREFilter::create()->
	       		setExpression(
	       			array (
		               '/\s+/su', // любые пробелы
		               '/^\s+/su',// пробелы в начале строки
		               '/\s+$/su', // пробелы в конце строки
		               '/[\r?\n]+/su'
	       			),
	       			array (
		               ' ',
		               '',
		               '',
		               ''
		            )
	       		);
		}

		/**
		 * Цепочка фильтров для чистки контента
		 *
		 * @return FilterChain
		 */
		protected function getFilterChain()
		{
			static $fc = null;

			if ($fc === null)
				$fc =
					FilterChain::create()->
					add(Filter::replaceSymbols(array('<br/>', '<br />', '<br>'), ' '))->
					add(Filter::stripTags())->
					add(Filter::htmlSpecialChars())->
					add($this->getEntityHtmlFilter())->
					add($this->getSpaceFilter())->
					add(Filter::trim());

			return $fc;
		}

		/**
		 * @return string
		 */
		protected function escape($value)
		{
			return $this->getFilterChain()->apply($value);
		}

		protected function lengthFilter($value, $length)
		{
			if (is_array($value)) {
				$rezult = array();
				foreach ($value as $key => &$val)
					if (mb_strlen($val) > $length)
						$rezult[$key] = $val;

				return $rezult;
			} elseif (is_string($value)) {
				if (mb_strlen($value) <= $length)
					return $value;

				$arrayOfWords = explode(' ', mb_substr($value, 0, $length));

				return implode(' ', array_slice($arrayOfWords, 0, count($arrayOfWords)-1));
			} else {
				throw new UnsupportedMethodException();
			}
		}

		protected function get($property)
		{
			return
				isset($this->properties[$property])
					? $this->properties[$property]
					: '';
		}

		protected function set($property, $value)
		{
			$this->properties[$property] = $value;

			return $this;
		}
	}
?>