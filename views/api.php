<?
use \Michelf\MarkdownExtra;
$this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user, 'return_to' => $return_to]);
?>

<div class="ui main text container api-docs" style="margin-top: 80px;">

  <h1>Telegraph API</h1>

<? ob_start(); ?>
<h2 class="ui dividing header">Send a webmention to a specific page</h2>
Post to `https://telegraph.p3k.io/webmention`

* `token` - your API key obtained after signing up
* `source` - the URL of your post
* `target` OR `target_domain` - the URL or domain you linked to, respectively
* `callback` - (optional) - a URL that will receive a web hook when new information about this webmention is available

The Telegraph API will validate the parameters and then queue the webmention for sending. If there was a problem with the request, you will get an error response immediately.

The API will first make an HTTP request to the source URL, and look for a link to the target on the page. This happens synchronously so you will get this error reply immediately.

If you pass `target_domain` instead of `target`, Telegraph will find and enqueue webmentions for all links to that domain.

#### Errors
* `authentication_required` - the token parameter was missing
* `invalid_token` - the token was invalid or expired
* `missing_parameters` - one or more of the three parameters were not in the request
* `invalid_parameter` - one or more of the parameters were invalid, e.g. the target was not a valid URL
* `source_not_html` - the source document could not be parsed as HTML (only in extreme cases, most of the time it just accepts whatever)
* `no_link_found` - the link to the target URL was not found on the source document

An error response in this case will be returned with an HTTP 400 status code an a JSON body:

```
HTTP/1.1 400 Bad Request
Content-type: application/json

{
  "error": "missing_parameters",
  "error_description": "The source or target parameters were missing"
}
```

#### Success

If the initial validation succeeds, Telegraph will queue the webmention for sending and return a success response, including a URL you can check for status updates. This URL will be returned even if you also provide a callback URL. The URL will be available in both the `Location` header as well as in the JSON response.

```
HTTP/1.1 201 Created
Content-type: application/json
Location: https://telegraph.p3k.io/webmention/xxxxxxxx

{
  "status": "queued",
  "location": "https://telegraph.p3k.io/webmention/xxxxxxxx"
}
```

If you use `target_domain` instead of `target`, the `location` field will be a list containing the status URLs for each webmention that was queued. The `Location` header will be omitted.

```
HTTP/1.1 201 Created
Content-type: application/json

{
  "status": "queued",
  "location": [
    "https://telegraph.p3k.io/webmention/xxxxxxxx",
    "https://telegraph.p3k.io/webmention/yyyyyyyy"
  ]
}
```

<h2 class="ui dividing header">Status API</h2>

You can poll the status URL returned after queuing a webmention for more information on the progress of sending the webmention. The response will look like the following:

```
HTTP/1.1 200 OK
Content-Type: application/json

{
  "status": "queued",
  "summary": "The webmention is still in the processing queue.",
  "location": "https://telegraph.p3k.io/webmention/xxxxxxxx"
}
```

```
HTTP/1.1 200 OK
Content-Type: application/json

{
  "status": "no_link_found",
  "summary": "No link was found from source to target"
}
```

```
HTTP/1.1 200 OK
Content-Type: application/json

{
  "status": "success",
  "type": "webmention",
  "endpoint":
  "summary": "The webmention request was accepted.",
  "location": "https://telegraph.p3k.io/webmention/xxxxxxxx"
}
```

The possible fields that are returned are as follows:

* `status` - One of the status codes listed below
* `type` - optional - "webmention" or "pingback", depending on what was discovered at the target
* `endpoint` - optional - The webmention or pingback endpoint that was discovered
* `http_code` - optional - The HTTP code that the webmention or pingback endpoint returned
* `summary` - optional - A human-readable summary of the status
* `location` - optional - If present, you can continue checking this URL for status updates. If not present, no further information will be available about this request.

Other possible status codes are listed below.

* `accepted` - the webmention or pingback request was accepted (pingback does not differentiate between when a request is queued or processed immediately)
* `success` - the webmention status endpoint indicated the webmention was successful after processing it
* `not_supported` - no webmention or pingback endpoint was found at the target
* `no_link_found` - no link was found from source to target

Other status codes may be returned depending on the receiver's status endpoint. You should only assume a webmention was successfully sent if the status is `success` or `accepted`. If the response does not contain a `location` parameter you should not continue polling the endpoint.


<h2 class="ui dividing header">Callback Events</h2>
After Telegraph processes your request, you will receive a post to the callback URL. The initial callback you receive will be one of the status codes returned by the status API.

Typically, webmention endpoints defer processing until later, so normally the first callback received will indicate that the webmention was queued. This callback will normally be sent relatively quickly after you make the initial request, typically within a few seconds.

If the webmention endpoint provides status updates, either through a status URL or web hook, then Telegraph will deliver follow-up notifications when it gets updated information.

A callback from Telegraph will include the following post body parameters:

* `source` - the URL of your post
* `target` - the URL you linked to
* `type` - "pingback" or "webmention" depending on what was discovered at the target
* `status` - one of the status codes above, e.g. `accepted`
* `location` - if further updates will be available, the status URL where you can check again in the future
<?
$source=ob_get_clean();
echo MarkdownExtra::defaultTransform($source);
?>

</div>
