# Configuration Reference

```yaml
prooph_service_bus:
    command_buses:
        # Multiple command buses can be defined.
        # The identifier must be unique over all buses.
        # It can be accessed via prooph_service_bus.<identifier>
        # e.g. prooph_service_bus.acme_command_bus
        acme_command_bus:
            # Service ID of the message factory
            message_factory: 'prooph_service_bus.message_factory'
            # Service ID of the message data converter
            message_data_converter: 'prooph_service_bus.message_data_converter'
            # Service ID of the message converter
            message_converter: 'prooph_service_bus.message_converter'
            router:
                # Service ID of the async message producer e.g. for Amazon AWS SQS
                async_switch: 'my_async_message_producer'
                # Service ID of the router
                type: 'prooph_service_bus.command_bus_router'
                # Routing definition constructed as
                # 'message-name': 'service.id.of.the.handler'
                routes: {}
            # Service IDs of plugins utilized by this command bus
            plugins: []
            
    event_buses:
        # Multiple event buses can be defined.
        # The identifier must be unique over all buses.
        # It can be accessed via prooph_service_bus.<identifier>
        # e.g. prooph_service_bus.acme_event_bus
        acme_event_bus:
            # Service ID of the message factory
            message_factory: 'prooph_service_bus.message_factory'
            # Service ID of the message data converter
            message_data_converter: 'prooph_service_bus.message_data_converter'
            # Service ID of the message converter
            message_converter: 'prooph_service_bus.message_converter'
            router:
                # Service ID of the async message producer e.g. for Amazon AWS SQS
                async_switch: 'my_async_message_producer'
                # Service ID of the router
                type: 'prooph_service_bus.event_bus_router'
                # Routing definition constructed as
                # message-name:
                #   - 'service-id-of-one-handler'
                routes: {}
            # Service IDs of plugins utilized by this event bus
            plugins: []
            
    query_buses:
        # Multiple query buses can be defined.
        # The identifier must be unique over all buses.
        # It can be accessed via prooph_service_bus.<identifier>
        # e.g. prooph_service_bus.acme_query_bus
        acme_query_bus:
            # Service ID of the message factory
            message_factory: 'prooph_service_bus.message_factory'
            router:
                # Service ID of the async message producer e.g. for Amazon AWS SQS
                async_switch: 'my_async_message_producer'
                # Service ID of the router
                type: 'prooph_service_bus.query_bus_router'
                # Routing definition constructed as
                # 'message-name': 'service.id.of.the.handler'
                routes: {}
            # Service IDs of plugins utilized by this query bus
            plugins: []
```
