<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class APITest extends PHPUnit_Framework_TestCase {

  private $client;

  public function setUp() {
    $this->client = new API();
    $this->client->http = new Telegraph\HTTPTest(dirname(__FILE__).'/data/');
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

  private function superfeedr_tracker($content, $args) {
    $request = new Request();
    $request->initialize([], [], [], [], [], [], $content);
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

  public function testTargetDomainCantMatchSourceDomain() {
    $this->_createExampleAccount();

    $response = $this->webmention(['token'=>'a','source'=>'http://example.com/test','target_domain'=>'example.com']);
    $body = $response->getContent();
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('not_supported', $data->error);
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

    $payload = '{"status":{"code":200,"http":"Track feed","nextFetch":1238466305,"period":900,"lastFetch":1238466305,"lastParse":1238466305,"lastMaintenanceAt":1238466305,"feed":"http:\/\/track.superfeedr.com\/?query=indieweb"},"title":"","updated":1454695477,"id":"","items":[{"id":"http:\/\/werd.io\/2016\/im-so-used-to-posting-on-my-own-site-first","published":1454690643,"updated":1454690643,"title":"I\'m so used to posting on my own site first and syndicating to Twitter and Facebook that I\'d find it so weird to post natively.","summary":"<div class=\"\">\n    <p class=\"p-name\">I&#039;m so used to posting on my own site first and syndicating to Twitter and Facebook that I&#039;d find it so weird to post natively. <a href=\"http:\/\/werd.io\/tag\/indieweb\" class=\"p-category\" rel=\"tag\">#indieweb<\/a><\/p>\n<\/div>","permalinkUrl":"http://source.example.com/basictest","standardLinks":{"alternate":[{"title":"I\'m so used to posting on my own site first and syndicating to Twitter and Facebook that I\'d find it so weird to post natively.","rel":"alternate","href":"http:\/\/werd.io\/2016\/im-so-used-to-posting-on-my-own-site-first","type":"text\/html"}]},"actor":{"displayName":"Ben Werdm\u00fcller","id":"ben-werdm-ller"},"categories":["#indieweb"],"source":{"id":"ben-werdm-ller-2016-2-5-18","title":"Ben Werdm\u00fcller","updated":1454695469,"permalinkUrl":"http:\/\/werd.io\/content\/all","standardLinks":{"alternate":[{"title":"Ben Werdm\u00fcller","rel":"alternate","href":"http:\/\/werd.io\/content\/all","type":"text\/html"}],"hub":[{"title":"","rel":"hub","href":"http:\/\/benwerd.superfeedr.com\/","type":"text\/html"}],"self":[{"title":"Ben Werdm\u00fcller","rel":"self","href":"http:\/\/werd.io\/content\/all?_t=rss","type":"application\/rss+xml"}]},"status":{"code":200,"http":"","nextFetch":1454776929,"lastFetch":1454695477,"lastParse":1454695477,"lastMaintenanceAt":1454627737,"period":86400,"velocity":10.5,"popularity":0.97363835294308,"bozoRank":0.1,"entriesCountSinceLastMaintenance":7,"feed":"http:\/\/werd.io\/content\/all?_t=rss"}}}]}';
    $response = $this->superfeedr_tracker($payload, ['token'=>'a']);

    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getContent());
    $this->assertEquals('queued', $data->status);
  }

}
