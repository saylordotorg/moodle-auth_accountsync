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
 * @package    auth_accountsync
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2016 Remote Learner.net Inc http://www.remote-learner.net
 */

$string['auth_accountsyncdescription'] = 'This method verifies if a user\'s credentials match the credentials of a remote Moodle site; and creates a user account using the profile fields from the account on the remote site.';
$string['pluginname'] = 'Account sync';
$string['accountsync_heading'] = 'Account Sync';
$string['header_desc'] = 'Account Sync settings';
$string['accountsync_wstoken'] = 'Token';
$string['accountsync_wstoken_desc'] = 'Web service token needed to call a web service function from the remote site';
$string['accountsync_wsurl'] = 'URL';
$string['accountsync_wsurl_desc'] = 'URL of the remote Moodle site to make web service calls.';
$string['eventaccountsync_user_login'] = 'User logged in';
$string['eventaccountsync_login_fail'] = 'User login failed';
$string['eventaccountsync_remote_token_returned'] = 'Remote token received';
$string['eventaccountsync_web_service_error'] = 'Web service error';
$string['eventaccountsync_profile_field_sync_error'] = 'Profile field sync error';
$string['accountsync_syncfields'] = 'Sync custom profile fields';
$string['accountsync_syncfields_desc'] = 'Enable this setting to sync custom profile fields from the remote site to the';
$string['accountsync_wsservice'] = 'Web service name';
$string['accountsync_wsservice_desc'] = 'The name of the external service on the remote site';
$string['servertype'] = 'Server Type';
$string['servertype_desc'] = 'Select the type of remote server being connected to';
$string['moodle_server'] = 'Moodle';
$string['totara_server'] = 'Totara';
