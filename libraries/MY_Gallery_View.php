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
class Gallery_View extends Gallery_View_Core {
	/**
	 * If script combining is enabled, remove this script from the list of scripts that will be
	 * combined into a single script element.
	 *
	 * @param $file  the file name or path of the script to exclude. If a path is specified then
	 *               it needs to be relative to DOCROOT. Just specifying a file name will result
	 *               in searching Kohana's cascading file system.
	 * @param $group the group of scripts this file was added to. defaults to "core"
	 */
	public function removeScript($file, $group="core") {
		if (($path = gallery::find_file("js", $file, false))) {
			if (isset($this->combine_queue["script"])) {
				unset($this->combine_queue["script"][$group][$path]);
			}
		}
	}

	/**
	 * If css combining is enabled, remove this css from the list of css that will be
	 * combined into a single style element.
	 *
	 * @param $file  the file name or path of the css to exclude. If a path is specified then
	 *               it needs to be relative to DOCROOT. Just specifying a file name will result
	 *               in searching Kohana's cascading file system.
	 * @param $group the group of css this file was added to. defaults to "core"
	 */
	public function removeCss($file, $group="core") {
		if (($path = gallery::find_file("css", $file, false))) {
			if (isset($this->combine_queue["css"])) {
				unset($this->combine_queue["css"][$group][$path]);
			}
		}
	}
}
