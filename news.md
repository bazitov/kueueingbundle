# Ver 0.6

* IMPROVED: when the consumer stops because of a unix signal or too much memory used, print the reason

* IMPROVED: make the consolecommand consumer accept messages without 'arguments' and 'options' members in the payload


# Ver 0.5

* NEW: If the consumer in use supports it, let it handle unix signals so that it can stop gracefully without loosing data
       (up to now only the rabbitmq consumer was able to do that)


# Ver 0.4.1

* FIXED: The routing key defined in consumer queues was not being applied when running the consumer command without a
         -r option

* NEW: If the consumer in use supports it, pass down to it the 'label' option from the command line


# Ver 0.4

* NEW: When tagging a service as Event Listener, it is possible to specify that it will only be triggered for a specific
       queue

* NEW: A new service is made available, commented out, in services.yml, which can be used to requeue console-command
       messages when their execution fails. This can be use as a substitute for the NACK feature of some queueing systems,
       which is not supported by the library

* CHANGED: The interfaces ConsumerInterface and MessageInterface have acquired a new method each.
           If you have implemented a driver you will need to adapt your code.

* FIXED: the `Monitor` event listener does not dump twice any more the received messages nor strip html tags


# Ver 0.3

* FIXED: do not crash the consumer if the received message is invalid json (or other expected encoding)

* NEW: introduced a new Event: `MessageConsumptionFailed` (triggered when message processing raises an Exception)

* NEW: the ConsoleCommand producer gained a batchPublish() method

* NEW: a new *EXPERIMENTAL* consumer is available for executing Console-Command messages. It is registered as service
       `kaliop_queueing.message_consumer.inprocess_console_command`
       The difference with the standard `kaliop_queueing.message_consumer.console_command` consumer is that this one does
       not fork a new php process to execute the received commands.
       This has the effect of making it:
       - fast
       - prone to memory leaks
       - prone to resource leaks
       - sensitive to problems with long-lived database connections
       - prone to problems with fatal errors (unless you are on php 7 and you catch them all as exceptions)


# Ver 0.2

* NEW: introduced a new Event: MessageConsumed (triggered after message processing)
       The Monitor event listener can be tagged to listen to this event and log debug information

* NEW: the Publisher classes now implement a BatchPublish method for optimized sending of multiple messages

* NEW: the Consume method of Consumer classes now accepts a $timeout optional parameter.
       This is also true of the kaliop_queueing:consumer console command

* NEW: introduced fluent interfaces for all setter methods

* NEW: all MessageConsumer classes now return a value from their consume() method

* NEW: added an interface for MessageProducer classes

* NEW: the QueueManager classes (and console command) now take optional parameters for all actions.
       The exact parameters depend on the driver+action combination

* NEW: added a new service which can be used as MessageConsumed listener to help testing: kaliop_queueing.message_consumer.filter.accumulator

* NEW: introduced protection against recursion for MessageConsumer::decodeAndConsume

* CHANGED: the ConsumerInterface now sports a method setCallback()

* CHANGED: changed the MessageReceived event to simplify it a bit

* CHANGED: cli commands use '-i' to specify the driver to use instead of '-b'

* CHANGED: cli command kaliop_queueing:managequeue uses '-o option=value' to specify options for the remote command

* CHANGED: cli command `kaliop_queueing:managequeue list` has been renamed `kaliop_queueing:managequeue list-configured`
           to avoid confusion between configured bundle queues and queues/exchanges existing on the broker.
           It now works in prod environments and not only in dev

* FIXED: RabbitMQ Consumers can not change the routing key associated with their queue. The bundle now throws an exception
         if this is attempted


# Ver 0.1

* first release announced to the world
