<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container" style="margin-top: 80px; margin-bottom: 40px;">

  <h2>Send Webmentions</h2>

  Source URL: <a href="<?= $url ?>" target="_blank"><?= $url ?></a>

  <table class="ui very basic fixed single line unstackable table" id="send-table">
    <thead>
      <th class="twelve wide">URL</th>
      <th class="four wide">Status</th>
    </thead>
    <tbody>
      <tr><td colspan="2">Looking for URLs...</td></tr>
    </tbody>
  </table>

</div>
<script>
var source_url = "<?= $url ?>";
var token = "<?= $role->token ?>";

$(function(){
  $.post('/dashboard/get_outgoing_links.json', {
    url: source_url
  }, function(data) {
    if(data.links.length == 0) {
      $("#send-table tbody tr:first td").html('<div class="ui message">No links were found from the given URL. Make sure your post is marked up with <a href="https://indieweb.org/h-entry">h-entry</a> and contains some links.</div>');
      $("#send-table").removeClass("fixed").removeClass("single").removeClass("line");
      return;
    }

    $("#send-table tbody").html('<tr><td colspan="2"></td></tr>');
    for(var i in data.links) {
      $("#send-table tr:last").after('<tr data-url="'+data.links[i]+'">'
          +'<td class="target-url">'
            +'<div class="popup" data-content="'+data.links[i]+'"><span>'+data.links[i]+'<span></div>'
          +'</td>'
          +'<td class="send">'
            +'<div class="ui active mini inline loader"></div>'
          +'</td>'
        +'</tr>');
    }

    $("#send-table tbody tr:first").remove();

    // Enable popup on any values that overflowed the container
    $(".popup").each(function(i,el){
      if($(el).children("span").width() > $(el).width()) {
        $(el).popup();
      }
    });

    // Check for a webmention or pingback endpoint
    $("#send-table tr").each(function(i,el){
      discover_endpoint(el, false);
    });

  });
});

function discover_endpoint(row, ignore_cache) {
  $.post("/dashboard/discover_endpoint.json", {
    target: $(row).data("url"),
    ignore_cache: ignore_cache
  }, function(data){
    var html;
    if(data.status == 'none') {
      html = '<div class="ui yellow horizontal label">No endpoint found</div><br><button class="send-button check-again ui button">Check Again</button>';
    } else if(data.status == 'webmention') {
      html = '<button class="send-button send-now ui primary button">Send Webmention</button>';
    } else if(data.status == 'pingback') {
      html = '<button class="send-button send-now ui primary button">Send Pingback</button>';
    }
    $(row).children(".send").html(html);
    bind_send_buttons();
  });
}

function bind_send_buttons() {
  $(".send-button").unbind("click");
  $(".check-again").bind("click", function(){
    var row = $(this).parents("tr");
    $(row).find(".send-button").addClass('loading');
    discover_endpoint(row, true);
  });
  $(".send-now").bind("click", function(){
    var row = $(this).parents("tr");
    $(row).find(".send-button").addClass('loading');
    // Send to the API
    $.post("/webmention", {
      token: token,
      source: source_url,
      target: $(row).data("url")
    }, function(data){
      $(row).find(".send-button").removeClass('loading');
      if(data.status == 'queued') {
        $(row).find(".send").html('<a href="'+data.location+'/details" data-status="'+data.location+'"><i class="circular inverted orange wait icon"></i></a>');
        // TODO: check for status updates on a timer
      } else {
        $(row).find(".send").html('<i class="circular inverted red x icon"></i>');
      }
    });
  });
}

</script>
<style type="text/css">
.popup {
  word-wrap: break-word;
}
#send-table tbody tr {
  height: 83px;
}
</style>
