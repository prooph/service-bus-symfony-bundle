# Plugins

Plugins are a great way to expand a message bus with additional functionality.
You can find more information about PSB plugins and a list of included plugins in the
[documentation of the Service Bus](http://docs.getprooph.org/service-bus/plugins.html) itself.

There are two ways to attach a plugin to a bus.
You can configure it using a tag at the plugin definition or directly at the bus configuration.

We will show you the configuration using the example of the
`Prooph\ServiceBus\Plugin\InvokeStrategy\HandleCommandStrategy` – which is part of the PSB –
but de facto each plugin will be attached the same way. 

The basis for both ways is the service definition of the plugin:
```yaml
# app/config/services.yml
services:
    acme.prooph.plugin.handle_command_strategy:
        class: Prooph\ServiceBus\Plugin\InvokeStrategy\HandleCommandStrategy
```

and a configured message bus:
```yaml
# app/config/config.yml or (flex) config/packages/prooph_service_bus.yaml
prooph_service_bus:
    command_buses:
        acme_command_bus: ~
```
The command bus is used as example again, plugins are attached to each bus the same way.

## Definition using tags

If you don't know about tags in Symfony please have a look at the
[official documentation](http://symfony.com/doc/current/service_container/tags.html).

To attach a plugin to a specific message bus, we just need to add a tag to its service definition:
```yaml
# app/config/services.yml
services:
    acme.prooph.plugin.handle_command_strategy:
        class: Prooph\ServiceBus\Plugin\InvokeStrategy\HandleCommandStrategy
        tags:
            - { name: 'prooph_service_bus.acme_command_bus.plugin' }
```

The name of the tag is simple `prooph_service_bus.<name-of-the-bus>.plugin`.
There are no additional attributes that can be set.

But sometimes you want to attach a plugin to more than one message bus, e.g. for logging.
Of course you could add multiple tags, but for two use cases there are special tags:
 
 - To attach the plugin to every command bus, you can use the tag `prooph_service_bus.command_bus.plugin`
   (resp. `prooph_service_bus.query_bus.plugin` for every query bus and `prooph_service_bus.event_bus.plugin` for every event bus).
 - To attach the plugin to every message bus (no matter whether it is a command bus, a query bus or an event bus)
   you can use the tag `prooph_service_bus.plugin`. 

That is everything you need to know about attaching plugins to a message bus using tags.

Let's now have a look at the other way.

## Definition at the bus

If you are no fan of service tags, you can attach the plugin directly at the bus configuration:
```yaml
# app/config/config.yml or (flex) config/packages/prooph_service_bus.yaml
prooph_service_bus:
    command_buses:
        acme_command_bus:
            plugins:
                - "acme.prooph.plugin.handle_command_strategy"
```
Again this will work the same way for query buses and event buses.

> **Hint:** To get autocompletion in some IDEs you can prepend the service id
> with an `@` (`"@acme.prooph.plugin.handle_command_strategy"`).
>
> The bundle will recognize this and find your plugin anyway.

If you attach your plugins directly at the bus configuration, they will be at a central place.
But there is no way to attach them to every message bus or every message bus of a type like when using tags. 
