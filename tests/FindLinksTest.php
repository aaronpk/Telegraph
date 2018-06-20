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

}

