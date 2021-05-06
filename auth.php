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

require_once($CFG->libdir.'/authlib.php');

class auth_plugin_accountsync extends auth_plugin_base {
    /**
     * The name of the component. Used by the configuration.
     */
    const COMPONENT_NAME = 'auth_accountsync';
    /**
     * The remote site's web service function.
     */
    const WS_FUNCTION = 'core_user_get_users_by_field';
    /** @var array A list of other profile fields.*/
    public $otherfields = array(
        'icq',
        'skype',
        'yahoo',
        'aim',
        'msn',
        'firstaccess',
        'lastaccess',
        'descriptionformat',
    );

    /**
     * Constructor, set authentication plugin configuration
     */
    public function __construct() {
        $this->authtype = 'accountsync';
        $this->config = get_config('auth_accountsync');

        // All standard and custom fields will be defaulted to sync.  This is required for when Moodle calls @see update_user_record_by_id().
        foreach ($this->userfields as $key => $fieldname) {
            $prop = "field_lock_".$fieldname;
            $this->config->$prop = 1;
            $prop = "field_updatelocal_".$fieldname;
            $this->config->$prop = 'onlogin';
        }
        $custfields = $this->get_custom_user_profile_fields();
        foreach ($custfields as $key => $fieldname) {
            $prop = "field_lock_".$fieldname;
            $this->config->$prop = 1;
            $prop = "field_updatelocal_".$fieldname;
            $this->config->$prop = 'onlogin';
        }
        foreach ($this->otherfields as $fieldname) {
            $prop = "field_lock_".$fieldname;
            $this->config->$prop = 1;
            $prop = "field_updatelocal_".$fieldname;
            $this->config->$prop = 'onlogin';
        }
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist. (Non-mnet accounts only!)
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        return $this->call_remote_token_script($username, $password);
    }

    /**
     * Indicates if moodle should automatically update internal user
     * records with data from external sources using the information
     * from get_userinfo() method.
     *
     * @return bool true means automatically copy data from ext to user table
     */
    function is_synchronised_with_external() {
        if ($this->config->accountsync_syncfields) {
            return true;
        }
        return false;
    }

    /**
     * Returns the user fields from the remote site by making a WS call.
     * @param string $username The user's username.
     * @return array An array of user properties both standard and custom.
     */
    public function get_userinfo($username) {
        $data = $this->retrieve_remote_user_profile($username);
        if (1 == $data['error']) {
            $event = \auth_accountsync\event\accountsync_profile_field_sync_error::create(array(
                'other' => array(
                    'message' => 'Reponse object of web service call contain an error.  Unable to sync profile fields from remote.',
                )
            ));
            $event->trigger();
            return parent::get_userinfo($username);
        }

        return $this->format_user_profile_data($data);
    }

    /**
     * Formats user data so that is can be used by @see create_user_record()
     * @param array $data An array with user profile data @see retrieve_remote_user_profile() return data.
     * @return array An array with custom profile field have a key of 'profile_field_<shortname>' and value.
     */
    public function format_user_profile_data(&$data) {
        $data = $data['message'];
        $tempdata = $data;
        unset($data['customfields']);
        // If setting to sync custom fields is empty, then do not format custom fields.
        if (empty($this->config->accountsync_syncfields)) {
            return $data;
        }
        if (isset($tempdata['customfields'])) {
            // Iterate through the custom fields and re-format them.
            foreach ($tempdata['customfields'] as $key => $value) {
                $data['profile_field_'.$value['shortname']] = $value['value'];
            }
        }
        return $data;
    }

    /**
     * Retirieves the user's profile from the remote site.
     * @param string $username The user's username.
     * @return array An array with the user's profile data or error codes and message.  @see validate_ws_response() for more details.
     */
    protected function retrieve_remote_user_profile($username) {
        $remoteurl = self::format_wsurl($this->config->accountsync_wsurl);
        $remoteurl .= "/webservice/rest/server.php?wstoken={$this->config->accountsync_wstoken}&wsfunction=".self::WS_FUNCTION."&moodlewsrestformat=json";

        $params = array('field' => 'username', 'values' => array($username));
        $params = $this->format_postdata_for_curlcall($params);

        $ch = curl_init();
        $this->set_curl_handler($ch, $remoteurl, $params);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($resp, true);

        $data = $this->validate_ws_response($data);

        return $data;
    }

    /**
     * Validates the response structure returned from the ws call.
     * @param array $response An array with the response parameters
     * @return array An array with the following key and value pairs
     * 'error' => 1|0
     * 'errorcode' => string
     * 'message' => string
     */
    protected function validate_ws_response($response) {
        if (!is_array($response)) {
            $errorcode = 'Response is not an array';
            $event = \auth_accountsync\event\accountsync_web_service_error::create(array(
                'other' => array(
                    'message' => $errorcode,
                )
            ));
            $event->trigger();
            return array('error' => 1, 'errorcode' => $errorcode, 'message' => '');
        }

        if (isset($response[0])) {
            return array('error' => 0, 'errorcode' => '', 'message' => $response[0]);
        } else if (empty($response)) {
            $message = 'There was an error retrieving the remote user\'s profile.  Check that the idnumber is the same as the username on the remote site.';
            $errorcode = 'Empty array error';
            $event = \auth_accountsync\event\accountsync_web_service_error::create(array(
                'other' => array('message' => $message)
            ));
            $event->trigger();
            return array('error' => 1, 'errorcode' => $errorcode, 'message' => $message);
        } else {
            $event = \auth_accountsync\event\accountsync_web_service_error::create(array(
                'other' => array(
                    'message' => $response['errorcode'].': '.$response['message'],
                )
            ));
            $event->trigger();
            return array('error' => 1, 'errorcode' => $response['errorcode'], 'message' => $response['message']);
        }
    }

    /**
     * Recursive function formating an array in POST parameter.
     * Original code was taken from @see https://github.com/moodlehq/sample-ws-clients
     * @param array $arraydata The array that we are going to format and add into &$data array.
     * @param string $currentdata A row of the final postdata array at instant T
     *                when finish, it's assign to $data under this format: name[keyname][][]...[]='value'.
     * @param array $data The final data array containing all POST parameters : 1 row = 1 parameter.
     */
    protected function format_array_postdata_for_curlcall($arraydata, $currentdata, &$data) {
        foreach ($arraydata as $k => $v) {
            $newcurrentdata = $currentdata;
            if (is_object($v)) {
                $v = (array) $v;
            }
            // The value is an array, call the function recursively.
            if (is_array($v)) {
                $newcurrentdata = $newcurrentdata.'['.urlencode($k).']';
                $this->format_array_postdata_for_curlcall($v, $newcurrentdata, $data);
            }  else {
                // Add the POST parameter to the $data array.
                $data[] = $newcurrentdata.'['.urlencode($k).']='.urlencode($v);
            }
        }
    }

    /**
     * Transform a PHP array into POST parameter
     * Original code was taken from @see https://github.com/moodlehq/sample-ws-clients
     * (see the recursive function format_array_postdata_for_curlcall)
     * @param array $postdata An array of data to send as part of the POST request.
     * @return array containing all POST parameters  (1 row = 1 POST parameter).
     */
    protected function format_postdata_for_curlcall($postdata) {
        if (is_object($postdata)) {
            $postdata = (array) $postdata;
        }
        $data = array();
        foreach ($postdata as $k => $v) {
            if (is_object($v)) {
                $v = (array) $v;
            }
            if (is_array($v)) {
                $currentdata = urlencode($k);
                $this->format_array_postdata_for_curlcall($v, $currentdata, $data);
            }  else {
                $data[] = urlencode($k).'='.urlencode($v);
            }
        }
        $convertedpostdata = implode('&', $data);
        return $convertedpostdata;
    }

    /**
     * Calls remote site token script.  If a token is returned that means the user account exists and the
     * credentials were valid.  If there was an error then return false.
     * @param string $username The user name
     * @param string $password The user's password
     * @return bool True if a token was returned, otherwise false.
     */
    protected function call_remote_token_script($username, $password) {
        $params = array('username' => $username, 'password' => $password, 'service' => 'cust_mobile_ws');
        if ($this->config->servertype == '0') {
            $remoteurl = self::format_wsurl($this->config->accountsync_wsurl).'/login/token.php';
        } else {
            $remoteurl = self::format_wsurl($this->config->accountsync_wsurl).'/local/moodle_connect/token.php';
        }
        $ch = curl_init();
        $this->set_curl_handler($ch, $remoteurl, $params);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            $event = \auth_accountsync\event\accountsync_login_fail::create(array(
                'other' => array(
                    'message' => "Failed login attempt for username: {$username}. Remote did not return with an array data structure",
                )
            ));
            $event->trigger();
            return false;
        }

        $status = self::validate_token_response($data);
        if (!empty($status['error'])) {
            $event = \auth_accountsync\event\accountsync_login_fail::create(array(
                'other' => array(
                    'message' => "Failed login attempt for username: {$username}. Error title: {$status['error']}. Debug info: {$status['debuginfo']}",
                )
            ));
            $event->trigger();
            return false;
        }

        $event = \auth_accountsync\event\accountsync_remote_token_returned::create(array(
            'other' => array(
                'message' => "Login attempt for username: {$username}. Token returned from remote: {$data['token']}",
            )
        ));
        $event->trigger();
        // If the user logs in check to see if we can get the profile information.
        $data = $this->retrieve_remote_user_profile($username);
        if (1 == $data['error']) {
            $msg = $data['message'];
            $userevent = \core\event\user_login_failed::create(array(
              'other' => array(
                    'username' => $username,
                    'reason'=> $msg
                )
            ));
            $userevent->trigger();
            return false;
        }
        return true;
    }

    /**
     * Set options for the curl handler.
     * @param object $ch curl handler "reference".
     * @param string $remoreurl The remore URL to the token script.
     * @param array $params An array of parameters.
     */
    protected function set_curl_handler(&$ch, $remoteurl, $params) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $remoteurl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
    }
    /**
     * Validate the returned structure from calling the remote token script.
     * @param array $response The response array returned from calling the token script.
     * @return array The first element will be an error.  The value will be a zero if nothing went wrong
     * Or it will be an error message if something went wrong.  The second element will 'debuginfo'.  If
     * debugging info exists it will be populated with the debugging info returned from the reomote site.
     */
    public static function validate_token_response($response) {
        $status = array('error' => 0, 'debuginfo' => 0);
        if (!is_array($response)) {
            $status['debuginfo'] = 'Incorrect data structure';
            $status['error'] = 'Incorrect data structure';
        }

        if (isset($response['error'])) {
            $status['debuginfo'] = $response['debuginfo'];
            $status['error'] = $response['error'];
        }
        return $status;
    }

    /**
     * Format the configured domain to include http:// if no protocol was specified.  Also removes a trailing slash if found.
     * @return string The formatted URL including the protocol and trailing forwars slash removed.
     */
    public static function format_wsurl($url) {
        if (!preg_match("/^http/", $url)) {
            $url = 'http://'.$url;
        }

        $url = rtrim($url, '/');
        return $url;
    }

    /**
     * A post authentication hook is required to cover the use case where custom profile field
     * shortnames contain upper case characters.  As the update_user_record_by_id() function
     * converts profile field shortnames to lower case before finding a match on the host site.
     *
     * This function is called from authenticate_user_login() for all enabled auth plugins.
     *
     * @param object $user user object, later used for $USER.
     * @param string $username (with system magic quotes).
     * @param string $password plain text password (with system magic quotes).
     * @return void.
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        // Only concerned with users authenticated by this plug-in.
        if ($this->authtype != $user->auth) {
            return;
        }

        $data = $this->retrieve_remote_user_profile($username);
        if (1 == $data['error']) {
            $event = \auth_accountsync\event\accountsync_profile_field_sync_error::create(array(
                'other' => array(
                    'message' => 'Reponse object of web service call contain an error.  Unable to sync profile fields from remote. (2)',
                )
            ));
            $event->trigger();
            return;
        }

        $usrprofdata = $this->format_user_profile_data($data);
        $usrprofdata['id'] = $user->id;

        // Save user profile data.
        profile_save_data((object) $usrprofdata);
        // Trigger event.
        \core\event\user_updated::create_from_userid($usrprofdata['id'])->trigger();
    }

    /**
     * This function validates whether the authentication plug-in was properly configured.
     */
    public function validate_configuration() {
        return $this->is_wstoken_set() && $this->is_wsurl_set();
    }

    /**
     * This function validates whether the web service name configuration is set.
     * @return bool True if token is set.  Otherwise false.
     */
    public function is_wsservice_set() {
        if (empty($this->config->accountsync_wsservice)) {
            return false;
        }

        return true;
    }

    /**
     * This function validates whether the token configuration is set.
     * @return bool True if token is set.  Otherwise false.
     */
    public function is_wstoken_set() {
        if (empty($this->config->accountsync_wstoken)) {
            return false;
        }

        return true;
    }

    /**
     * This function validates whether the web service URL configuration is set.
     * @return bool True if wsurl is set.  Otherwsise false.
     */
    public function is_wsurl_set() {
        if (empty($this->config->accountsync_wsurl)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return null;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }
}
