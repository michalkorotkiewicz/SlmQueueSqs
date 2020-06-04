<?php

namespace SlmQueueSqsTest\Worker;

use Aws\Sqs\Exception\SqsException;
use PHPUnit\Framework\TestCase;
use SlmQueue\Worker\Event\AbstractWorkerEvent;
use SlmQueue\Worker\Event\ProcessJobEvent;
use SlmQueue\Worker\WorkerEvent;
use SlmQueueSqs\Worker\SqsWorker;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Job\JobInterface;
use Laminas\EventManager\EventManagerInterface;
use SlmQueueSqs\Queue\SqsQueueInterface;
use Aws\CommandInterface;

class SqsWorkerTest extends TestCase
{
    /**
     * @var SqsWorker
     */
    protected $worker;

    public function setUp(): void
    {
        $this->worker = new SqsWorker($this->getMockBuilder(EventManagerInterface::class)->getMock());
    }

    public function testReturnsUnknownIfNotASqsQueue()
    {
        $queue = $this->getMockBuilder(QueueInterface::class)->getMock();
        $job   = $this->getMockBuilder(JobInterface::class)->getMock();

        $this->assertEquals(ProcessJobEvent::JOB_STATUS_UNKNOWN, $this->worker->processJob($job, $queue));
    }

    public function testDeleteJobOnSuccess()
    {
        $queue = $this->getMockBuilder(SqsQueueInterface::class)->getMock();
        $job   = $this->getMockBuilder(JobInterface::class)->getMock();

        $job->expects($this->once())->method('execute');
        $queue->expects($this->once())->method('delete')->with($job);

        $status = $this->worker->processJob($job, $queue);

        $this->assertEquals(ProcessJobEvent::JOB_STATUS_SUCCESS, $status);
    }

    public function testDoNotDeleteJobOnFailure()
    {
        $queue = $this->getMockBuilder(SqsQueueInterface::class)->getMock();
        $job   = $this->getMockBuilder(JobInterface::class)->getMock();

        $job->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new \RuntimeException()));

        $queue->expects($this->never())->method('delete');

        $status = $this->worker->processJob($job, $queue);

        $this->assertEquals(ProcessJobEvent::JOB_STATUS_FAILURE_RECOVERABLE, $status);
    }

    public function testRethrowSqsException()
    {
        $this->expectException(SqsException::class);

        $queue = $this->getMockBuilder(SqsQueueInterface::class)->getMock();
        $job   = $this->getMockBuilder(JobInterface::class)->getMock();
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();

        $job->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new SqsException('Foo', $command)));

        $queue->expects($this->never())->method('delete');

        $this->worker->processJob($job, $queue);
    }
}
