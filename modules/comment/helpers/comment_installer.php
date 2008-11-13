<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2008 Bharat Mediratta
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
class comment_installer {
  public static function install() {
    Kohana::log("debug", "comment_installer::install");
    $db = Database::instance();
    $version = module::get_version("comment");
    Kohana::log("debug", "version: $version");

    if ($version == 0) {
      $db->query("CREATE TABLE IF NOT EXISTS `comments` (
          `id` int(9) NOT NULL auto_increment,
          `author` varchar(255) default NULL,
          `email` varchar(255) default NULL,
          `text` text,
          `datetime` int(9) NOT NULL,
          `item_id` int(9) NOT NULL,
          PRIMARY KEY (`id`))
        ENGINE=InnoDB DEFAULT CHARSET=utf8;");

      module::set_version("comment", 1);
    }
  }

  public static function uninstall() {
    $db = Database::instance();
    $db->query("DROP TABLE IF EXISTS `comments`;");
    module::delete("comment");
  }
}
