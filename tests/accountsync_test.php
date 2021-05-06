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

global $CFG;
require_once($CFG->dirroot.'/auth/accountsync/auth.php');

/**
 * Account sync authentication tests class.
 *
 * @package    auth_accountsync
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_accountsync_testcase extends advanced_testcase {

    /** @var auth_plugin_accountsync Keeps the authentication plugin. */
    protected $authplugin;

    /** @var stdClass Keeps authentication plugin config */
    protected $config;

    /**
     * Setup test data.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        set_config('accountsync_wstoken', '1x2x3', auth_plugin_accountsync::COMPONENT_NAME);
        set_config('accountsync_wsurl', 'http://testsite.com', auth_plugin_accountsync::COMPONENT_NAME);
        set_config('accountsync_wsservice', 'service_name', auth_plugin_accountsync::COMPONENT_NAME);
        set_config('accountsync_syncfields', 1, auth_plugin_accountsync::COMPONENT_NAME);
        $this->authplugin = new auth_plugin_accountsync();
    }

    /**
     * Test for an invalid token
     */
    public function test_is_token_set_fail() {
        set_config('accountsync_wstoken', '', auth_plugin_accountsync::COMPONENT_NAME);
        $this->authplugin = new auth_plugin_accountsync();
        $this->assertFalse($this->authplugin->is_wstoken_set());
    }

    /**
     * Test for an valid token
     */
    public function test_is_token_set() {
        $this->assertTrue($this->authplugin->is_wstoken_set());
    }

    /**
     * Test for an invalid wsurl
     */
    public function test_is_wsurl_set_fail() {
        set_config('accountsync_wsurl', '', auth_plugin_accountsync::COMPONENT_NAME);
        $this->authplugin = new auth_plugin_accountsync();
        $this->assertFalse($this->authplugin->is_wsurl_set());
    }

    /**
     * Test for a valid wsurl
     */
    public function test_is_wsurl_set() {
        $this->assertTrue($this->authplugin->is_wsurl_set());
    }

    /**
     * Test for an invalid wsurl
     */
    public function test_is_wsservice_set_fail() {
        set_config('accountsync_wsservice', '', auth_plugin_accountsync::COMPONENT_NAME);
        $this->authplugin = new auth_plugin_accountsync();
        $this->assertFalse($this->authplugin->is_wsservice_set());
    }

    /**
     * Test for a valid wsurl
     */
    public function test_is_wsservice_set() {
        $this->assertTrue($this->authplugin->is_wsservice_set());
    }

    /**
     * Test formatting the wsrul setting
     * @dataProvider wsurls_samples
     */
    public function test_format_wsurl($data) {
        $url = auth_plugin_accountsync::format_wsurl($data);
        $expected = 'http://test.com';
        $this->assertEquals($expected, $url);
    }

    public function wsurls_samples() {
        return array(
            'test_format_wsurl_1' => array('test.com'),
            'test_format_wsurl_2' => array('test.com/'),
            'test_format_wsurl_3' => array('http://test.com/'),
            'test_format_wsurl_4' => array('http://test.com'),
        );
    }

    /**
     * Test validate_token_response.
     */
    public function validate_token_response() {
        $expected = array('error' => 'error1', 'debuginfo' => 0);
        $data = array('error' => 'error1', 'debuginfo' => 0);
        $response = $this->authplugin->validate_token_response($data);
        $this->assertEquals($expected, $response);

        $expected = array('error' => 'error2', 'debuginfo' => 'error2');
        $data = array('error' => 'error2', 'debuginfo' => 'error2');
        $response = $this->authplugin->validate_token_response($data);
        $this->assertEquals($expected, $response);

        $expected = array('error' => 0, 'debuginfo' => 0);
        $data = array('token' => '1234');
        $response = $this->authplugin->validate_token_response($data);
        $this->assertEquals($expected, $response);
    }

    /**
     * Test format_user_profile_data
     */
    public function test_format_user_profile_data() {
        $customfields = array(
            'checkbox' => 1,
            'datetime' => 1451606400,
            'menu' => 'menu choice 2',
            'textarea' => '<div class="no-overflow"><p>This is some default value for text area field.</p></div>',
            'text' => 'Test Text value',
        );

        $data = array(
            'message' => array(
                'firstname' => 'testuser firstname',
                'lastname' => 'testuser lastname',
                'customfields' => array(
                    '0' => array(
                        'type' => 'checkbox',
                        'value' => $customfields['checkbox'],
                        'name' => 'Test Checkbox',
                        'shortname' => 'testcheckbox'
                    ),
                    '1' => array(
                        'type' => 'datetime',
                        'value' => $customfields['datetime'],
                        'name' => 'Test Datetime',
                        'shortname' => 'testdatetime'
                    ),
                    '2' => array(
                        'type' => 'menu',
                        'value' => $customfields['menu'],
                        'name' => 'Test Menu',
                        'shortname' => 'testmenu'
                    ),
                    '3' => array(
                        'type' => 'textarea',
                        'value' => $customfields['textarea'],
                        'name' => 'Test Textarea',
                        'shortname' => 'testtextarea'
                    ),
                    '4' => array(
                        'type' => 'text',
                        'value' => $customfields['text'],
                        'name' => 'Test Text',
                        'shortname' => 'testtext'
                    )
                )
            )
        );

        $expected = array(
            'firstname' => 'testuser firstname',
            'lastname' => 'testuser lastname',
            'profile_field_testcheckbox' => $customfields['checkbox'],
            'profile_field_testdatetime' => $customfields['datetime'],
            'profile_field_testmenu' => $customfields['menu'],
            'profile_field_testtextarea' => $customfields['textarea'],
            'profile_field_testtext' => $customfields['text'],
        );

        $this->authplugin = new auth_plugin_accountsync();
        $this->assertEquals($expected, $this->authplugin->format_user_profile_data($data));
    }
}
