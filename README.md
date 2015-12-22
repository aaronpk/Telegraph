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
  "result": "queued",
  "status": "https://telegraph.p3k.io/webmention/xxxxxxxx"
}
```

### Callback Events
After Telegraph processes your request, you will receive a post to the callback URL. The initial callback you receive will be one of the following status codes:

* `not_supported` - no webmention or pingback endpoint found
* `webmention_accepted` - the webmention request was accepted
* `webmention_error` - the webmention endpoint returned an error code
* `pingback_accepted` - pingback was accepted (pingback does not differentiate between when a request is queued or processed immediately)
* `pingback_error` - the pingback endpoint returned an error code

Typically, webmention endpoints defer processing until later, so normally the first callback received will indicate that the webmention was queued. This callback will normally be sent relatively quickly after you make the initial request, typically within a few seconds.

If the webmention endpoint provides status updates, either through a status URL or web hook, then Telegraph will deliver follow-up notifications when it gets updated information.

A callback from Telegraph will include the following post body parameters:
* `source` - the URL of your post
* `target` - the URL you linked to
* `status` - one of the status codes above, e.g. `webmention_queued`

## Credits

Telegraph photo: https://www.flickr.com/photos/nostri-imago/3407786186

Telegraph icon: https://thenounproject.com/search/?q=telegraph&i=22058
