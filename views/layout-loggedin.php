<?php $this->layout('layout', ['title' => $title]); ?>

<div class="ui fixed inverted menu">
  <div class="ui container">
    <a href="/" class="header item">
      <img class="logo" src="/assets/telegraph-icon-white.png">
      Telegraph
    </a>
    <? if($user): ?>
      <a href="/dashboard" class="item">Dashboard</a>
    <? endif; ?>
    <a href="/api" class="item">API</a>
    <div class="ui right simple dropdown item">
      <? if($user): ?>
        <img class="ui mini circular image" src="<?= $user->photo ?: '/assets/default-user.jpg' ?>"> <i class="dropdown icon"></i>
        <div class="menu">
          <div class="header">Websites</div>
          <? foreach($accounts as $account): ?>
            <a class="item" href="/dashboard?account=<?= $account->id ?>"><?= $this->e($account->name) ?></a>
          <? endforeach; ?>
          <!--
          <div class="divider"></div>
          <a class="item" href="/new-site"><i class="plus icon"></i> New Site</a>
          <a class="item" href="/profile"><i class="user icon"></i> Profile</a>
          -->
          <div class="divider"></div>
          <a class="item" href="/logout"><i class="sign out icon"></i> Log Out</a>
        </div>
      <? else: ?>
        <a href="/login<?= isset($return_to) ? '?return_to='.urlencode($return_to) : '' ?>"><i class="sign in icon"></i> Log In</a>
      <? endif; ?>
    </div>
  </div>
</div>

<?= $this->section('content') ?>

<? $this->insert('footer-block') ?>
