<?php $this->layout('layout-loggedin', ['title' => $title, 'user' => $user, 'accounts' => $accounts]); ?>

<div class="ui main text container" style="margin-top: 80px; margin-bottom: 40px;">

  <h2 class="site-name">Send a Webmention</h2>

  <form class="ui form" id="send-webmention-form">
    <div class="two fields">
      <div class="field"><label>Source URL</label><input type="url" placeholder="Source URL" id="send-source"></div>
      <div class="field"><label>Target URL</label><input type="url" placeholder="Target URL" id="send-target"></div>
    </div>
    <div class="ui error message"></div>
    <button class="ui button right floated" id="send-webmention-btn">Send Webmention</button>
    <div style="clear:both;"></div>
  </form>

  <div style="margin-top: 2em;">
    <p>Enter a source URL (your post) and target URL (the post you linked to).</p>
    <p>Telegraph will discover the Webmention endpoint of the target URL and send the Webmention for you.</p>
    <p>You'll be able to see the progress after you click "send".</p>
  </div>

</div>

<script>
$(function(){
  var csrf = "<?= $csrf ?>";

  $("#send-source").focus();

  $("#send-webmention-btn").click(function(){
    $("#send-webmention-btn").addClass("loading");
    $("#send-webmention-form").removeClass("error");
    // Send the request to the API now, and then redirect to the status page
    $.ajax({
      url: "/webmention",
      method: "POST",
      data: {
        _csrf: csrf,
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
