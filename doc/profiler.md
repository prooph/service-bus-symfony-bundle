# Symfony Profiler

The ProophServiceBusBundle provides several plugins that help you to inspect your application.
For most of them you will need access to the Symfony Profiler, so please ensure that you have installed the
[WebProfilerBundle](https://packagist.org/packages/symfony/web-profiler-bundle).

## DataCollectorPlugin

The DataCollectorPlugin gathers data about the dispatched messages and shows them in an extra section within
the Symfony Profiler.
There is one profile for each bus type and they are automatically enabled if `kernel.debug` is `true`.

![Example of a timeline with a command and an event](profiler_data_collector_sections.png)

## PsrLoggerPlugin

The PsrLoggerPlugin fills your log with information about your dispatched messages.
There will be one PsrLoggerPlugin automatically registered for each defined message bus.

You can find the logged messages either in the *Logs* section of the Symfony Profiler or directly in your log
(depending on how your logging is configured).

![Example of a timeline with a command and an event](profiler_logs.png)

## StopwatchPlugin

The StopwatchPlugin is automatically enabled if `kernel.debug` is `true`
(which is the case e.g. in the `dev` environment).
It times the execution time of your command- and event-handlers.
The collected data are shown within the *Performance* section of the Symfony Profiler.

For an example with a executed command and an executed event please have a look at the following image:

![Example of a timeline with a command and an event](profiler_timeline.png)
