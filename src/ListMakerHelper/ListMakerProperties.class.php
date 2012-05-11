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

	class ListMakerProperties
	{
		const OPTION_COLUMN = 'column';
		const OPTION_ORDERING = 'ordering';
		const OPTION_DEFAULT_ORDER = 'defaultOrder';
		const OPTION_FILTERABLE = 'filterable';
		const OPTION_FILTERABLE_EQ = 'eq';
		const OPTION_FILTERABLE_IN = 'in';
		const OPTION_FILTERABLE_GT = 'gt';
		const OPTION_FILTERABLE_GTEQ = 'gteq';
		const OPTION_FILTERABLE_LT = 'lt';
		const OPTION_FILTERABLE_LTEQ = 'lteq';
		const OPTION_FILTERABLE_IS_NULL = 'isNull';
		const OPTION_FILTERABLE_IS_NOT_NULL = 'isNotNull';
		const OPTION_FILTERABLE_IS_TRUE = 'isTrue';
		const OPTION_FILTERABLE_IS_NOT_TRUE = 'isNotTrue';
		const OPTION_FILTERABLE_IS_FALSE = 'isFalse';
		const OPTION_FILTERABLE_IS_NOT_FALSE = 'isNotFalse';
		const OPTION_FILTERABLE_ILIKE = 'ilike';
		const OPTION_FILTERABLE_IS_CONTAINED_WITHIN = 'isContainedWithin';
		const OPTION_FILTERABLE_CONTAINS = 'contains';
		const OPTION_FILTERABLE_IS_CONTAINED_WITHIN_EQ = 'isContainedWithinOrEq';
		const OPTION_FILTERABLE_CONTAINS_EQ = 'containsOrEq';
		const OPTION_DESCRIPTION = 'description';
		const OPTION_OBJECT_LINK = 'objectLink';
		const OPTION_SQL_FUNCTION = 'sqlFunction';
		const OPTION_VALUE_FUNCTION = 'valueFunction';
		const OPTION_PROPERTY_TYPE = 'propertyType';

		const ORDER_ASC = 'asc';
		const ORDER_DESC = 'desc';
		
		private static $labelMapping = array(		
			self::OPTION_FILTERABLE_EQ => '=',
			self::OPTION_FILTERABLE_IN => 'IN',
			self::OPTION_FILTERABLE_GT => '>',
			self::OPTION_FILTERABLE_GTEQ => '>=',
			self::OPTION_FILTERABLE_LT => '<',
			self::OPTION_FILTERABLE_LTEQ => '<=',
			self::OPTION_FILTERABLE_IS_NULL => 'is Null',
			self::OPTION_FILTERABLE_IS_NOT_NULL => 'Not Null',
			self::OPTION_FILTERABLE_IS_TRUE => 'True',
			self::OPTION_FILTERABLE_IS_NOT_TRUE => 'Not True',
			self::OPTION_FILTERABLE_IS_FALSE => 'False',
			self::OPTION_FILTERABLE_IS_NOT_FALSE => 'Not False',
			self::OPTION_FILTERABLE_ILIKE => 'ILIKE',
			self::OPTION_FILTERABLE_IS_CONTAINED_WITHIN => '<<',
			self::OPTION_FILTERABLE_CONTAINS => '>>',
			self::OPTION_FILTERABLE_IS_CONTAINED_WITHIN_EQ => '<<=',
			self::OPTION_FILTERABLE_CONTAINS_EQ => '>>=',
		);
		
		/**
		 * @param string $filterName
		 * @return string
		 */
		public static function getFilterLabel($filterName) {
			if (isset(self::$labelMapping[$filterName])) {
				return self::$labelMapping[$filterName];
	}
			return $filterName;
		}
	}
?>