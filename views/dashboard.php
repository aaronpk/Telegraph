<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container" style="margin-top: 80px; margin-bottom: 40px;">

  <h2 class="site-name"><?= $site->name ?> <a href="/site/edit?account=<?= $site->id ?>" class="edit-site" style="font-size: 0.8em;"><i class="setting icon"></i></a></h2>

  <div class="ui top attached tabular menu">
    <a class="item active" data-tab="send-from-source">Find Links</a>
    <a class="item" data-tab="send-source-target">Send Webmention</a>
  </div>
  <div class="ui bottom attached tab segment active" data-tab="send-from-source">
    <form action="/dashboard/send" method="get" class="ui form">
      <div class="ui fluid action input">
        <input type="url" name="url" placeholder="http://example.com/">
        <button class="ui button">Find Links</button>
      </div>
      <input type="hidden" name="account" value="<?= $site->id ?>">
    </form>
    <div style="padding: 6px;">Enter a URL above to preview and send webmentions from all the links found on the page.</div>
  </div>
  <div class="ui bottom attached tab segment" data-tab="send-source-target">
    <form class="ui form" id="send-webmention-form">
      <div class="two fields">
        <div class="field"><label>Source URL</label><input type="url" placeholder="Source URL" id="send-source"></div>
        <div class="field"><label>Target URL</label><input type="url" placeholder="Target URL" id="send-target"></div>
      </div>
      <div class="ui error message"></div>
      <button class="ui button right floated" id="send-webmention-btn">Send Webmention</button>
      <div style="clear:both;"></div>
    </form>
  </div>

  <? if(count($webmentions)): ?>
  <table class="ui striped table status-table">
    <thead>
      <th>Status</th>
      <th>Date</th>
      <th>Source &amp; Target</th>
    </thead>
    <tbody>
    <?php foreach($webmentions as $mention): ?>
      <tr<?= $mention['status'] == 'pending' ? ' class="warning"' : '' ?>>
        <td class="status">
          <div class="popup" data-content="<?= $mention['status'] ?>">
            <a href="/webmention/<?= $mention['webmention']->token ?>/details">
              <i class="circular inverted <?= $mention['icon'] ?> icon"></i>
            </a>
          </div>
        </td>
        <td class="date">
          <a href="/webmention/<?= $mention['webmention']->token ?>/details">
            <?= date('M j, g:ia', strtotime($mention['webmention']->created_at)) ?>
          </a>
        </td>
        <td class="urls">
          source=<a href="<?= $this->e($mention['webmention']->source) ?>"><?= $this->e($mention['webmention']->source) ?></a><br>
          target=<a href="<?= $this->e($mention['webmention']->target) ?>"><?= $this->e($mention['webmention']->target) ?></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <? else: ?>
    <div class="ui message">It looks like you haven't sent any webmentions yet! Try entering one of your post URLs above and send some.</div>
  <? endif; ?>

</div>

<script>
$(function(){
  var token = "<?= $role->token ?>";
  $(".tabular.menu .item").tab();

  $(".popup").popup();

  $("#send-webmention-btn").click(function(){
    $("#send-webmention-btn").addClass("loading");
    $("#send-webmention-form").removeClass("error");
    // Send the request to the API now, and then redirect to the status page
    $.ajax({
      url: "/webmention",
      method: "POST",
      data: {
        token: token,
        source: $("#send-source").val(),
        target: $("#send-target").val()
      },
      success: function(data){
        $("#send-webmention-btn").removeClass("loading");
        window.location = data.location+"/details";
      },
      error: function(data){
        $("#send-webmention-btn").removeClass("loading");
        $("#send-webmention-form").addClass("error");
        $("#send-webmention-form .error.message").text(data.responseJSON.error_description);
      }
    });

    return false;
  });
});
</script>
