<?php $this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user]); ?>

<div class="ui main text container" style="margin-top: 80px;">

  <h2>Send Webmentions</h2>

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
var url = "<?= $url ?>";
var token = "<?= $role->token ?>";

$(function(){
  $.post('/dashboard/get_outgoing_links.json', {
    url: url
  }, function(data) {
    $("#send-table tbody").html('<tr><td colspan="2"></td></tr>');
    for(var i in data.links) {
      console.log(data.links[i]);
      $("#send-table tr:last").after('<tr>'
          +'<td class="target-url">'
            +'<div class="popup" data-content="'+data.links[i]+'">'+data.links[i]+'</div>'
          +'</td>'
          +'<td><button class="ui button">Send</button></td>'
        +'</tr>');
    }
    $("#send-table tbody tr:first").remove();
    $(".popup").popup();
  });
});

</script>
<style type="text/css">
.popup {
  word-wrap: break-word;
}
</style>
