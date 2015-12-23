<?php $this->layout('layout', ['title' => $title]); ?>

<div class="ui fixed inverted menu">
  <div class="ui container">
    <a href="/" class="header item">
      <img class="logo" src="/assets/telegraph-logo-256.png">
      Telegraph
    </a>
    <a href="/dashboard" class="item">Dashboard</a>
    <a href="/api" class="item">API</a>
    <div class="ui right simple dropdown item">
      Sites <i class="dropdown icon"></i>
      <div class="menu">
        <div class="header"><?= display_url($user->url) ?></div>
        <?php foreach($accounts as $account): ?>
          <a class="item" href="/dashboard?account=<?= $account->id ?>"><?= $this->e($account->name) ?></a>
        <?php endforeach; ?>
        <div class="divider"></div>
        <a class="item" href="/new-site">New Site</a>
        <a class="item" href="/logout">Log Out</a>
      </div>
    </div>
  </div>
</div>
<?= $this->section('content') ?>
