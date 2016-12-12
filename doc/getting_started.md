# Getting started

This documentation covers just the configuration of the Prooph Service Bus in Symfony.
To inform yourself about the ProophServiceBus please have a look at the
[official documentation](http://getprooph.org/service-bus/intro.html).

## Download the Bundle

Download the bundle using composer by running
```bash
composer require prooph/service-bus-symfony-bundle
```
at the root of your Symfony project.

## Enable the Bundle

To start using this bundle, register the bundle in your application's kernel class:
```php
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

## Configure your first command bus

There are three different types of message bus supported by the ProophServiceBus.
While they have totally different purposes, their configuration is nearly the same.
As an example, we will configure a command bus.
For query bus and event bus, please have a look at the [configuration reference](./configuration_reference.html). 

The command bus is configured in `app/config/config.yml`:
```yaml
# app/config/config.yml
prooph_service_bus:
    command_buses:
        acme_command_bus: ~
```

That's all you need to define a (useless) command bus. Let's make it useful.

### Route to a command handler

There are two ways to route a command to a command handler.
You can simply add it to the ProophServiceBus configuration:

```yaml
# app/config/config.yml
prooph_service_bus:
    command_buses:
        acme_command_bus:
            router:
                routes:
                    'Acme\Command\RegisterUser': '@acme.command.register_user_handler'
```

In this case `Acme\Command\RegisterUser` would be the name of your command (which usually corresponds to its class name)
and `acme.command.register_user_handler` the service-id of the handler (that you have to normally configure in Symfony).
The `@` before the service-id can be omitted, but it provides auto completion in some IDEs.

> **Note**: When configuring the event bus you can pass an array of service IDs for each event instead of a single service ID.
> This is necessary because events can be routed to multiple event handlers. 

The main benefit of this way is that you have all command handlers registered in one place.
But it has also some drawbacks:
You have to configure every command handler for itself and add it to the routes of the command bus,
which implies changes at two different places for adding one command handler.
Also, because the name of the command usually corresponds to the class of the command, it is vulnerable for refactoring.

Therefore, you can also route a command to a command handler using tags. This will look like this:
```yaml
# app/config/services.yml
services:
    acme.command.register_user_handler:
        class: Acme\Command\RegisterUserHandler
        tags:
            - { name: 'prooph_service_bus.acme_command_bus.route_target' }
```
The bundle will try to detect the name of the command by itself.

If this feels like too much magic or if the detection fails, you can still pass the name of the command as attribute:
```yaml
# app/config/services.yml
services:
    acme.command.register_user_handler:
        class: Acme\Command\RegisterUserHandler
        tags:
            - { name: 'prooph_service_bus.acme_command_bus.route_target', message: 'Acme\Command\RegisterUser' }
```

> **Hint:** If you rely on automatic message detection and your handler handles multiple messages of the same message bus,
> you need to tag the handler just once. 

Both options have its advantages and disadvantages.
The result is the same, so it's up to your personal preference which option you choose.

### Add a plugin

[Plugins](http://getprooph.org/service-bus/plugins.html) are a great way to expand a message bus. 
Let's assume that we want to use the `HandleCommandStrategy` for our command bus.
Again, there are two options to do this.
Both require to define a service for the plugin:
```yaml
# app/config/services.yml
services:
    acme.prooph.plugin.handle_command_strategy:
        class: Prooph\ServiceBus\Plugin\InvokeStrategy\HandleCommandStrategy
```

Let's start with the first option, modifying our `app/config/config.yml`:
```yaml
# app/config/config.yml
prooph_service_bus:
    command_buses:
        acme_command_bus:
            plugins:
                - "acme.prooph.plugin.handle_command_strategy"
```
That is all you need to do to register the plugin.

Now we will have a look at the other option, using tags.
Therefore, we just need to add a tag to the service configuration:
```yaml
# app/config/services.yml
services:
    acme.prooph.plugin.handle_command_strategy:
        class: Prooph\ServiceBus\Plugin\InvokeStrategy\HandleCommandStrategy
        tags:
            - { name: 'prooph_service_bus.acme_command_bus.plugin' }
```

> **Hint:** If you want to register the plugin for more than one message bus, you can use
>  - `prooph_service_bus.command_bus.plugin` to register it for every command bus (resp. `prooph_service_bus.query_bus.plugin` and `prooph_service_bus.event_bus.plugin`) or
>  - `prooph_service_bus.plugin` to register it for every message bus.

## Access the command bus

Given our configuration
```yaml
# app/config/config.yml
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
        $this->get('prooph_service_bus.acme_command_bus')
            ->dispatch(new RegisterUser(/* … */));
        // …
    }
}
```