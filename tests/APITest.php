<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class APITest extends PHPUnit_Framework_TestCase {

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

  private function webmention($params) {
    $request = new Request($params);
    $response = new Response();
    return $this->client->webmention($request, $response);
  }

  private function superfeedr_tracker($params, $args) {
    $request = new Request($params);
    $response = new Response();
    return $this->client->superfeedr_tracker($request, $response, $args);
  }

  private function status($code) {
    $request = new Request();
    $response = new Response();
    return $this->client->webmention_status($request, $response, ['code'=>$code]);
  }

  private function _createExampleAccount() {
    $user = ORM::for_table('users')->create();
    $user->url = 'http://example.com';
    $user->save();

    $site = ORM::for_table('sites')->create();
    $site->name = 'Example';
    $site->url = 'http://example.com';
    $site->created_by = $user->id();
    $site->save();

    $role = ORM::for_table('roles')->create();
    $role->site_id = $site->id();
    $role->user_id = $user->id();
    $role->role = 'owner';
    $role->token = 'a';
    $role->save();
  }

  private function _assertQueued($source, $target, $status_url) {
    preg_match('/\/webmention\/(.+)/', $status_url, $match);
    $this->assertNotNull($match);

    # Verify it queued the mention in the database
    $d = ORM::for_table('webmentions')->where(['source' => $source, 'target' => $target])->find_one();
    $this->assertNotFalse($d);
    $this->assertEquals($match[1], $d->token);
    # Check the status endpoint to make sure it says it's still queued
    $response = $this->status($d->token);
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('queued', $data->status);
  }

  private function _assertNotQueued($source, $target) {
    # Verify it did not queue a mention in the database
    $d = ORM::for_table('webmentions')->where(['source' => $source, 'target' => $target])->find_one();
    $this->assertFalse($d);
  }

  public function testAuthentication() {
    $response = $this->webmention([]);
    $this->assertEquals(401, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('authentication_required', $data->error);

    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'x','source'=>'http://source.example','target'=>'http://target.example']);
    $this->assertEquals(401, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_token', $data->error);

    $response = $this->webmention(['token'=>'a']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('missing_parameters', $data->error);
  }

  public function testMissingParameters() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('missing_parameters', $data->error);

    $response = $this->webmention(['token'=>'a','source'=>'foo']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('missing_parameters', $data->error);

    $response = $this->webmention(['token'=>'a','target'=>'foo']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('missing_parameters', $data->error);

    $response = $this->webmention(['token'=>'a','target_domain'=>'foo']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('missing_parameters', $data->error);
  }

  public function testTargetAndTargetDomain() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'foo','target'=>'foo','target_domain'=>'foo']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_parameter', $data->error);
  }

  public function testInvalidURLs() {
    $this->_createExampleAccount();

    foreach ([['token'=>'a','source'=>'notaurl','target'=>'alsonotaurl'],
              ['token'=>'a','source'=>'http://source.example','target'=>'alsonotaurl'],
              ['token'=>'a','source'=>'notaurl','target'=>'http://target.example'],
              ['token'=>'a','source'=>'http://source.example','target'=>'mailto:user@example.com'],
              ['token'=>'a','source'=>'http://source.example','target'=>'http://target.example','callback'=>'notaurl']
             ] as $params) {
      $response = $this->webmention($params);
      $this->assertEquals(400, $response->getStatusCode());
      $data = json_decode($response->getContent());
      $this->assertEquals('invalid_parameter', $data->error);
    }
  }

  public function testNoLinkToSource() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/nolink','target'=>'http://target.example.com']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('no_link_found', $data->error);

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/nothtml','target'=>'http://target.example.com']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('no_link_found', $data->error);
  }

  public function testHandlesMalformedHTMLWithLink() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/invalidhtml','target'=>'http://target.example.com']);
    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals(false, property_exists($data, 'error'));
  }

  public function testTargetQueuesWebmention() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/basictest','target'=>'http://target.example.com']);
    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals(false, property_exists($data, 'error'));
    $this->assertEquals('queued', $data->status);
    $this->_assertQueued('http://source.example.com/basictest', 'http://target.example.com', $data->location);
  }

  public function testTargetQueuesOnlyTargetWebmention() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/multipletest','target'=>'http://target.example.com']);
    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals(false, property_exists($data, 'error'));
    $this->assertEquals('queued', $data->status);
    $this->_assertQueued('http://source.example.com/multipletest', 'http://target.example.com', $data->location);
    $this->_assertNotQueued('http://source.example.com/multipletest', '/relativelink');
  }

  public function testTargetDomainQueuesOneWebmention() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/basictest','target_domain'=>'target.example.com']);
    $body = $response->getContent();
    $this->assertEquals(201, $response->getStatusCode(), $body);
    $data = json_decode($body);
    $this->assertEquals(false, property_exists($data, 'error'), $body);
    $this->assertEquals('queued', $data->status, $body);
    $this->assertEquals(true, property_exists($data, 'location'), $body);
    $this->assertEquals(1, count($data->location), $body);
    $this->_assertQueued('http://source.example.com/basictest', 'http://target.example.com', $data->location[0]);
  }

  public function testTargetDomainQueuesMultipleWebmentions() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/basictest','target_domain'=>'example.com']);
    $body = $response->getContent();
    $this->assertEquals(201, $response->getStatusCode(), $body);
    $data = json_decode($body);
    $this->assertEquals(false, property_exists($data, 'error'), $body);
    $this->assertEquals('queued', $data->status, $body);
    $this->assertEquals(2, count($data->location), $body);
    $this->_assertQueued('http://source.example.com/basictest', 'http://target.example.com', $data->location[0]);
    $this->_assertQueued('http://source.example.com/basictest', 'http://target2.example.com', $data->location[1]);
  }

  public function testTargetDomainQueuesOnlyWebmentionsFromTargetDomain() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/multipletest','target_domain'=>'example.com']);
    $body = $response->getContent();
    $this->assertEquals(201, $response->getStatusCode(), $body);
    $data = json_decode($body);
    $this->assertEquals(false, property_exists($data, 'error'), $body);
    $this->assertEquals('queued', $data->status, $body);
    $this->assertEquals(2, count($data->location), $body);
    $this->_assertQueued('http://source.example.com/multipletest', 'http://target.example.com', $data->location[0]);
    $this->_assertQueued('http://source.example.com/multipletest', 'http://target2.example.com', $data->location[1]);
    $this->_assertNotQueued('http://source.example.com/multipletest', 'http://target.example.org');
    $this->_assertNotQueued('http://source.example.com/multipletest', '/relativelink');
    $this->_assertNotQueued('http://source.example.com/multipletest', 'http://source.example.com/relativelink');
  }

  public function testTargetDomainSubdomainCheck() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/multipletest','target_domain'=>'ample.com']);
    $body = $response->getContent();
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('no_link_found', $data->error);
  }

  public function testStatusNotFound() {
    $this->_createExampleAccount();

    $response = $this->status('foo');
    $this->assertEquals(404, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('not_found', $data->status);
  }

  public function testSuperfeedrTracker() {
    $this->_createExampleAccount();

    $payload = [
      'items' => [[
        'permalinkUrl' => 'http://source.example.com/basictest'
      ]]
    ];
    $response = $this->superfeedr_tracker($payload, ['token'=>'a']);

    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('queued', $data->status);
  }

}
