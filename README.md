# Telegraph

Telegraph is an API for sending [Webmentions](http://webmention.net).

## API

See https://telegraph.p3k.io/api

## Developing

* Fork and clone this repo.
* Install MySQL, composer, beanstalk, and phpunit if they're not already
  installed, e.g. `brew install mysql composer beanstalk phpunit`.
* Start MySQL and `beanstalkd`.
* Copy `config.template.php` to `config.test.php`. Update the appropriate values
  for your local environment if necessary.
* Run these commands to install the dependencies, create a local database, and
  run the tests:
    
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

## License

Copyright 2016 by Aaron Parecki

Available under the Apache 2.0 license. See [LICENSE](LICENSE).

