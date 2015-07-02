<?php

/* Copyright (C) 2015, Sport Archive Inc. */

/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or */
/* (at your option) any later version. */

/* This program is distributed in the hope that it will be useful, */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the */
/* GNU General Public License for more details. */

/* You should have received a copy of the GNU General Public License along */
/* with this program; if not, write to the Free Software Foundation, Inc., */
/* 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. */

/* Cloud Processing Engine, Copyright (C) 2015, Sport Archive Inc */
/* Cloud Processing Engine comes with ABSOLUTELY NO WARRANTY; */
/* This is free software, and you are welcome to redistribute it */
/* under certain conditions; */

/* June 29th 2015 */
/* Sport Archive Inc. */
/* info@sportarchive.tv */



/**
 * This script listen to AWS SQS queues for incoming input commands
 * It opens the JSON input and starts a execute a callback corresponding to the command
 */

require __DIR__ . "/../vendor/autoload.php";

use Aws\Swf\Exception;
use SA\CpeSdk;

class InputPoller
{
    private $debug;
    private $config;
    private $cpeSqsListener;
    private $cpeSwfHandler;
    private $cpeLogger;
    
    const INVALID_JSON = "INVALID_JSON"; 
    
    function __construct($config)
    {
        global $debug;
        global $cpeLogger;
        
        $this->config    = $config;
        $this->debug     = $debug;
        $this->cpeLogger = $cpeLogger;
        
        // Mapping event/methods
        // Events come from clients using the CpeClientSDK
        $this->typesMap = [
            'START_JOB'            => 'start_job',
            'CANCEL_JOB'           => 'cancel_job',
            'CANCEL_ACTIVITY'      => 'cancel_activity',
            'GET_JOB_LIST'         => 'get_job_list',
            'GET_ACTIVITY_LIST'    => 'get_activity_list',
            'GET_JOB_STATUS'       => 'get_job_status',
            'GET_ACTIVITY_STATUS'  => 'get_activity_status',
        ];
        
        // For listening to the Input SQS queue
        $this->cpeSqsListener = new CpeSdk\Sqs\CpeSqsListener($this->debug);

        // For creating SWF object 
        $this->cpeSwfHandler  = new CpeSdk\Swf\CpeSwfHandler($this->debug);
    }
    
    // Poll from the 'input' SQS queue of all clients
    // If a msg is received, we pass it to 'handle_input' for processing
    public function poll_SQS_queues()
    {
        // For all clients in config files
        // We poll from queues
        foreach ($this->config->{'clients'} as $client)
        {
            // Long Polling messages from client input queue
            $queue = $client->{'queues'}->{'input'};
            try {
                if ($msg = $this->cpeSqsListener->receive_message($queue, 10))
                {
                    if (!($decoded = json_decode($msg['Body'])))
                        $this->cpeLogger->log_out(
                            "ERROR", 
                            basename(__FILE__), 
                            "JSON data invalid in queue: '$queue'");
                    else                    
                        $this->handle_message($decoded, $client);
                }
            } catch (CpeSdk\CpeException $e) {
                $this->cpeLogger->log_out(
                    "ERROR", 
                    basename(__FILE__), 
                    $e->getMessage().print_r($msg, true));
            }
            
            // Message polled. Valid or not, we delete it from SQS
            if ($msg)
                $this->cpeSqsListener->delete_message($queue, $msg);
        }
    }

    // Receive an input, check if we know the command and exec the callback
    public function handle_message($message, $client)
    {
        $this->validate_message($message);

        // Do we know this input ?
        if (!isset($this->typesMap[$message->{"type"}]))
        {
            $this->cpeLogger->log_out(
                "ERROR", 
                basename(__FILE__), 
                "Command '" . $message->{"type"} . "' is unknown! Ignoring ..."
            );
            return;
        }

        $this->cpeLogger->log_out(
            "INFO", 
            basename(__FILE__), 
            "Received message '" . $message->{"type"}  . "'"
        );
        if ($this->debug)
            $this->cpeLogger->log_out(
                "DEBUG", 
                basename(__FILE__), 
                "Details:\n" . json_encode($message, JSON_PRETTY_PRINT)
            );

        // We call the callback function that handles this message  
        $this->{$this->typesMap[$message->{"type"}]}($message, $client);
    }

    
    /** 
     * CALLBACKS
     */

    // Start a new workflow in SWF to initiate new transcoding job
    private function start_job($message, $client)
    {
        if ($this->debug)
            $this->cpeLogger->log_out(
                "DEBUG",
                basename(__FILE__),
                "Starting new workflow!"
            );
        
        // Workflow info
        $workflowType = array(
            "name"    => $message->{'data'}->{'workflow'}->{'name'},
            "version" => $message->{'data'}->{'workflow'}->{'version'});
        
        // Append client info to message data
        $message->{"client"} = $client;

        // Request start SWF workflow
        try {
            $workflowRunId = $this->cpeSwfHandler->swf->startWorkflowExecution(array(
                    "domain"       => $message->{'data'}->{'workflow'}->{'domain'},
                    "workflowId"   => uniqid('', true),
                    "workflowType" => $workflowType,
                    "taskList"     => array("name" => $message->{'data'}->{'workflow'}->{'taskList'}),
                    "input"        => json_encode($message)
                ));

            $this->cpeLogger->log_out(
                "INFO",
                basename(__FILE__),
                "New workflow submitted to SWF: ".$workflowRunId->get('runId'));
        } catch (\Aws\Swf\Exception\SwfException $e) {
            $this->cpeLogger->log_out(
                "ERROR",
                basename(__FILE__),
                "Unable to start workflow!"
                . $e->getMessage());
        }
    }

    /**
     * UTILS
     */ 

    private function validate_message($message)
    {
        if (!isset($message) || 
            !isset($message->{"time"})   || $message->{"time"} == "" || 
            !isset($message->{"job_id"}) || $message->{"job_id"} == "" || 
            !isset($message->{"type"})   || $message->{"type"} == "" || 
            !isset($message->{"data"})   || $message->{"data"} == "")
            throw new CpeSdk\CpeException("'time', 'type', 'job_id' or 'data' fields missing in JSON message file!",
                self::INVALID_JSON);
        
        if (!isset($message->{'data'}->{'workflow'}))
            throw new CpeSdk\Cpexception("Input doesn't contain any workflow information. You must provide the workflow you want to sent this job to!",
                self::INVALID_JSON);
    }
}


/**
 * INPUT POLLER START
 */

$debug = false;
$cpeLogger = new CpeSdk\CpeLogger();

function usage($defaultConfigFile)
{
    echo("Usage: php ". basename(__FILE__) . " [-h] -c <config_path>\n");
    echo("-h: Print this help\n");
    echo("-c <config_path>: Path to the config file.\n");
    exit(0);
}

function check_input_parameters()
{
    global $debug;
    global $cpeLogger;
    
    // Handle input parameters
    if (!($options = getopt("c:hd")))
        usage();
    
    if (isset($options['h']))
        usage();
    
    if (isset($options['d']))
        $debug = true;
    
    if (isset($options['c']))
    {
        $cpeLogger->log_out(
            "INFO", 
            basename(__FILE__), 
            "Config file: '" . $options['c'] . "'"
        );
        $configFile = $options['c'];
    }
    
    if (!($config = json_decode(file_get_contents($configFile))))
    {
        $cpeLogger->log_out(
            "FATAL", 
            basename(__FILE__), 
            "Configuration file '$configFile' invalid!"
        );
        exit(1);
    }

    # Validate against JSON Schemas
    # if (($err = validate_json($config, "config/mainConfig.json")))
    # exit("JSON main configuration file invalid! Details:\n".$err);

    return $config;
}

// Get config file
$config = check_input_parameters();
$cpeLogger->log_out("INFO", basename(__FILE__), $config->{'clients'});

// Create InputPoller object
try {
    $inputPoller = new InputPoller($config);
} 
catch (CpeSdk\CpeException $e) {
    $cpeLogger->log_out(
        "FATAL", 
        basename(__FILE__), 
        $e->getMessage()
    );
    exit(1);
}

// Start polling loop to get incoming commands from SQS input queues
while (42)
    $inputPoller->poll_SQS_queues();
