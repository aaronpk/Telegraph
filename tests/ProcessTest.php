<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProcessTest extends PHPUnit_Framework_TestCase {

  private $http;
  private $api;

  public function setUp() {
    $this->http = new Telegraph\HTTPTest();
    $this->api = new API();
    $this->api->http = $this->http;
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
    $response = $this->api->webmention($request, $response);
    $webmention = ORM::for_table('webmentions')->where(['source' => $params['source'], 'target' => $params['target']])->find_one();
    $client = new IndieWeb\MentionClientTest();
    $client::$dataDir = dirname(__FILE__) . '/data/';
    if(!is_object($webmention)) {
      throw new Exception("No webmention was queued for this test");
    }
    $callback = Telegraph\Webmention::send($webmention->id, $client, $this->http);
    return [$webmention, $callback];
  }

  private static function webmentionStatus($id) {
    return ORM::for_table('webmention_status')->where(['webmention_id'=>$id])->order_by_desc('created_at')->find_one();
  }

  public function testNoEndpoint() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/no-endpoint',
      'target' => 'http://target.example.com/no-endpoint'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals($status->status, 'not_supported');
  }

  public function testPingbackSuccess() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/pingback-success',
      'target' => 'http://target.example.com/pingback-success'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals('accepted', $status->status);
    $webmention = ORM::for_table('webmentions')->where('id',$webmention->id)->find_one();
    $this->assertEquals('http://pingback.example.com/success', $webmention->pingback_endpoint);
  }

  public function testPingbackFailed() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/pingback-failed',
      'target' => 'http://target.example.com/pingback-failed'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals('error', $status->status);
  }

  public function testWebmentionTakesPriorityOverPingback() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/webmention-and-pingback',
      'target' => 'http://target.example.com/webmention-success'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $webmention = ORM::for_table('webmentions')->where('id',$webmention->id)->find_one();
    $this->assertNotNull($webmention->webmention_endpoint);
    $this->assertNull($webmention->pingback_endpoint);
    $this->assertEquals('accepted', $status->status);
  }

  public function testWebmentionSucceeds() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/webmention-success',
      'target' => 'http://target.example.com/webmention-success'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals('accepted', $status->status);
    $webmention = ORM::for_table('webmentions')->where('id',$webmention->id)->find_one();
    $this->assertEquals('http://webmention.example.com/success', $webmention->webmention_endpoint);
  }

  public function testSavesWebmentionStatusURL() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/webmention-status-url',
      'target' => 'http://target.example.com/webmention-status-url'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals('accepted', $status->status);
    $webmention = ORM::for_table('webmentions')->where('id',$webmention->id)->find_one();
    $this->assertEquals('http://webmention.example.com/success-with-status', $webmention->webmention_endpoint);
    // Make sure the status URL returned is an absolute URL
    $this->assertEquals('http://webmention.example.com/webmention/1000', $webmention->webmention_status_url);
  }

  public function testWebmentionFailed() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/webmention-failed',
      'target' => 'http://target.example.com/webmention-failed'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals('error', $status->status);
    $webmention = ORM::for_table('webmentions')->where('id',$webmention->id)->find_one();
    $this->assertEquals('http://webmention.example.com/error', $webmention->webmention_endpoint);
  }

  public function testWebmentionStatusCallback() {
    $this->_createExampleAccount();
    list($webmention, $callback) = $this->webmention([
      'token' => 'a',
      'source' => 'http://source.example.com/webmention-success',
      'target' => 'http://target.example.com/webmention-success',
      'callback' => 'http://source.example.com/callback'
    ]);
    $status = $this->webmentionStatus($webmention->id);
    $this->assertEquals('accepted', $status->status);
    $webmention = ORM::for_table('webmentions')->where('id',$webmention->id)->find_one();
    $this->assertEquals('http://webmention.example.com/success', $webmention->webmention_endpoint);
    $this->assertEquals('Callback was successful', trim($callback['body']));
  }

}
