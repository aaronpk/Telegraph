<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container" style="margin-top: 80px; margin-bottom: 40px;">

  <?php if($site): ?>
  <h2>Edit Site</h2>

  <?php else: ?>
  <h2>Create a New Site</h2>

  <p>Create a new site to help organize your webmentions. Each site has its own API key for sending webmentions, or for receiving mentions from Superfeedr.</p>
  <?php endif; ?>

  <form action="/site/save" method="post" class="ui form">
    <div class="field">
      <label>Name</label>
      <input type="text" name="name" value="<?= $site ? $site->name : '' ?>">
    </div>
    <div class="field">
      <label>URL</label>
      <input type="url" name="url" placeholder="http://example.com/" value="<?= $site ? $site->url : '' ?>">
    </div>
    <button class="ui button"><?= $site ? 'Save' : 'Create' ?> Site</button>
    <input type="hidden" name="account" value="<?= $site ? $site->id : '' ?>">
  </form>

  <p style="margin-top: 1em;">Enter your website's home page URL above, and you will be able to receive webmentions from a Superfeedr tracking feed to that domain.</p>

  <?php if($site): ?>

    <form class="ui form">
      <div class="field">
        <label>API Key</label>
        <input type="text" readonly="" value="<?= $role->token ?>">
        <p>Use this key when sending webmentions using the <a href="/api">API</a>.</p>
      </div>
      <div class="field">
        <label>Superfeedr Webhook URL</label>
        <input type="text" readonly="" value="<?= Config::$base ?>superfeedr/<?= $role->token ?>">
        <p>Create a <a href="/superfeedr">Superfeedr tracker</a> subscription and set this URL as the web hook URL.</p>
        <p>If you are using Telegraph to send webmentions as well as receive webmentions from Superfeedr, it is recommended you create a separate site for Superfeedr so you can more easily separate the two uses of the service.</p>
      </div>
      <div class="field">
        <label>Superfeedr Topic URL</label>
        <input type="text" readonly="" value="http://track.superfeedr.com/?query=link%3A<?= parse_url($site->url, PHP_URL_HOST) ?>">
        <p>Your Superfeedr tracker subscription should have this topic URL.</p>
      </div>
    </form>

  <?php endif; ?>

</div>
