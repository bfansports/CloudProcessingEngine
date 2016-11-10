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
 * The activity poller listen for "activity tasks" 
 * Stuff to do for this worker: compute, process, whatever.
 * The ActivityPoller can be configued to listen on a particular SWF TaskList (queue)
 * It will process tasks only coming in the TaskList
 */

require_once __DIR__ . "/../vendor/autoload.php";

use Aws\Swf\Exception;
use SA\CpeSdk;

class ActivityPoller
{
    private $debug;
    private $cpeSwfHandler;
    private $cpeLogger;
    
    private $domain;
    private $taskList;
    private $activityName;
    private $activityVersion;
    private $activityHandler;
    private $knownActivities;
    
    const ACTIVITY_FAILED = "ACTIVITY_FAILED";
  
    public function __construct($config)
    {
        global $debug;
        global $cpeLogger;
        
        global $domain;
        global $taskList;
        global $activityName;
        global $activityVersion;
        
        $this->debug           = $debug;
        $this->cpeLogger       = $cpeLogger;

        // SWF related
        $this->domain          = $domain;
        $this->taskList        = $taskList;
        $this->activityName    = $activityName;
        $this->activityVersion = $activityVersion;
        $this->knownActivities = $config->{'activities'};
        
        // For creating SWF object 
        $this->cpeSwfHandler   = new CpeSdk\Swf\CpeSwfHandler($this->debug);
        
        // Check and load activities to handle
        if (!$this->register_activities()) {
            $msg = "No activity class registered! Check the logs (/var/tmp/logs/cpe/). Exiting ...\n";
            $cpeLogger->log_out(
                "FATAL", 
                basename(__FILE__),
                $msg
            );
            print $msg;
            exit(1);
        }
    }
    
    // We poll for new activities
    // Return true to keep polling even on failure
    // Return false will stop process !
    public function poll_for_activities()
    {
        // Poll from all the taskList registered for each activities 
        if ($this->debug)
            $this->cpeLogger->log_out("DEBUG", basename(__FILE__),
                "Polling activity taskList '" . $this->taskList  . "' ... ");
            
        try {
            // Call SWF and poll for incoming tasks
            $activityTask = $this->cpeSwfHandler->swf->pollForActivityTask([
                    "domain"   => $this->domain,
                    "taskList" => array("name" => $this->taskList)
                ]);

            // Handle and process the new activity task
            $this->process_activity_task($activityTask);
        } catch (CpeSdk\CpeException $e) {
            $this->cpeLogger->log_out("ERROR", basename(__FILE__),
                "Unable to poll activity tasks! " . $e->getMessage());
        }
        
        return true;
    }

    // Process the new task using one of the activity handler classes registered
    private function process_activity_task($activityTask)
    {
        // Get activityType and WorkflowExecution info
        if (!($activityType      = $activityTask->get("activityType")) ||
            !($workflowExecution = $activityTask->get("workflowExecution")))
            return false;
        
        $this->cpeLogger->log_out("INFO",
            basename(__FILE__),
            "Starting activity: name=" 
            . $activityType['name'] . ",version=" . $activityType['version'],
            $workflowExecution['workflowId']);

        // Has activity handler object been instantiated ?
        if (!isset($this->activityHandler)) 
        {
            $this->cpeLogger->log_out("ERROR", basename(__FILE__),
                "The activity handler class for this activity type is not instantiated !",
                $workflowExecution['workflowId']);
            
            return false;
        }
        
        $result = null;
        $reason = 0;
        $details = 0;
        try {
            // Check activity task
            $this->activityHandler->do_task_check($activityTask);
            // Perform input validation
            $this->activityHandler->do_input_validation();

            if ($this->debug)
                $this->cpeLogger->log_out("DEBUG", basename(__FILE__), 
                    "Activity input:\n" . print_r($this->activityHandler->input, true));
        
            // Run activity task
            $result = $this->activityHandler->do_activity($activityTask);

            if ($this->debug && $result)
                $this->cpeLogger->log_out("DEBUG", basename(__FILE__), 
                    "Activity output:\n" . print_r($result, true));
        
        } catch (CpeSdk\CpeException $e) {
            $reason  = $e->ref;
            $details = $e->getMessage();
        } catch (Exception $e) {
            $reason  = self::ACTIVITY_FAILED;
            $details = $e->getMessage();
        } finally {
            if ($reason && $details)
            {
                // Activity has failed!
                // We send back to SWF the reason and details about the failure
                $this->activityHandler->activity_failed(
                    $activityTask, 
                    $reason, 
                    $details
                );
                return false;
            }
        }
    
        // Activity has completed!
        $this->activityHandler->activity_completed($activityTask, $result);
        return true;
    }
  
    // Register and instantiate activities handlers classes
    private function register_activities()
    {
        foreach ($this->knownActivities as $knownActivity)
        {
            if ($this->activityName == $knownActivity->{"name"} &&
                $this->activityVersion == $knownActivity->{"version"})
            {
                $activityToHandle = $knownActivity;
                
                if (!file_exists($activityToHandle->{"file"}))
                {
                    $this->cpeLogger->log_out("ERROR", basename(__FILE__),
                        "The code file '".$activityToHandle->{"file"}."' for activity: name=" 
                        . $activityToHandle->{"name"} . ",version=" 
                        . $activityToHandle->{"version"}." doesn't exists! Check if the file is accessible and if the path is correct in your config file.");
                    return false;
                }
                
                $this->cpeLogger->log_out("INFO", basename(__FILE__),
                    "Registering Activity: $this->activityName:$this->activityVersion");
        
                // Load the file implementing the activity
                require_once $activityToHandle->{"file"};
                
                // Instantiate the Activity class that will process Tasks

                if (!isset($this->cpeLogger) ||
                    !$this->cpeLogger)
                    print "EMPTY !!!\n";

                $params = [
                    "domain"  => $this->domain,
                    "name"    => $activityToHandle->{"name"},
                    "version" => $activityToHandle->{"version"}
                ];
                if (isset($activityToHandle->{"defaultTaskStartToCloseTimeout"}))
                    $params["defaultTaskStartToCloseTimeout"] =
                        $activityToHandle->{"defaultTaskStartToCloseTimeout"};
                if (isset($activityToHandle->{"defaultTaskHeartbeatTimeout"}))
                    $params["defaultTaskHeartbeatTimeout"] =
                        $activityToHandle->{"defaultTaskHeartbeatTimeout"};
                if (isset($activityToHandle->{"defaultTaskScheduleToStartTimeout"}))
                    $params["defaultTaskScheduleToStartTimeout"] =
                        $activityToHandle->{"defaultTaskScheduleToStartTimeout"};
                if (isset($activityToHandle->{"defaultTaskScheduleToCloseTimeout"}))
                    $params["defaultTaskScheduleToCloseTimeout"] =
                        $activityToHandle->{"defaultTaskScheduleToCloseTimeout"};
                
                $this->activityHandler = 
                    new $activityToHandle->{"class"}(
                        $params, 
                        $this->debug,
                        $this->cpeLogger
                    );
                
                $this->cpeLogger->log_out("INFO", basename(__FILE__),
                    "Activity handler registered: name=" 
                    . $activityToHandle->{"name"} . ",version=" 
                    . $activityToHandle->{"version"});

                return true;
            }
        }
        
        $this->cpeLogger->log_out("ERROR", basename(__FILE__),
            "No Activity handler was found for: name=" 
            . $this->activityName . ",version=" 
            . $this->activityVersion.". Check your config file and ensure your 'activity' name AND 'version' is there.");    
        return false;
    }
}



/*
 ***************************
 * POLLER START
 ***************************
*/

// Globals
$debug = false;
$cpeLogger;

// Usage
function usage($defaultConfigFile)
{
    echo("Usage: php ". basename(__FILE__) . " -D <domain> -A <activity_name> -V <activity_version> [-T <task_list>] [-h] [-d] [-c <config_file path>] [-l <log path>]\n");
    echo("-h: Print this help\n");
    echo("-d: Debug mode\n");
    echo("-c <config_file path>: Optional parameter to override the default configuration file: '$defaultConfigFile'.\n");
    echo("-l <log_path>: Location where logs will be dumped in (folder).\n");
    echo("-D <domain>: SWF domain your Workflow runs on.\n");
    echo("-A <activity_name>: Activity name this Poller can process.\n");
    echo("-V <activity_version>: Activity version this Poller can process.\n");
    echo("-T <task list>: Specify the Activity Task List this activity will listen to. An Activity Task list is the queue your Activity poller will listen to for new tasks. If not specified, it will listen to the default channel used by the Decider : 'activity_name-activity_version'.\n");
    exit(0);
}

// Check command line input parameters
function check_input_parameters(&$defaultConfigFile)
{
    global $debug;
    global $cpeLogger;

    // Filling the globals with command input
    global $domain;
    global $taskList;
    global $activityName;
    global $activityVersion;
    
    // Handle input parameters
    if (!($options = getopt("D:T:A:V:c:l:hd")))
        usage($defaultConfigFile);
    
    if (!count($options) || isset($options['h']))
        usage($defaultConfigFile);

    // Debug
    if (isset($options['d']))
        $debug = true;

    // Domain
    if (!isset($options['D']))
    {
        echo "ERROR: You must provide a Domain\n";
        usage($defaultConfigFile);
    }
    $domain = $options['D'];

    // Activity name
    if (!isset($options['A']))
    {
        echo "ERROR: You must provide an Activity name\n";
        usage($defaultConfigFile);
    }
    $activityName = $options['A'];
    
    $logPath = null;
    if (isset($options['l']))
    {
        $logPath = $options['l'];
    }
    $cpeLogger = new CpeSdk\CpeLogger($logPath, $activityName, $debug);
    
    // Activity version
    if (!isset($options['V']))
    {
        echo "ERROR: You must provide an Activity version\n";
        usage($defaultConfigFile);
    }
    $activityVersion = $options['V'];
    
    // Tasklist
    if (!isset($options['T']))
    {
        $taskList = $options['A'].'-'.$options['V'];
    } else {
        $taskList = $options['T'];
    }
    
    // OVerrride config file
    if (isset($options['c']))
    {
        $cpeLogger->log_out(
            "INFO",
            basename(__FILE__),
            "Config file: '" . $options['c'] . "'");
        $defaultConfigFile = $options['c'];
    }

    try {
        // Check config file
        if (!($config = json_decode(file_get_contents($defaultConfigFile))))
        {
            print "\n[CONFIG ISSUE]\nConfiguration file '$defaultConfigFile' invalid or non-existant.\n\n[EASY FIX]\nGo to the directory mentioned in the error above and rename the template file 'cpeConfigTemplate.json' to 'cpeConfig.json'. Configure your Activities. As example you have Activities for CloudTranscode already setup in the template. You can declare your Activities and start executing tasks in an SWF workflow.\n";
            exit(1);
        }
    }
    catch (Exception $e) {
        print $e;
    }
            
        
    # Validate against JSON Schemas
    # if (($err = validate_json($config, "config/mainConfig.json")))
    # exit("JSON main configuration file invalid! Details:\n".$err);

    return $config;
}

// Get config file
$defaultConfigFile =
    realpath(dirname(__FILE__)) . "/../config/cpeConfig.json";
$config = check_input_parameters($defaultConfigFile);

// Instantiate ActivityPoller
try {
    $activityPoller = new ActivityPoller($config);
} 
catch (CpeSdk\CpeException $e) {
    $cpeLogger->log_out("FATAL",
        basename(__FILE__), $e->getMessage());
    exit(1);
}

$cpeLogger->log_out("INFO", basename(__FILE__), "Starting activity tasks polling");

// Start polling loop
while (42)
    $activityPoller->poll_for_activities();

