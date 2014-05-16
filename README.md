TimeTraveler
===========

[![Build Status](https://secure.travis-ci.org/rezzza/TimeTraveler.png)](http://travis-ci.org/rezzza/TimeTraveler)

Mock the time system in PHP using [AOP-PHP](https://github.com/AOP-PHP/AOP). You can now travel the time on your application easily !

Methods supported
-----------------

- `DateTime` object.
- `time`
- `microtime`

Usage
-----

```php
Rezzza\TimeTraveler::enable();
Rezzza\TimeTraveler::setCurrentDate('2011-06-10 11:00:00');

var_dump(new \DateTime());           // 2011-06-10 11:00:00
var_dump(new \DateTime('+2 hours')); // 2011-06-10 13:00:00
var_dump(time());
var_dump(microtime());
var_dump(microtime(true));
```


Launch tests
------------

```
composer install --dev
bin/atoum -d tests/units
```