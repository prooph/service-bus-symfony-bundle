# Getting started

This documentation covers just the configuration of the Prooph Service Bus in Symfony.
To inform yourself about the ProophServiceBus please have a look at the
[official documentation](http://docs.getprooph.org/service-bus/).

## Download the Bundle

Download the bundle using composer by running
```bash
composer require prooph/service-bus-symfony-bundle
```
at the root of your Symfony project.

## Enable the Bundle

To start using this bundle, register the bundle in your application's kernel class:
```php
<?php
// app/AppKernel.php
// …
class AppKernel extends Kernel
{
    // …
    public function registerBundles()
    {
        $bundles = [
            // …
            new Prooph\Bundle\ServiceBus\ProophServiceBusBundle(),
            // …
        ];
        // …
    }
    // …
}
```

or, if you are using [the new flex structure](https://symfony.com/doc/current/setup/flex.html):
```php
<?php
// config/bundles.php

return [
    // …
    Prooph\Bundle\ServiceBus\ProophServiceBusBundle::class => ['all' => true],
];
```

## Configure your first command bus

There are three different types of message bus supported by the ProophServiceBus.
While they have totally different purposes, their configuration is nearly the same.
As an example, we will configure a command bus.
For query bus and event bus, please have a look at the [configuration reference](./configuration_reference.html). 

The command bus is configured in `app/config/config.yml`
(or `config/packages/prooph_service_bus.yaml` if you are using flex):
```yaml
prooph_service_bus:
    command_buses:
        acme_command_bus: ~
```

That's all you need to define a (useless) command bus. Let's make it useful.

### Route your first command handler

We assume that you already have a command `Acme\Command\RegisterUser`
and a command handler `Acme\Command\RegisterUserHandler`.
We will define the command handler as a regular service in Symfony:
```yaml
# app/config/services.yml or (flex) config/packages/prooph_service_bus.yaml
services:
    Acme\Command\RegisterUserHandler: ~
```

To route the command `Acme\Command\RegisterUser` to this service, we just need to add a tag to this definition:
```yaml
services:
    Acme\Command\RegisterUserHandler:
        tags:
            - { name: 'prooph_service_bus.acme_command_bus.route_target' }
```

Now we are ready to dispatch our command.

## Dispatching the command

Given our configuration
```yaml
# app/config/config.yml or (flex) config/packages/prooph_service_bus.yaml
prooph_service_bus:
    command_buses:
        acme_command_bus: ~
```

we can access the command bus from the container using the ID `prooph_service_bus.acme_command_bus`:

```php
<?php
// …
class RegisterUserController extends Controller
{
    public function indexAction()
    {
        // …
        $this
            ->get('prooph_service_bus.acme_command_bus')
            ->dispatch(new RegisterUser(/* … */));
        // …
    }
}
```

That was everything we need to configure and dispatch our first command.
Perhaps you want to know more about [routing](./routing.html),
about how to customize message buses with [plugins](./plugins.html)
or about what information will be included in the [profiler](./profiler.html)?
