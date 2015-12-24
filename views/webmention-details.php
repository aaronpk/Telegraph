<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container" style="margin-top: 80px;">

  <h2>Webmention Request</h2>

  <table class="ui table single line"><tbody>
    <tr>
      <td><b>Status</b></td>
      <td>
        <i class="circular inverted <?= $icon ?> icon"></i>
        <?= ucfirst($status) ?>
      </td>
    </tr>
    <tr>
      <td><b>Date</b></td>
      <td><?= date('M j, g:ia', strtotime($webmention->created_at)) ?></td>
    </tr>
    <tr>
      <td><b>Source</b></td>
      <td><a href="<?= $this->e($webmention->source) ?>"><?= $this->e($webmention->source) ?></a></td>
    </tr>
    <tr>
      <td><b>Target</b></td>
      <td><a href="<?= $this->e($webmention->target) ?>"><?= $this->e($webmention->target) ?></a></td>
    </tr>
    <? if($webmention->vouch): ?>
      <tr>
        <td><b>Vouch</b></td>
        <td><a href="<?= $this->e($webmention->vouch) ?>"><?= $this->e($webmention->vouch) ?></a></td>
      </tr>
    <? endif; ?>
    <? if($webmention->callback): ?>
      <tr>
        <td><b>Callback URL</b></td>
        <td><?= $this->e($webmention->callback) ?></td>
      </tr>
    <? endif; ?>
  </tbody></table>

  <h2>Details</h2>

  <? if(count($statuses) == 0): ?>
    <p>The request is queued for processing. Check for updates again later.</p>
  <? else: ?>

    <table class="ui table single line"><tbody>
      <? if($webmention->webmention_endpoint): ?>
        <tr>
          <td><b>Webmention Endpoint</b></td>
          <td><?= $this->e($webmention->webmention_endpoint) ?></td>
        </tr>
      <? endif; ?>
      <? if($webmention->pingback_endpoint): ?>
        <tr>
          <td><b>Pingback Endpoint</b></td>
          <td><?= $this->e($webmention->pingback_endpoint) ?></td>
        </tr>
      <? endif; ?>
      <? if($webmention->webmention_endpoint == false && $webmention->pingback_endpoint == false): ?>
        <tr>
          <td><b>Webmention Endpoint</b></td>
          <td>No webmention endpoint was discovered for this target</td>
        </tr>
      <? endif; ?>
      <? if($webmention->webmention_endpoint): ?>
        <tr>
          <td><b>Status URL</b></td>
          <td><?= $webmention->webmention_status_url ? $this->e($webmention->webmention_status_url) : 'The webmention endpoint did not return a status URL' ?></td>
        </tr>
      <? endif; ?>
    </tbody></table>

    <h2>Logs</h2>

    <table class="ui very compact table single line">
      <thead>
        <tr>
          <th>Date</th>
          <th>Status</th>
          <th>HTTP Code</th>
        </tr>
      </thead>
      <tbody>
      <? foreach($statuses as $status): ?>
        <tr>
          <td><?= date('M j, g:ia', strtotime($status->created_at)) ?></td>
          <td><?= $status->status ?></td>
          <td><?= $status->http_code ?></td>
        </tr>
        <tr>
          <td colspan="3">
            <pre style="font-size: 10px;"><?= $this->e($status->raw_response) ?></pre>
          </td>
        </tr>
      <? endforeach; ?>
      </tbody>
    </table>

  <? endif; ?>

</div>
