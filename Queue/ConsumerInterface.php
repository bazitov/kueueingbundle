<?php

namespace Kaliop\QueueingBundle\Queue;

/**
 * Modeled after the RabbitMqBundle consumer, so that it can be used by the consumer console command
 */
interface ConsumerInterface
{
    /**
     * @param int $limit MB
     * @return $this
     */
    public function setMemoryLimit($limit);

    /**
     * @param string $key
     * @return $this
     */
    public function setRoutingKey($key);

    /**
     * @param mixed $callback depending on the consumer, it might be a Callable, a MessageConsumerInterface or a
     * @return $this
     */
    public function setCallback($callback);

    /**
     * This method will be called by the driver, using the 'logical' name of the queue (not the driver specific one).
     * In turn, the consumer should inject the queueName into the Messages it builds
     * @param string $queueName
     * @return $this
     */
    public function setQueueName($queueName);

    /**
     * If $amount is null and $timeout is 0, loop forever
     *
     * @param int $amount maximum number of consumed messages after which to exit the loop
     * @param int $timeout maximum number seconds after which to exit the loop.
     *                     NB: this includes all time including both processing and network communication, and is a total,
     *                     it is not reset on message reception.
     *                     NB: do not trust this timing to be precise: since php is not multi-threaded, the timeout can
     *                     not be enforced; if a MessageConsumer takes a huge amount of time to consume a single message,
     *                     or if the underlying networking library of a driver does the same, the consume() call will
     *                     still wait for them to finish before returning
     * @return mixed
     */
    public function consume($amount, $timeout=0);
}
