<?php

App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('CakeResqueShell', 'CakeResque.Console/Command');

class CakeResqueShellTest extends CakeTestCase
{

	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->CakeResque = $this->getMockClass(
			'CakeResque',
			array('enqueue', 'enqueueIn', 'enqueueAt', 'getJobStatus', 'getFailedJobLog', 'getWorkers')
		);

		$this->Shell = $this->getMock(
			'CakeResqueShell',
			array('in', 'out', 'hr'),
			array($out, $out, $in)
		);
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Dispatch, $this->Shell);
	}

/**
 * @covers CakeResqueShell::track
 */
	public function testTrackingWithNoJobIdReturnError() {
		$this->Shell->expects($this->exactly(2))->method('out');

		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;

		$CakeResque::staticExpects($this->never())->method('getJobStatus');
		$CakeResque::staticExpects($this->never())->method('getFailedJobLog');

		$this->Shell->expects($this->at(0))->method('out')->with($this->stringContains('Tracking job status'));

		$this->Shell->expects($this->at(1))->method('out')->with($this->stringContains('error'));
		$this->Shell->track();
	}

/**
 * @covers CakeResqueShell::track
 */
	public function testTrackingJobWithUnknownStatus() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;

		$CakeResque::staticExpects($this->once())
			->method('getJobStatus')
			->will($this->returnValue(false));

		$CakeResque::staticExpects($this->never())->method('getFailedJobLog');

		$this->Shell->args = array('dd');
		$this->Shell->expects($this->at(1))->method('out')->with($this->stringContains('Status'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/unknown/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/warning/'));
		$this->Shell->track();
	}

/**
 * @covers CakeResqueShell::track
 */
	public function testTrackingCompletedJob() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;

		$CakeResque::staticExpects($this->once())
			->method('getJobStatus')
			->will($this->returnValue(Resque_Job_Status::STATUS_COMPLETE));

		$CakeResque::staticExpects($this->never())->method('getFailedJobLog');

		$this->Shell->args = array('dd');
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/complete/'));
		$this->Shell->track();
	}

/**
 * @covers CakeResqueShell::track
 */
	public function testTrackingRunningJob() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;

		$CakeResque::staticExpects($this->once())
			->method('getJobStatus')
			->will($this->returnValue(Resque_Job_Status::STATUS_RUNNING));

		$CakeResque::staticExpects($this->never())->method('getFailedJobLog');

		$this->Shell->args = array('dd');
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/running/'));
		$this->Shell->track();
	}

/**
 * @covers CakeResqueShell::track
 */
	public function testTrackingWaitingJob() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;

		$CakeResque::staticExpects($this->once())
			->method('getJobStatus')
			->will($this->returnValue(Resque_Job_Status::STATUS_WAITING));

		$CakeResque::staticExpects($this->never())->method('getFailedJobLog');

		$this->Shell->args = array('dd');
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/waiting/'));
		$this->Shell->track();
	}

/**
 * @covers CakeResqueShell::track
 */
	public function testTrackingFailedJobWithEmptyLog() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$CakeResque::staticExpects($this->once())
			->method('getJobStatus')
			->will($this->returnValue(Resque_Job_Status::STATUS_FAILED));

		$CakeResque::staticExpects($this->once())
			->method('getFailedJobLog')
			->will($this->returnValue(array()));

		$this->Shell->args = array('dd');
		$this->Shell->expects($this->exactly(3))->method('out');
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/failed/'));
		$this->Shell->track();
	}

	public function testTrackingFailedJobWithStringLog() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$CakeResque::staticExpects($this->once())
			->method('getJobStatus')
			->will($this->returnValue(Resque_Job_Status::STATUS_FAILED));

		$CakeResque::staticExpects($this->once())
			->method('getFailedJobLog')
			->will($this->returnValue(array("log++")));

		$this->Shell->args = array('dd');
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/failed/'));
		$this->Shell->expects($this->at(3))->method('out')->with($this->matchesRegularExpression('/details/'));
		$this->Shell->expects($this->at(6))->method('out')->with($this->matchesRegularExpression('/log/'));
		$this->Shell->track();
	}

/**
 * @covers CakeResqueShell::track
 */
	public function testTrackingFailedJobWithArrayLog() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$CakeResque::staticExpects($this->once())
			->method('getJobStatus')
			->will($this->returnValue(Resque_Job_Status::STATUS_FAILED));

		$CakeResque::staticExpects($this->once())
			->method('getFailedJobLog')
			->will($this->returnValue(array("key" => "name")));

		$this->Shell->args = array('dd');
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/failed/'));
		$this->Shell->expects($this->at(3))->method('out')->with($this->matchesRegularExpression('/details/'));
		$this->Shell->expects($this->at(5))->method('out')->with($this->matchesRegularExpression('/key/i'));
		$this->Shell->expects($this->at(6))->method('out')->with($this->matchesRegularExpression('/name/'));
		$this->Shell->track();
	}

/**
 * @covers CakeResqueShell::enqueue
 */
	public function testEnqueueJobWithoutArguments() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;

		$CakeResque::staticExpects($this->never())->method('enqueue');

		$this->Shell->expects($this->exactly(2))->method('out');
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/adding/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/usage/i'));
		$this->Shell->enqueue();
	}

/**
 * @covers CakeResqueShell::enqueue
 */
	public function testEnqueueJobWithWrongNumberOfArguments() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$this->args = array('queue', 'class');

		$CakeResque::staticExpects($this->never())->method('enqueue');

		$this->Shell->expects($this->exactly(2))->method('out');
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/adding/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/usage/i'));
		$this->Shell->enqueue();
	}

/**
 * @covers CakeResqueShell::enqueue
 */
	public function testEnqueueJob() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$this->Shell->args = array('queue', 'class', 'args');

		$id = md5(time());

		$CakeResque::staticExpects($this->once())->method('enqueue')->will($this->returnValue($id));

		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/adding/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/succesfully/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/' . $id . '/i'));
		$this->Shell->enqueue();
	}

/**
 * @covers CakeResqueShell::enqueueIn
 */
	public function testEnqueueInJobWithWrongNumberOfArguments() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$this->args = array('queue', 'class');

		$CakeResque::staticExpects($this->never())->method('enqueueIn');

		$this->Shell->expects($this->exactly(2))->method('out');
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/scheduling/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/usage/i'));
		$this->Shell->enqueueIn();
	}

/**
 * @covers CakeResqueShell::enqueueIn
 */
	public function testEnqueueInJob() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$this->Shell->args = array(0, 'queue', 'class', 'args');

		$id = md5(time());

		$CakeResque::staticExpects($this->once())->method('enqueueIn')->will($this->returnValue($id));

		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/scheduling/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/succesfully/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/' . $id . '/i'));
		$this->Shell->enqueueIn();
	}

/**
 * @covers CakeResqueShell::enqueueAt
 */
	public function testEnqueueAtJobWithWrongNumberOfArguments() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$this->args = array('queue', 'class');

		$CakeResque::staticExpects($this->never())->method('enqueueAt');

		$this->Shell->expects($this->exactly(2))->method('out');
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/scheduling/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/usage/i'));
		$this->Shell->enqueueAt();
	}

/**
 * @covers CakeResqueShell::enqueueAt
 */
	public function testEnqueueAtJob() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		$this->Shell->args = array(0, 'queue', 'class', 'args');

		$id = md5(time());

		$CakeResque::staticExpects($this->once())->method('enqueueAt')->will($this->returnValue($id));

		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/scheduling/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/succesfully/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->matchesRegularExpression('/' . $id . '/i'));
		$this->Shell->enqueueAt();
	}

/**
 * @covers CakeResqueShell::pause
 */
	public function testPauseWorkerWhenThereIsNoWorkers() {
		$shell = $this->Shell;

		$shell::$cakeResque = $CakeResque = $this->CakeResque;

		$CakeResque::staticExpects($this->once())->method('getWorkers')->will($this->returnValue(array()));

		$this->Shell->expects($this->exactly(3))->method('out');
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/pausing/i'));
		$this->Shell->expects($this->at(1))->method('out')->with($this->stringContains('There is no active workers to pause'));

		$this->Shell->pause();
	}

/**
 * @covers CakeResqueShell::pause
 */
	public function testPauseWorkerWhenThereIsOnlyOneWorkers() {
		$shell = $this->Shell;
		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		//$CakeResque::staticExpects($this->once())->method('getWorkers')->will($this->returnValue(array()));
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/pausing/i'));
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

/**
 * @covers CakeResqueShell::pause
 */
	public function testPauseWorkerWhenThereIsMultipleWorkers() {
		$shell = $this->Shell;
		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		//$CakeResque::staticExpects($this->once())->method('getWorkers')->will($this->returnValue(array()));
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/pausing/i'));
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

/**
 * @covers CakeResqueShell::pause
 */
	public function testPauseWorkerWhenThereIsAlreadySomePausedWorkers() {
		$shell = $this->Shell;
		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		//$CakeResque::staticExpects($this->once())->method('getWorkers')->will($this->returnValue(array()));
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/pausing/i'));
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

/**
 * @covers CakeResqueShell::pause
 */
	public function testPauseWorkerAllAtOnce() {
		$shell = $this->Shell;
		$shell::$cakeResque = $CakeResque = $this->CakeResque;
		//$CakeResque::staticExpects($this->once())->method('getWorkers')->will($this->returnValue(array()));
		$this->Shell->expects($this->at(0))->method('out')->with($this->matchesRegularExpression('/pausing/i'));
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

}