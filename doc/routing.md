# Routing

You can define your message handlers like every service in Symfony.
But you need to tell your message bus that a handler is a target for a message object.

There are two ways to route a message object to a handler.
You can configure it using a tag at the handler definition or directly at the bus configuration.

The basis for both ways is the service definition of the handler
```yaml
# app/config/services.yml
services:
    Acme\Command\RegisterUserHandler: ~
```

and a configured message bus:
```yaml
# app/config/config.yml or (flex) config/packages/prooph_service_bus.yaml
prooph_service_bus:
    command_buses:
        acme_command_bus: ~
```
The command bus is used as example, handlers are routed to each bus (nearly) the same way.

## Routing using tags

If you don't know about tags in Symfony please have a look at the
[official documentation](http://symfony.com/doc/current/service_container/tags.html).

To route a message to a specific handler, we just need to add a tag to its service definition:
```yaml
# app/config/services.yml
services:
    Acme\Command\RegisterUserHandler:
        tags:
            - { name: 'prooph_service_bus.acme_command_bus.route_target', message: Acme\Command\RegisterUser }
```

The name of the tag is simple `prooph_service_bus.<name-of-the-bus>.route_target`.
The additional `message` attribute defines the message name (message class by default) that is routed to the handler.

If your handler handles multiple messages, you need to add a tag for each one.

> **Important**: Your handlers must be `public`.

### Automatic message detection

If you are not afraid of a little bit magic
you can use automatic message detection to simplify the configuration and make it less vulnerable for refactoring.
Instead of defining the `message` attribute add a `message_detection` attribute:

```yaml
# app/config/services.yml
services:
    Acme\Command\RegisterUserHandler:
        tags:
            - { name: 'prooph_service_bus.acme_command_bus.route_target', message_detection: true }
```

The bundle will try to detect the message itself
by analyzing the methods of the handler and creating instances of the message objects.
But don't worry about performance because this will happen on compiling.

> **Hint:** If you rely on automatic message detection and your handler handles multiple messages of the same message bus,
> you need to tag the handler just once.

> **Hint:** Registering handlers can be extremely compact using
> the [new DI features of Symfony 3.3](http://symfony.com/doc/current/service_container/3.3-di-changes.html):
>
> ```yaml
> services:
>   _defaults:
>     autowire: true
>   
>   App\Command\:
>     resource: '../../src/Command/*Handler.php'
>     tags: [{ name: 'prooph_service_bus.acme_command_bus.route_target', message_detection: true }]
> ``` 

## Routing at the bus

If you are no fan of service tags, you can route the messages directly at the bus configuration:
```yaml
# app/config/config.yml or (flex) config/packages/prooph_service_bus.yaml
prooph_service_bus:
    command_buses:
        acme_command_bus:
            router:
                routes:
                    'Acme\Command\RegisterUser': 'acme.command.register_user_handler'
```
This will work the same way for query buses.

When configuring event buses you can pass an array of service IDs for each event instead of a single service ID.
This is necessary because events can be routed to multiple event handlers.

> **Hint:** To get autocompletion in some IDEs you can prepend the service id
> with an `@` (`"@acme.command.register_user_handler"`).
>
> The bundle will recognize this and find your handler anyway.

Which way you choose to configure your routing is up to you.
Each way has its benefits and drawbacks.
