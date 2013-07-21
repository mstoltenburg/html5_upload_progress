<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2013 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
class html5_upload_progress_Core {

	/**
	 * Format byte values considering current locale
	 *
	 * @param integer	$bytes		file size in bytes
	 *
	 * @return string	human readable size
	 */
	static function pimp_my_bytes($bytes) {
		$format	= localeconv();
		$units	= array('bytes', 'KB', 'MB', 'GB', 'TB');

		$i		= 0;
		$decimals	= 0;
		$float	= $bytes;
		$unit	= $units[$i];

		while ($float >= 1024 && count($units) > ++$i) {
			$float	/= 1024;
			$unit	= $units[$i];
		}

		if (is_float($float)) {
			if ($float < 10) {
				$decimals	= 2;
			}
			elseif ($float < 100) {
				$decimals	= 1;
			}
		}

		return number_format($float, $decimals, $format['decimal_point'], $format['thousands_sep']) . ' ' . $unit;
	}
}
