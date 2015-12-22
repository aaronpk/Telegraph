<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProcessTest extends PHPUnit_Framework_TestCase {

  private $client;

  public function setUp() {
    $this->client = new API();
    $this->client->http = new Telegraph\HTTPTest();
    ORM::for_table('users')->raw_query('TRUNCATE users')->delete_many();
    ORM::for_table('roles')->raw_query('TRUNCATE roles')->delete_many();
    ORM::for_table('sites')->raw_query('TRUNCATE sites')->delete_many();
    ORM::for_table('webmentions')->raw_query('TRUNCATE webmentions')->delete_many();
    ORM::for_table('webmention_status')->raw_query('TRUNCATE webmention_status')->delete_many();
  }

  private function _createExampleAccount() {
    $user = ORM::for_table('users')->create();
    $user->url = 'http://example.com';
    $user->save();

    $site = ORM::for_table('sites')->create();
    $site->name = 'Example';
    $site->created_by = $user->id();
    $site->save();

    $role = ORM::for_table('roles')->create();
    $role->site_id = $site->id();
    $role->user_id = $user->id();
    $role->role = 'owner';
    $role->token = 'a';
    $role->save();
  }

  private function webmention($params) {
    $request = new Request($params);
    $response = new Response();
    $response = $this->client->webmention($request, $response);
    $webmention = ORM::for_table('webmentions')->where(['source' => $params['source'], 'target' => $params['target']])->find_one();
    $client = new IndieWeb\MentionClientTest();
    $client::$dataDir = dirname(__FILE__) . '/data/';
    if(!is_object($webmention)) {
      throw new Exception("No webmention was queued for this test");
    }
    Telegraph\Webmention::send($webmention->id, $client);
    return $webmention;
  }

  private static function webmentionStatus($id) {
    return ORM::for_table('webmention_status')->where(['webmention_id'=>$id])->order_by_desc('created_at')->find_one();
  }

  public function testNoEndpoint() {
    $this->_createExampleAccount();
    $webmention = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/no-endpoint',
      'target' => 'http://target.example.com/no-endpoint'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals($status->status, 'not_supported');
  }

  public function testPingbackSuccess() {
    $this->_createExampleAccount();
    $webmention = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/pingback-success',
      'target' => 'http://target.example.com/pingback-success'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals($status->status, 'pingback_accepted');
  }

  public function testPingbackFailed() {
    $this->_createExampleAccount();
    $webmention = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/pingback-failed',
      'target' => 'http://target.example.com/pingback-failed'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals($status->status, 'pingback_error');
  }

  public function testWebmentionTakesPriorityOverPingback() {
    $this->_createExampleAccount();
    $webmention = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/webmention-and-pingback',
      'target' => 'http://target.example.com/webmention-success'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals($status->status, 'webmention_accepted');
  }

  public function testWebmentionFailed() {

  }

}
