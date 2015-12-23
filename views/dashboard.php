<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container" style="margin-top: 80px;">


  <table class="ui striped table single line">
    <thead>
      <th>Status</th>
      <th>Date</th>
      <th>Source &amp; Target</th>
    </thead>
    <tbody>
    <?php foreach($webmentions as $mention): ?>
      <tr<?= $mention['status'] == 'pending' ? ' class="warning"' : '' ?>>
        <td><i class="<?= $mention['icon'] ?> icon"></i></td>
        <td><a href="/webmention/<?= $mention['webmention']->token ?>/details"><?= date('M j, g:ia', strtotime($mention['webmention']->created_at)) ?></a></td>
        <td>
          source=<a href="<?= $this->e($mention['webmention']->source) ?>"><?= $this->e($mention['webmention']->source) ?></a><br>
          target=<a href="<?= $this->e($mention['webmention']->source) ?>"><?= $this->e($mention['webmention']->target) ?></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
