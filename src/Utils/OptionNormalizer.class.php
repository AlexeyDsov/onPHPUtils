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

namespace Onphp\Utils;

class OptionNormalizer
{
	public static function normal($options)
	{
		$default = [];
		$arrayOptions = [];
		foreach ($options as $name => $value) {
			if (is_array($value)) {
				$arrayOptions[$name] = $value;
			} else {
				$default[$name] = $value;
			}
		}

		if (empty($arrayOptions)) {
			return $default;
		}

		$allresults = [$default];
		foreach ($arrayOptions as $name => $value) {
			$results = [];
			foreach ($value as $realValue => $subOptions) {
				foreach ($allresults as $result) {
					if (is_array($subOptions)) {
						$results[] = $result + [$name => $realValue] + self::subNormal($subOptions);
					} else {
						$results[] = $result + [$name => $subOptions];
					}
				}
			}
			$allresults = $results;
		}
		return $allresults;
	}

	public static function subNormal($options)
	{
		return self::normal($options);
	}
}