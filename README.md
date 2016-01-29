# Telegraph

Telegraph is an API for sending [Webmentions](http://webmention.net).

## API

See https://telegraph.p3k.io/api

## Developing

* Fork and clone this repo.
* Install MySQL if it's not already installed.
* Copy `config.template.php` to `config.test.php` and fill in the appropriate
  values for your local environment.
* Install the dependences, create a local database, and run the tests:
    
    ```sh
    $ composer install
    $ mysql [ARGS] -e 'CREATE DATABASE telegraph;'
    $ mysql [ARGS] < schema.sql
    $ phpunit
    # Hack hack hack!
    ```

## Credits

Telegraph photo: https://www.flickr.com/photos/nostri-imago/3407786186

Telegraph icon: https://thenounproject.com/search/?q=telegraph&i=22058
