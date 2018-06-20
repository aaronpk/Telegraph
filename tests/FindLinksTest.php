<?php
use Telegraph\FindLinks;

class FindLinksTest extends PHPUnit_Framework_TestCase {

  public function testFindLinksInText() {
    $links = FindLinks::inText('Hello world http://example.com/');
    $this->assertContains('http://example.com/', $links);
  }

  public function testFindLinksInHTML() {
    $links = FindLinks::inHTML('<a href="http://example.com/">Hello</a>');
    $this->assertContains('http://example.com/', $links);
  }

  public function testFindLinksInJSONArray() {
    $links = FindLinks::all([
      'link' => 'http://example.com/',
      'nested' => [
        'foo' => 'http://example.net/',
        'html' => 'This is some html with a <a href="http://example.html/">link</a>',
        'photo' => [
          'http://example.com/img.jpg'
        ],
        'bar' => [
          'baz' => [
            'http://example.org/'
          ]
        ],
        [[['http://example.io/']]]
      ]
    ]);
    $this->assertContains('http://example.com/', $links);
    $this->assertContains('http://example.com/img.jpg', $links);
    $this->assertContains('http://example.net/', $links);
    $this->assertContains('http://example.org/', $links);
    $this->assertContains('http://example.io/', $links);
    $this->assertContains('http://example.html/', $links);
  }

  public function testFindLinksInXRayResult() {
    $data = json_decode('
{"data":{"type":"entry","published":"2018-06-19T14:32:44-07:00","url":"https://aaronparecki.com/2018/06/19/12/indiewebsummit","category":["indieweb"],"syndication":["https://twitter.com/aaronpk/status/1009187255204732928"],"content":{"text":"I\'m excited to announce that @namedotcom is our newest sponsor of @IndieWebSummit and they\'ll be joining us next week! It\'s not too late to register! \ud83d\udd1c https://2018.indieweb.org","html":"I\'m excited to announce that <a href=\"https://twitter.com/namedotcom\">@namedotcom</a> is our newest sponsor of <a href=\"https://twitter.com/IndieWebSummit\">@IndieWebSummit</a> and they\'ll be joining us next week! It\'s not too late to register! <a href=\"https://aaronparecki.com/emoji/%F0%9F%94%9C\">\ud83d\udd1c</a> <a href=\"https://2018.indieweb.org\">https://2018.indieweb.org</a>"},"author":{"type":"card","name":"Aaron Parecki","url":"https://aaronparecki.com/","photo":"https://aaronparecki.com/images/profile.jpg"}},"url":"https://aaronparecki.com/2018/06/19/12/indiewebsummit","code":200}
', true);
    unset($data['data']['author']);
    $links = FindLinks::all($data['data']);
    $this->assertContains('https://aaronparecki.com/2018/06/19/12/indiewebsummit', $links);
    $this->assertContains('https://twitter.com/aaronpk/status/1009187255204732928', $links);
    $this->assertContains('https://2018.indieweb.org', $links);
    $this->assertContains('https://twitter.com/namedotcom', $links);
    $this->assertContains('https://twitter.com/IndieWebSummit', $links);
    $this->assertContains('https://aaronparecki.com/emoji/%F0%9F%94%9C', $links);
  }

}

