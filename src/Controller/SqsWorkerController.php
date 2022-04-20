<?php

namespace SlmQueueSqs\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use SlmQueue\Controller\Exception\WorkerProcessException;
use SlmQueue\Exception\ExceptionInterface;
use SlmQueue\Queue\QueuePluginManager;
use SlmQueue\Worker\WorkerInterface;

/**
 * This controller allow to execute jobs using the command line
 */
class SqsWorkerController extends AbstractActionController
{
    /**
     * @var WorkerInterface
     */
    protected $worker;

    /**
     * @var QueuePluginManager
     */
    protected $queuePluginManager;

    /**
     * @param WorkerInterface    $worker
     * @param QueuePluginManager $queuePluginManager
     */
    public function __construct(WorkerInterface $worker, QueuePluginManager $queuePluginManager)
    {
        $this->worker = $worker;
        $this->queuePluginManager = $queuePluginManager;
    }

    /**
     * Process a queue
     *
     * @return string
     * @throws WorkerProcessException
     */
    public function processAction(): string
    {
        $params = $this->params()->fromRoute();

        $options = array(
            'queue'              => $params['queue'],
            'visibility_timeout' => isset($params['visibilityTimeout']) ? $params['visibilityTimeout'] : null,
            'wait_time_seconds'  => isset($params['waitTime']) ? $params['waitTime'] : null
        );

        $queue = $this->queuePluginManager->get($options['queue']);

        try {
            $messages = $this->worker->processQueue($queue, array_filter($options));
        } catch (ExceptionInterface $e) {
            throw new WorkerProcessException('Caught exception while processing queue', $e->getCode(), $e);
        }

        return $this->formatOutput($options['queue'], $messages);
    }

    protected function formatOutput(string $queueName, array $messages = []): string
    {
        $messages = implode("\n", array_map(function (string $message): string {
            return sprintf(' - %s', $message);
        }, $messages));

        return sprintf(
            "Finished worker for queue '%s':\n%s\n",
            $queueName,
            $messages
        );
    }
}
