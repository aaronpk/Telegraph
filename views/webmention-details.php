<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container" style="margin-top: 80px; margin-bottom: 40px;">

  <h2>Webmention Request</h2>

  <table class="ui table details-table"><tbody>
    <tr>
      <td class="left"><b>Status</b></td>
      <td class="right">
        <i class="circular inverted <?= $icon ?> icon"></i>
        <?= ucfirst($status) ?>
      </td>
    </tr>
    <tr>
      <td class="left"><b>Date</b></td>
      <td class="right"><?= date('M j, g:ia', strtotime($webmention->created_at)) ?></td>
    </tr>
    <tr>
      <td class="left"><b>Source</b></td>
      <td class="right"><a href="<?= $this->e($webmention->source) ?>"><?= $this->e($webmention->source) ?></a></td>
    </tr>
    <tr>
      <td class="left"><b>Target</b></td>
      <td class="right"><a href="<?= $this->e($webmention->target) ?>"><?= $this->e($webmention->target) ?></a></td>
    </tr>
    <? if($webmention->vouch): ?>
      <tr>
        <td class="left"><b>Vouch</b></td>
        <td class="right"><a href="<?= $this->e($webmention->vouch) ?>"><?= $this->e($webmention->vouch) ?></a></td>
      </tr>
    <? endif; ?>
    <? if($webmention->code): ?>
      <tr>
        <td class="left"><b>Code</b></td>
        <td class="right"><code><?= $this->e($webmention->code) ?></code></td>
      </tr>
    <? endif; ?>
    <? if($webmention->realm): ?>
      <tr>
        <td class="left"><b>Realm</b></td>
        <td class="right"><code><?= $this->e($webmention->realm) ?></code></td>
      </tr>
    <? endif; ?>
    <? if($webmention->callback): ?>
      <tr>
        <td class="left"><b>Callback URL</b></td>
        <td class="right"><?= $this->e($webmention->callback) ?></td>
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
          <td><a href="<?= $this->e($webmention->webmention_endpoint) ?>"><?= $this->e($webmention->webmention_endpoint) ?></a></td>
        </tr>
      <? endif; ?>
      <? if($webmention->pingback_endpoint): ?>
        <tr>
          <td><b>Pingback Endpoint</b></td>
          <td><a href="<?= $this->e($webmention->pingback_endpoint) ?>"><?= $this->e($webmention->pingback_endpoint) ?></a></td>
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
          <td>
            <? if($webmention->webmention_status_url): ?>
              <a href="<?= $this->e($webmention->webmention_status_url) ?>"><?= $this->e($webmention->webmention_status_url) ?></a>
            <? else: ?>
              The webmention endpoint did not return a status URL
            <? endif; ?>
          </td>
        </tr>
      <? endif; ?>
    </tbody></table>

    <h2>Logs</h2>

    <table class="ui very compact table logs">
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
            <pre style="font-size: 10px;"><?
              $json = @json_decode($status->raw_response);
              if($json) {
                $pretty = new Camspiers\JsonPretty\JsonPretty;
                echo $this->e($pretty->prettify($json, null, "  "));
              } else {
                echo $this->e($status->raw_response);
              }
            ?></pre>
          </td>
        </tr>
      <? endforeach; ?>
      </tbody>
    </table>

  <? endif; ?>

</div>
