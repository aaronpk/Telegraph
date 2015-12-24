<!DOCTYPE html>
<html>
<head>
  <!-- Standard Meta -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

  <!-- Site Properities -->
  <title>Telegraph</title>
  <link rel="stylesheet" type="text/css" href="/semantic-ui/semantic.min.css">

  <style type="text/css">

    .hidden.menu {
      display: none;
    }

    .masthead.segment {
      min-height: 700px;
      padding: 1em 0em;
    }
    .masthead .logo.item img {
      margin-right: 1em;
    }
    .masthead .ui.menu .ui.button {
      margin-left: 0.5em;
    }
    .masthead h1.ui.header {
      margin-top: 3em;
      margin-bottom: 0em;
      font-size: 4em;
      font-weight: normal;
    }
    .masthead h2 {
      font-size: 1.7em;
      font-weight: normal;
    }

    .ui.vertical.stripe {
      padding: 8em 0em;
    }
    .ui.vertical.stripe h3 {
      font-size: 2em;
    }
    .ui.vertical.stripe .button + h3,
    .ui.vertical.stripe p + h3 {
      margin-top: 3em;
    }
    .ui.vertical.stripe .floated.image {
      clear: both;
    }
    .ui.vertical.stripe p {
      font-size: 1.33em;
    }
    .ui.vertical.stripe .horizontal.divider {
      margin: 3em 0em;
    }

    .quote.stripe.segment {
      padding: 0em;
    }
    .quote.stripe.segment .grid .column {
      padding-top: 5em;
      padding-bottom: 5em;
    }

    .footer.segment {
      padding: 5em 0em;
    }

    .secondary.pointing.menu .toc.item {
      display: none;
    }

    @media only screen and (max-width: 700px) {
      .ui.fixed.menu {
        display: none !important;
      }
      .secondary.pointing.menu .item,
      .secondary.pointing.menu .menu {
        display: none;
      }
      .secondary.pointing.menu .toc.item {
        display: block;
      }
      .masthead.segment {
        min-height: 350px;
      }
      .masthead h1.ui.header {
        font-size: 2em;
        margin-top: 1.5em;
      }
      .masthead h2 {
        margin-top: 0.5em;
        font-size: 1.5em;
      }
    }

    .ui.inverted.segment.masthead {
      background-image: url(/assets/telegraph-header.jpg);
      background-position: center;
    }

    .ui.secondary.inverted.pointing.menu, .ui.secondary.pointing.menu {
      border: 0;
    }

    pre.code {
      padding: 8px 12px;
      border-radius: 4px;
      border: 1px #ddd solid;
      background-color: rgba(248,246,255,1);
      font-size: 0.9em;
    }

  </style>

  <script src="/assets/jquery-1.11.3.min.js"></script>
  <script src="/semantic-ui/semantic.min.js"></script>
  <script>
  $(document)
    .ready(function() {

      // fix menu when passed
      $('.masthead')
        .visibility({
          once: false,
          onBottomPassed: function() {
            $('.fixed.menu').transition('fade in');
          },
          onBottomPassedReverse: function() {
            $('.fixed.menu').transition('fade out');
          }
        })
      ;

      // create sidebar and attach to menu open
      $('.ui.sidebar')
        .sidebar('attach events', '.toc.item')
      ;

    })
  ;
  </script>
</head>
<body>

<?php
$menu = [
  '/' => 'Home',
  '/api' => 'API',
];
?>

<!-- Following Menu -->
<div class="ui large top fixed hidden menu">
  <div class="ui container">
    <?php foreach($menu as $href=>$name): ?>
      <a class="item" href="<?= $href ?>"><?= $name ?></a>
    <?php endforeach; ?>
    <div class="right menu">
      <div class="item">
        <a class="ui button" href="/login">Log in</a>
      </div>
    </div>
  </div>
</div>

<!-- Sidebar Menu -->
<div class="ui vertical inverted sidebar menu">
  <?php foreach($menu as $href=>$name): ?>
    <a class="item" href="<?= $href ?>"><?= $name ?></a>
  <?php endforeach; ?>
  <a class="item" href="/login">Login</a>
</div>


<!-- Page Contents -->
<div class="pusher">
  <div class="ui inverted vertical masthead center aligned segment">

    <div class="ui container">
      <div class="ui large secondary inverted pointing menu">
        <a class="toc item">
          <i class="sidebar icon"></i>
        </a>
        <?php foreach($menu as $href=>$name): ?>
          <a class="item" href="<?= $href ?>"><?= $name ?></a>
        <?php endforeach; ?>
        <div class="right item">
          <a class="ui inverted button" href="/login">Log in</a>
        </div>
      </div>
    </div>

    <div class="ui text container">
      <h1 class="ui inverted header">
        Telegraph
      </h1>
      <h2>Easily send Webmentions from your website</h2>
      <a class="ui huge primary button" href="/login">Get Started <i class="right arrow icon"></i></a>
    </div>
  </div>


  <div class="ui vertical stripe segment">
    <div class="ui text container">
      <h3 class="ui header">We send webmentions for you</h3>
      <p>Instead of doing the hard work of sending webmentions yourself, we have a simple API that will handle endpoint discovery, gracefully handle failures and retries, and will let you know whether a webmention was successfully sent. All you have to do is tell us the page you want to send the webmention to and we'll take it from there.</p>
      <a class="ui large button" href="/api">Read More</a>
      <!--
      <h4 class="ui horizontal header divider">
        Case Studies
      </h4>
      <h3 class="ui header">Did We Tell You About Our Bananas?</h3>
      <p>Yes I know you probably disregarded the earlier boasts as non-sequitor filler content, but its really true. It took years of gene splicing and combinatory DNA research, but our bananas can really dance.</p>
      <a class="ui large button">I'm Still Quite Interested</a>
      -->
    </div>
  </div>

  <div class="ui vertical stripe segment">
    <div class="ui middle aligned stackable grid container">
      <div class="row">
        <div class="seven wide column">
          <img src="/assets/dashboard-screenshot.jpg" class="ui large bordered rounded image">
        </div>
        <div class="eight wide column">
          <h3 class="ui header">Webmentions at a glance</h3>
          <p>Sign in to quickly send webmentions from the Dashboard, and see the status of your previously sent webmentions.</p>
          <p>The status of each webmention can be viewed individually, so you can tell whether it worked or how it failed.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="ui vertical stripe segment">
    <div class="ui middle aligned stackable grid container">
      <div class="row">
        <div class="eight wide column">
          <h3 class="ui header">Send Webmentions with a Simple API</h3>
          <p>Let Telegraph send webmentions for you. With a simple API, Telegraph will handle sending webmentions to other websites. Let us handle webmention endpoint discovery and handling failures.</p>
          <h3 class="ui header">Get updates on webmention delivery</h3>
          <p>With a simple web hook, Telegraph will notify your site when a webmention was successfully sent or if an error occurred.</p>
          <!--
          <h3 class="ui header">Send webmentions automatically</h3>
          <p>You can even let Telegraph subscribe to your feed, and it will send webmentions whenever you publish a new post.</p>
          -->
        </div>
        <div class="seven wide right floated column">
          <pre class="code"><code>
POST /webmention HTTP/1.1
Content-type: application/json

{
  "source": "http://source.example.com/post/100",
  "target": "http://target.example.net/",
  "callback": "http://yoursite.example.org/webmention-status"
  "token": "xxxx"
}
          </code></pre>
        </div>
      </div>
      <div class="row">
        <div class="center aligned column">
          <a class="ui huge button" href="/api">API Docs</a>
        </div>
      </div>
    </div>
  </div>

  <!--
  <div class="ui vertical stripe quote segment">
    <div class="ui equal width stackable internally celled grid">
      <div class="center aligned row">
        <div class="column">
          <h3>"Nice thing"</h3>
          <p>A quote by a nice person</p>
        </div>
        <div class="column">
          <h3>"This makes everything so much easier."</h3>
          <p>
            <img src="/assets/ben.jpg" class="ui avatar image"> <b>Ben</b> Chief Ben Officer
          </p>
        </div>
      </div>
    </div>
  </div>
  -->


  <? $this->insert('footer-block') ?>

</div>

</body>

</html>
