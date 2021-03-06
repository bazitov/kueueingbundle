<?php

namespace Kaliop\QueueingBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Kaliop\QueueingBundle\Helper\BaseCommand;

class ManageQueueCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:managequeue')
            ->setDescription("Sends control commands to a queue to f.e. purge it or grab some stats")
            ->addArgument('action', InputArgument::REQUIRED, 'The action to execute. use "help" to see all available')
            ->addArgument('queue_name', InputArgument::OPTIONAL, 'The queue name (string)', '')
            ->addOption('argument', 'a', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The arguments (varies on the action/driver combination), use name=value syntax', array())
            ->addOption('driver', 'i', InputOption::VALUE_REQUIRED, 'The driver (string), if not default', null)
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);

        $driverName = $input->getOption('driver');
        $command = $input->getArgument('action');
        $arguments = $input->getOption('argument');
        $queue = $input->getArgument('queue_name');
        $debug = $input->getOption('debug');

        // note: this parsing does not work with positional args - it assumes named ones
        $parsedArguments = array();
        foreach ($arguments as $key => $arg) {
            $arg = explode('=', $arg, 2);
            $parsedArguments[$arg[0]] = ((count($arg) == 2) ? $arg[1] : null);
        }

        $driver = $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver($driverName);
        if ($debug !== null) {
            $driver->setDebug($debug);
        }
        $queueManager = $driver->getQueueManager($queue);

        if ($command == 'help') {
            $this->writeln("Available actions: " . implode(', ', $queueManager->listActions($queue)));
            return;
        }

        if (!in_array($command, $queueManager->listActions($queue))) {
            $this->writeln("Unrecognized action $command\nAvailable actions: " . implode(', ', $queueManager->listActions($queue)));
            return;
        }

        $queueManager->setQueueName($queue);
        $result = $queueManager->executeAction($command, $parsedArguments);

        $this->writeln("Sent '$command' to queue $queue");

        if ($result != '') {
            if (is_array($result)) {
                $result = print_r($result, true);
            }
            $this->writeln("Result: $result");
        }
    }
}