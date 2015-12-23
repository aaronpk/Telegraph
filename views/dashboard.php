<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container">

  <table class="ui striped table fixed single line">
    <thead>
      <th>Date</th>
      <th>Source &amp; Target</th>
    </thead>
    <?php foreach($webmentions as $mention): ?>
      <tr>
        <td><?= $mention['webmention']->created_at ?></td>
        <td><?= $this->e($mention['webmention']->source) ?><br><?= $this->e($mention['webmention']->target) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
