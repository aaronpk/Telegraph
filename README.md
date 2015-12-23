# Telegraph

Telegraph is an API for sending [Webmentions](http://webmention.net).

## Send a webmention to a specific page
Post to `https://telegraph.p3k.io/webmention`

* `token` - your API key obtained after signing up
* `source` - the URL of your post
* `target` - the URL you linked to
* `callback` - (optional) - a URL that will receive a web hook when new information about this webmention is available

The Telegraph API will validate the parameters and then queue the webmention for sending. If there was a problem with the request, you will get an error response immediately.

The API will first make an HTTP request to the source URL, and look for a link to the target on the page. This happens synchronously so you will get this error reply immediately.

#### Errors
* `authentication_required` - the token parameter was missing
* `invalid_token` - the token was invalid or expired
* `missing_parameters` - one or more of the three parameters were not in the request
* `invalid_parameter` - one or more of the parameters were invalid, e.g. the target was not a valid URL
* `source_not_html` - the source document could not be parsed as HTML (only in extreme cases, most of the time it just accepts whatever)
* `no_link_found` - the link to the target URL was not found on the source document

An error response in this case will be returned with an HTTP 400 status code an a JSON body:

```json
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

### Status API

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


### Callback Events
After Telegraph processes your request, you will receive a post to the callback URL. The initial callback you receive will be one of the status codes returned by the status API.

Typically, webmention endpoints defer processing until later, so normally the first callback received will indicate that the webmention was queued. This callback will normally be sent relatively quickly after you make the initial request, typically within a few seconds.

If the webmention endpoint provides status updates, either through a status URL or web hook, then Telegraph will deliver follow-up notifications when it gets updated information.

A callback from Telegraph will include the following post body parameters:

* `source` - the URL of your post
* `target` - the URL you linked to
* `type` - "pingback" or "webmention" depending on what was discovered at the target
* `status` - one of the status codes above, e.g. `accepted`
* `location` - if further updates will be available, the status URL where you can check again in the future



## Credits

Telegraph photo: https://www.flickr.com/photos/nostri-imago/3407786186

Telegraph icon: https://thenounproject.com/search/?q=telegraph&i=22058
