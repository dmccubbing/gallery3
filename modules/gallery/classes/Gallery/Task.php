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
class Gallery_Task {
  /**
   * Get all available tasks
   */
  static function get_definitions() {
    $tasks = array();
    foreach (Module::active() as $module) {
      $class_name = "Hook_" . Inflector::convert_module_to_class_name($module->name) . "Task";
      if (class_exists($class_name) && method_exists($class_name, "available_tasks")) {
        foreach (call_user_func(array($class_name, "available_tasks")) as $task) {
          $tasks[$task->callback] = $task;
        }
      }
    }

    return $tasks;
  }

  static function start($task_callback, $context=array()) {
    $tasks = Task::get_definitions();
    $task = Task::create($tasks[$task_callback], array());

    $task->log(t("Task %task_name started (task id %task_id)",
                 array("task_name" => $task->name, "task_id" => $task->id)));
    return $task;
  }

  static function create($task_def, $context) {
    $task = ORM::factory("Task");
    $task->callback = $task_def->callback;
    $task->name = $task_def->name;
    $task->percent_complete = 0;
    $task->status = "";
    $task->state = "started";
    $task->owner_id = Identity::active_user()->id;
    $task->context = serialize($context);
    $task->save();

    return $task;
  }

  static function cancel($task_id) {
    $task = ORM::factory("Task", $task_id);
    if (!$task->loaded()) {
      throw new Gallery_Exception("Missing task");
    }
    $task->done = 1;
    $task->state = "cancelled";
    $task->log(t("Task %task_name cancelled (task id %task_id)",
                 array("task_name" => $task->name, "task_id" => $task->id)));
    $task->save();

    return $task;
  }

  static function remove($task_id) {
    $task = ORM::factory("Task", $task_id);
    if ($task->loaded()) {
      $task->delete();
    }
  }

  static function run($task_id) {
    $task = ORM::factory("Task", $task_id);
    if (!$task->loaded()) {
      throw new Gallery_Exception("Missing task");
    }

    try {
      $task->state = "running";
      call_user_func_array($task->callback, array(&$task));
      if ($task->done) {
        $task->log($task->status);
      }
      $task->save();
    } catch (Exception $e) {
      Log::instance()->add(Log::ERROR, (string)$e);

      // Ugh.  I hate to use instanceof, But this beats catching the exception separately since
      // we mostly want to treat it the same way as all other exceptions
      if ($e instanceof ORM_Validation_Exception) {
        Log::instance()->add(Log::ERROR, "Validation errors: " . print_r($e->errors(), 1));
      }

      $task->log((string)$e);
      $task->state = "error";
      $task->done = true;
      $task->status = substr($e->getMessage(), 0, 255);
      $task->save();
    }

    return $task;
  }
}