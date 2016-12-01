#!/usr/bin/php

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

require_once __DIR__ . "/../vendor/autoload.php";

use SA\CpeSdk;

class InputPoller
{
    private $debug;
    private $config;
    private $cpeSqsListener;
    private $cpeSqsWriter;
    private $cpeSwfHandler;
    private $cpeLogger;
    private $typesMap;
    
    const INVALID_JSON   = "INVALID_JSON"; 
    const INVALID_CONFIG = "INVALID_CONFIG";
    
    public function __construct($config)
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
        $this->cpeSqsListener = new CpeSdk\Sqs\CpeSqsListener($this->debug, $cpeLogger);
        // For writing to SQS queue
        $this->cpeSqsWriter   = new CpeSdk\Sqs\CpeSqsWriter($this->debug, $cpeLogger);

        // For creating SWF object 
        $this->cpeSwfHandler  = new CpeSdk\Swf\CpeSwfHandler($this->debug, $cpeLogger);
    }
    
    // Poll from the 'input' SQS queue of all clients
    // If a msg is received, we pass it to 'handle_input' for processing
    public function poll_SQS_queues()
    {
        if (!isset($this->config->{'clients'}))
            throw new CpeSdk\CpeException("Clients configuration invalid. Check the config file or your parameters.",
                self::INVALID_CONFIG);
        
        // For all clients in config files
        // We poll from queues
        foreach ($this->config->{'clients'} as $client)
        {
            $msg = null;
            $this->cpeLogger->log_out("DEBUG", __DIR__, "Polling from client: " . print_r($client, true));
            
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
                        $this->handle_message($decoded);
                }
            } catch (CpeSdk\CpeException $e) {
                $this->cpeLogger->log_out(
                    "ERROR", 
                    basename(__FILE__), 
                    $e->getMessage().print_r($msg, true));
            } catch (\Exception $e) {
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
    public function handle_message($message)
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
        $this->{$this->typesMap[$message->{"type"}]}($message);
    }

    
    /** 
     * CALLBACKS
     */

    // Start a new workflow in SWF to initiate new transcoding job
    private function start_job($message)
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
        
        // Request start SWF workflow
        // We only pass $message->{'data'} as input for the WF
        // $message->{'data'}->{'workflow'}->{'domain'} MUST be contained in the JSON input
        try {
            $workflowId = uniqid('', true);
            $payload = array(
                "domain"       => $message->{'data'}->{'workflow'}->{'domain'},
                "workflowId"   => $workflowId,
                "workflowType" => $workflowType,
                "taskList"     => array("name" => $message->{'data'}->{'workflow'}->{'taskList'}),
                "input"        => json_encode($message->{'data'})
            );
            if (isset($message->{'data'}->{'workflow'}->{'executionStartToCloseTimeout'})) 
                $payload["executionStartToCloseTimeout"] =
                    $message->{'data'}->{'workflow'}->{'executionStartToCloseTimeout'};
            $workflowRunId = $this->cpeSwfHandler->swf->startWorkflowExecution($payload);

            $this->cpeLogger->log_out(
                "INFO",
                basename(__FILE__),
                "New workflow submitted to SWF: ".$workflowRunId->get('runId'));

            // Send WORKFLOW_SCHEDULED message back to client
            $this->cpeSqsWriter->workflow_scheduled($workflowType, $workflowRunId->get('runId'), $workflowId, $message);
                
        } catch (\Exception $e) {
            $this->cpeLogger->log_out(
                "ERROR", 
                basename(__FILE__), 
                $e->getMessage());
        }
    }

    /**
     * UTILS
     */ 

    private function validate_message($message)
    {
        if (!isset($message) || 
            !isset($message->{"time"})   || $message->{"time"} == "" || 
            !isset($message->{"jobId"})  || $message->{"jobId"} == "" || 
            !isset($message->{"type"})   || $message->{"type"} == "" || 
            !isset($message->{"data"})   || $message->{"data"} == "")
            throw new CpeSdk\CpeException("'time', 'type', 'jobId' or 'data' fields missing in JSON message file!",
                self::INVALID_JSON);
        
        if (!isset($message->{'data'}->{'workflow'}))
            throw new CpeSdk\CpeException("Input doesn't contain any workflow information. You must provide the workflow you want to send this job to!",
                self::INVALID_JSON);
    }
}


/**
 * INPUT POLLER START
 */

$debug = false;
$cpeLogger;

function usage($defaultConfigFile)
{
    echo("# Description\nThe InputPoller connects your client applications to your stack. It handles communication over the two SQS channels you created for your client application.\n\n");
    echo("Usage: php ". basename(__FILE__) . " [-h] [-d] -n <client_name> [-l <log path>]\n");
    echo("-h: Print this help\n");
    echo("-d: Debug mode\n");
    echo("-l <log_path>: Location where logs will be dumped in (folder).\n");
    echo("-n <client_name> [mandatory]: The name of the client application that will use this Poller to communicate with the stack. The client will be sending and listenening to the Stack through SQS. We expect the INPUT_QUEUE and OUTPUT_QUEUE environment variables set to the URL of the SQS queues.\n");
    exit(0);
}

function check_input_parameters()
{
    global $debug;
    global $cpeLogger;
    
    // Handle input parameters
    $options = getopt("l:hdn:");

    if (isset($options['h']))
        usage();
    
    if (isset($options['d'])) 
        $debug = true;
        
    $logPath = null;
    if (isset($options['l']))
    {
        $logPath = $options['l'];
    }
    
    if (!isset($options['n']))
    {
        print "[ERROR] You must provide a [-n client_name] parameter to provide your client application name.\n";
        exit(1);
    }
    
    $cpeLogger = new CpeSdk\CpeLogger($logPath, $options['n'], $debug);
        
    $config = new \stdClass;
    $config->clients = [(object)[
            'name' => $options['n'],
            'queues' => (object)[
                'input' => getenv('INPUT_QUEUE'),
                'output' => getenv('OUTPUT_QUEUE'),
            ],
        ]];

    # Validate against JSON Schemas
    # if (($err = validate_json($config, "config/mainConfig.json")))
    # exit("JSON main configuration file invalid! Details:\n".$err);

    return $config;
}

$config = check_input_parameters();
$cpeLogger->log_out("INFO", basename(__FILE__), $config);

// Create InputPoller object
try {
    $inputPoller = new InputPoller($config);
}
catch (CpeSdk\CpeException $e) {
    echo $e->getMessage();
    $cpeLogger->log_out(
        "FATAL", 
        basename(__FILE__), 
        $e->getMessage()
    );
    exit(1);
}

$cpeLogger->log_out("INFO", __DIR__, "Start Listening.");
print "Start polling ...\n";

// Start polling loop to get incoming commands from SQS input queues
while (42)
    $inputPoller->poll_SQS_queues();
