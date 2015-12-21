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
  }

  public function testInvalidURLs() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'notaurl','target'=>'alsonotaurl']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_parameter', $data->error);

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example','target'=>'alsonotaurl']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_parameter', $data->error);

    $response = $this->webmention(['token'=>'a','source'=>'notaurl','target'=>'http://target.example']);
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_parameter', $data->error);
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

  public function testQueuesWebmention() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://source.example.com/basictest','target'=>'http://target.example.com']);
    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals(false, property_exists($data, 'error'));
    $this->assertEquals('queued', $data->result);
    $this->assertEquals(true, property_exists($data, 'status'));

    preg_match('/\/webmention\/(.+)/', $data->status, $match);
    $this->assertNotNull($match);

    # Verify it queued the mention in the database
    $d = ORM::for_table('webmentions')->where(['source' => 'http://source.example.com/basictest', 'target' => 'http://target.example.com'])->find_one();
    $this->assertNotNull($d);
    $this->assertEquals($match[1], $d->token);
  }

}
