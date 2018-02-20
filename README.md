# ProophServiceBus (PSB) Symfony bundle
[![Build Status](https://travis-ci.org/prooph/service-bus-symfony-bundle.svg?branch=master)](https://travis-ci.org/prooph/service-bus-symfony-bundle)
[![Coverage Status](https://coveralls.io/repos/prooph/service-bus-symfony-bundle/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/service-bus-symfony-bundle?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)


## A note about versions

Version 0.4 of the bundle works with `prooph/service-bus` v6.0 and above.

Use version 0.3 for `prooph/service-bus` 5.x.

## Installation

Installation of this Symfony bundle uses Composer. For Composer documentation, please refer to
[getcomposer.org](http://getcomposer.org/).

Run `composer require prooph/service-bus-symfony-bundle` to install prooph service-bus-symfony-bundle.

> See [Symfony Proophessor-Do demo application](https://github.com/prooph/proophessor-do-symfony) for an example.

## Documentation
For the latest online documentation visit [http://getprooph.org/](http://getprooph.org/ "Latest documentation").

Documentation is [in the doc tree](doc/), and can be compiled using [bookdown](http://bookdown.io)

```console
$ ./vendor/bin/bookdown doc/bookdown.json
$ php -S 0.0.0.0:8080 -t doc/html/
```

Then browse to [http://localhost:8080/](http://localhost:8080/)

## Support

- Ask questions on Stack Overflow tagged with [#prooph](https://stackoverflow.com/questions/tagged/prooph).
- File issues at [https://github.com/prooph/service-bus-symfony-bundle/issues](https://github.com/prooph/service-bus-symfony-bundle/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## License

Released under the [New BSD License](LICENSE.md).
