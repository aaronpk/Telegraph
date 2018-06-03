<?php $this->layout('layout', ['title' => $title]); ?>

<style type="text/css">
  body {
    background-color: #DADADA;
  }
  body > .grid {
    height: 100%;
  }
  .image {
    margin-top: -100px;
  }
  .column {
    max-width: 450px;
  }
</style>

<div class="ui middle aligned center aligned grid">
  <div class="column">
    <h2 class="ui teal image header">
      <img src="/assets/telegraph-logo-256.png" class="image">
      <div class="content">
        Sign in to Telegraph
      </div>
    </h2>

    <?php if(isset($error)): ?>
      <div class="ui warning message" style="word-wrap: break-word;">
        <div class="header"><?= $error ?></div>
        <?= $error_description ?>
      </div>
    <?php endif; ?>

    <form class="ui large form" action="/login/start" method="POST" >
      <div class="ui stacked segment">
        <div class="field">
          <div class="ui left icon input">
            <i class="globe icon"></i>
            <input type="url" name="url" placeholder="Your Web Address">
          </div>
        </div>
        <input type="hidden" name="return_to" value="<?= isset($return_to) ? $return_to : '' ?>">
        <button class="ui fluid large teal submit button">Login</button>
      </div>

      <div class="ui error message"></div>

    </form>

    <div class="ui message">
      What's this? <a href="https://indielogin.com/setup">About Web Sign-In</a>
    </div>
  </div>
</div>

<script>
  /* add http:// to URL fields on blur or when enter is pressed */
  document.addEventListener('DOMContentLoaded', function() {
    function addDefaultScheme(target) {
      if(target.value.match(/^(?!https?:).+\..+/)) {
        target.value = "http://"+target.value;
      }
    }
    var elements = document.querySelectorAll("input[type=url]");
    Array.prototype.forEach.call(elements, function(el, i){
      el.addEventListener("blur", function(e){
        addDefaultScheme(e.target);
      });
      el.addEventListener("keydown", function(e){
        if(e.keyCode == 13) {
          addDefaultScheme(e.target);
        }
      });
    });
  });
</script>
