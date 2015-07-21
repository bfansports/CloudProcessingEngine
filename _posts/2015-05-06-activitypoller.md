---
layout: page
title: "ActivityPoller"
category: comp
date: 2015-05-06 17:50:14
order: 200
---

The ActivityPoller is a SWF Activity worker. It is a daemon that listens to a SWF TaskList and execute incoming tasks (See SWF Activities documentation). There can be N ActivityPoller daemons running. They all listen to a TaskList. 

Each TaskList will forward a specific type of tasks to the ActivityPoller listening to it. So you will have as many TaskLists as there are type of Activities in your workflow. SWF ensures that a task will be executed by only one Activity Worker and only once.

### Activities

The ActivityPoller is just a daemon listening to SWF for incoming tasks. It doesn't contain any logic.

In your poller configuration file, you will list the different activities your stack supports and the path to their PHP code. You will then start the ActivityPoller and from the command line you will tell it to load a specific activity logic. It will dynamically load the activity code you specified in the config file and will use it to process your task.

The logic code is loaded by the ActivityPoller when a new task that can be handled is received. The ActivityPoller will instanciate the Activity class and will execute the 'do_activity' method.

### Dependencies

Before starting the ActivityPoller, you must install the PHP dependencies using composer:

```
    $> cd CloudProcessingEngine/pollers
    $> make
```

This will install the dependencies in the `vendor` folder.

You can also use the `composer.phar` binary by hand to update your dependencies. The Makefile is only there to make this task easier.

### Script usage

```
$> php ActivityPoller.php
Usage: php ActivityPoller.php -D <domain> -T <task_list> -A <activity_name> -V <activity_version> [-h] [-d] [-c <path to JSON config file>]
-h: Print this help
-d: Debug mode
-c <file path>: Optional parameter to override the default configuration file: '/tmp/CloudProcessingEngine/pollers/src/../config/cloudTranscodeConfig.json'.
-D <domain>: SWF domain your Workflow runs on.
-T <task list>: Specify the Activity Task List this activity will listen to. An Activity Task list is the queue your Activity poller will listen to for new tasks.
-A <activity name>: Activity name this Poller can process.
-V <activity version>: Activity version this Poller can process.
```

### Run it

The following command will start the ActivityPoller daemon. It will listen to the `TranscodeActivity-v2` Activity TaskList for the `SADomain` SWF domain, and will execute the `TranscodeActivity` Activity version `v2`.

> **IMPORTANT:** The Decider will by default set the ActivityTask list name for you and will register it in SWF. It uses the `activity_name`-`activity_version`. Make sure your ActivityPoller is set to listen to the proper TaskList. 

```
$> php ActivityPoller.php -D SADomain -T TranscodeActivity-v2 -A TranscodeActivity -V v2 -d &

```

The `TranscodeActivity` activity is listed in the poller configuration file. See the [Config File](config-files.html) section for more info.

By default the daemons will log its output in `/var/tmp/logs/cpe/`.

Tail the log file to see the script output:

```
$> tail -f ActivityPoller.php-TranscodeActivity.log 
1436216992 [INFO] [ActivityPoller.php] [{"name":"SA","queues":{"input":"https:\/\/sqs.us-east-1.amazonaws.com\/441276146445\/nico-ct-input","output":"https:\/\/sqs.us-east-1.amazonaws.com\/441276146445\/nico-ct-output"}}]
1436216992 [INFO] [ActivityPoller.php] Registering Activity: TranscodeActivity:v2
1436216992 [INFO] [ActivityPoller.php] Starting activity tasks polling
1436216992 [DEBUG] [ActivityPoller.php] Polling activity taskList 'sa_transcode' ...
```

The daemon will output information and progress status to the `output` SQS queue at destination to the client application.

### SWF documentation

http://docs.aws.amazon.com/amazonswf/latest/developerguide/swf-dg-develop-activity.html

### SQS documentation

http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/Welcome.html
