<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Account sync
 *
 * @package    auth_accountsync
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2016 Remote Learner.net Inc http://www.remote-learner.net
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__).'/lib.php');

$component = AUTH_ACCOUNTSYNC_COMP_NAME;
$settings->add(new \admin_setting_heading('accountsync_heading', '', get_string('header_desc', $component)));

$field = 'accountsync_wstoken';
$title = get_string($field, $component);
$desc = get_string($field.'_desc', $component);
$setting = new \admin_setting_configtext($field, $title, $desc, '', PARAM_TEXT);
$setting->plugin = $component;
$settings->add($setting);

$field = 'accountsync_wsurl';
$title = get_string($field, $component);
$desc = get_string($field.'_desc', $component);
$setting = new \admin_setting_configtext($field, $title, $desc, '', PARAM_URL);
$setting->plugin = $component;
$settings->add($setting);

$field = 'accountsync_wsservice';
$title = get_string($field, $component);
$desc = get_string($field.'_desc', $component);
$setting = new \admin_setting_configtext($field, $title, $desc, '', PARAM_TEXT);
$setting->plugin = $component;
$settings->add($setting);

$field = 'accountsync_syncfields';
$title = get_string($field, $component);
$desc = get_string($field.'_desc', $component);
$setting = new \admin_setting_configcheckbox($field, $title, $desc, 1);
$setting->plugin = $component;
$settings->add($setting);

$field = 'servertype';
$title = get_string($field, $component);
$desc = get_string($field.'_desc', $component);
$moodledesc = get_string('moodle_server', $component);
$totaradesc = get_string('totara_server', $component);
$serverlist = array($moodledesc, $totaradesc);
$setting = new admin_setting_configselect($field, $title, $desc, 'moodle', $serverlist);
$setting->plugin = $component;
$settings->add($setting);
