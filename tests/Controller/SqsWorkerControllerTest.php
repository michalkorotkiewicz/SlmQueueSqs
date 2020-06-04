<?php

namespace SlmQueueSqsTest\Controller;

use PHPUnit\Framework\TestCase;
use SlmQueueSqs\Controller\SqsWorkerController;
use Laminas\Mvc\Router\RouteMatch;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\WorkerInterface;

class SqsWorkerControllerTest extends TestCase
{
    public function testCorrectlyCountJobs()
    {
        $queue         = $this->createMock(QueueInterface::class);
        $worker        = $this->createMock(WorkerInterface::class);
        $pluginManager = $this->createMock('SlmQueue\Queue\QueuePluginManager', array(), array(), '', false);

        $pluginManager->expects($this->once())
                      ->method('get')
                      ->with('newsletter')
                      ->will($this->returnValue($queue));

        $controller    = new SqsWorkerController($worker, $pluginManager);

        $routeMatch = new RouteMatch(array('queue' => 'newsletter'));
        $controller->getEvent()->setRouteMatch($routeMatch);

        $worker->expects($this->once())
               ->method('processQueue')
               ->with($queue)
               ->willReturn(array('One state'));

        $result = $controller->processAction();

        $this->assertStringEndsWith("One state\n", $result);
    }
}
