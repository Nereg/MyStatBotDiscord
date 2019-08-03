# Plasma [![Build Status](https://travis-ci.org/PlasmaPHP/core.svg?branch=master)](https://travis-ci.org/PlasmaPHP/core) [![Build Status](https://scrutinizer-ci.com/g/PlasmaPHP/core/badges/build.png?b=master)](https://scrutinizer-ci.com/g/PlasmaPHP/core/build-status/master) [![Code Coverage](https://scrutinizer-ci.com/g/PlasmaPHP/core/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/PlasmaPHP/core/?branch=master)

Plasma provides an asynchronous, non-blocking (data access) Database Abstraction Layer. This is the core component, defining common parts and interfaces.

The core component alone does __nothing__, you need a Plasma driver, which does all the handling of the DBMS.

Plasma does not aim to be a full Database Abstraction Layer. Simulating missing features is not a goal and should never be.

For a list of drivers, see the [main repository](https://github.com/PlasmaPHP/plasma).

# Getting Started
As soon as you have selected a driver, you can install it using `composer`. For the core, the command is

```
composer require plasma/core
```

Each driver has their own dependencies, as such they have to implement a factory, which creates their driver instances correctly. For more information, see the driver project page.

But this is some little pseudo code:

```php
$loop = \React\EventLoop\Factory::create();
$factory = new \SomeGuy\PlasmaDriver\MsSQLFactory($loop);

$client = \Plasma\Client::create($factory, 'root:1234@localhost');

$client->execute('SELECT * FROM `users`', [])
    ->then(function (\Plasma\QueryResultInterface $result) use ($client) {
        // Do something with the query result
        // Most likely for a SELECT query,
        // it will be a streaming query result
        
        $client->close()->done();
    }, function (\Throwable $error) use ($client) {
        // Oh no, an error occurred!
        echo $error.\PHP_EOL;
        
        $client->close()->done();
    });

$loop->run();
```

# Documentation
https://plasmaphp.github.io/core/
