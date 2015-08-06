<?php

namespace Kaliop\QueueingBundle\Services;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class used to manage a series of "worker processes".
 * Each worker is a php process running a rabbitmq consumer (as symfony console command).
 * The list of workers is defined using configuration parameters.
 *
 * @todo rip away the parts which are specific to eZPublish
 *
 * @todo define an interface for the part of this code which deals with getConsoleCommand() - maybe split it off altogether
 */
class WorkerManager
{
    protected $configResolver;
    protected $kernelRootDir;
    protected static $paramName = 'workers.list';
    protected static $paramScope = 'kaliop_queueing';

    public function __construct( ConfigResolverInterface $configResolver, $kernelRootDir )
    {
        $this->configResolver = $configResolver;
        $this->kernelRootDir = $kernelRootDir;
    }

    /**
     * Returns the list of commands for all workers configured to be running as daemons
     *
     * @param string $serverName
     * @return array key: worker name, value: command to execute to start the worker
     * @throws \Exception
     */
    public function getWorkersCommands( $groupName = 'default', $env = null )
    {

        $procs = array();
        foreach( $this->getWorkersNames( $groupName ) as $name )
        {
            $procs[$name] = $this->getWorkerCommand( $name, $groupName, $env );
        }
        return $procs;
    }

    /**
     * Returns the list of workers groups available
     */
    public function getWorkersGroups()
    {
        return array_keys( $this->configResolver->getParameter( self::$paramName, self::$paramScope ) );
    }

    /**
     * Returns the list of workers configured to be running as daemons
     */
    public function getWorkersNames( $groupName ='default' )
    {
        return array_keys( $this->configResolver->getParameter( self::$paramName . '.' . $groupName, self::$paramScope ) );
    }

    /**
     * Returns the command line of a workers configured to be running as daemon
     *
     * @param string $name
     * @param bool $unescaped do not add shell escaping to the command. NB: never use this option for executing stuff, only for grepping
     * @param string $env set it to non null to force execution using a specific environment
     * @return string
     * @throws \Exception
     *
     * @todo make queue_name optional: take it from $name if not set (it is only used to allow many workers for the same queue)
     * @todo allow to take path to php binary from config
     * @todo filter out any undesirable cli options from the ones given by the user
     * @todo tighten security: option names should be checked against the valid ones in the rabbitmq:consumer process
     * @todo get the name of the console command from himself
     */

    public function getWorkerCommand( $name, $groupName ='default', $env=null, $unescaped=false )
    {
        $defs = $this->configResolver->getParameter( self::$paramName . '.' . $groupName, self::$paramScope );
        if ( !isset( $defs[$name] ) )
        {
            throw new \Exception( "No worker configuration for $name" );
        }
        $workerDef = $defs[$name];
        if ( empty( $workerDef['queue_name'] ) || ( isset( $workerDef['options'] ) && !is_array( $workerDef['options'] ) ) )
        {
            throw new \Exception( "Bad worker configuration for $name" );
        }
        $cmd = $this->getConsoleCommand( $env, $unescaped ) .
            " kaliop_queueing:consumer " . ( $unescaped ? $workerDef['queue_name'] : escapeshellarg( $workerDef['queue_name'] ) ) .
            " --label=" . ( $unescaped ? $name : escapeshellarg( $name ) ) . " -w";
        if ( isset( $workerDef['options'] ) )
        {
            foreach( $workerDef['options'] as $name => $value )
            {
                $cmd .= ' ' . ( strlen( $name ) == 1 ? '-' : '--' ) . $name . '=' . ( $unescaped ? $value : escapeshellarg( $value ) );
            }
        }
        return $cmd;
    }

    /**
     * Generates the php command to run the sf console
     *
     * @param string $env when not null an environment is set for the cmd to execute
     * @return string
     * @throws \RuntimeException
     *
     * @todo should get the name of the 'console' file in some kind of flexible way as well?
     */
    public function getConsoleCommand( $env = null, $unescaped=false )
    {
        $phpFinder = new PhpExecutableFinder;
        if ( !$php = $phpFinder->find() )
        {
            throw new \RuntimeException( 'The php executable could not be found, add it to your PATH environment variable and try again' );
        }

        $out = $php . ' ' . escapeshellcmd( $this->kernelRootDir . DIRECTORY_SEPARATOR . "console" );
        if (  $env != '' )
        {
            $out .=  ' --env=' . ( $unescaped ? $env : escapeshellarg( $env ) );
        }
        return $out;
    }
}