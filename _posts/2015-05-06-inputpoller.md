---
layout: page
title: "InputPoller"
category: comp
date: 2015-05-06 17:55:23
order: 300
---

The InputPoller is a daemon that listens to a client application `input` queue. It listens to the queue for incoming commands from the client applications. If the client app sends a `start_job` command for example, the InputPoller will order SWF to start a new workflow using the input payload the client app provided.

### Dependencies

Before starting the InputPoller, you must install the PHP dependencies using composer:

```
$> cd CloudProcessingEngine/pollers
$> make
```

This will install the dependencies in the `vendor` folder.

You can also use the `composer.phar` binary by hand to update your dependencies. The Makefile is only there to make this task easier.

### Script usage

```
$> php InputPoller.php 
Usage: php InputPoller.php [-h] [-d] -c <config_file path>
-h: Print this help
-d: Debug mode
-c <config_file path>: Optional parameter to override the default configuration file: '/tmp/CloudProcessingEngine/pollers/src/../config/cloudTranscodeConfig.json'.
```

### Run it

The following command will start the InputPoller daemon:

```
$> php InputPoller.php -d &

```

By default the daemons will log its output in `/var/tmp/logs/cpe/`.

Tail the log file to see the script output:

```
$> tail -f /var/tmp/logs/cpe/InputPoller.php.log 
1436209659 [DEBUG] [CpeSqsListener.php] Polling from 'https://sqs.us-east-1.amazonaws.com/441276146445/nico-ct-input' ...
1436209669 [DEBUG] [CpeSqsListener.php] Polling from 'https://sqs.us-east-1.amazonaws.com/441276146445/nico-ct-input' ...
1436209679 [DEBUG] [CpeSqsListener.php] Polling from 'https://sqs.us-east-1.amazonaws.com/441276146445/nico-ct-input' ...
1436209689 [DEBUG] [CpeSqsListener.php] Polling from 'https://sqs.us-east-1.amazonaws.com/441276146445/nico-ct-input' ...
```

#### Input requirements

The InputPoller listens for input commands from the client application and performs actions on SWF. The JSON input payload you send over to the InputPoller MUST contains the following required field:

   - `workflow`: Contains information about the workflow you want the InputPoller to interact with. The format is as follow:

```json
    "workflow": {
        "name": "Transcode",
        "version": "1.0",
        "taskList": "basic_transcode",
        "domain": "SADomain"
    }
```

This field contains the workflow `name` and `version` that will be used to perform the command on SWF. Most importantly it contains the `Domain` in which your workflow run or will run and the `taskList` which is the Decision TaskList your Decider is listening to. Make sure it all match.

> Examples of JSON input can be found in the `client_example/input_sample` folder.

### SWF documentation

http://docs.aws.amazon.com/amazonswf/latest/developerguide/swf-welcome.html

### SQS documentation

http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/Welcome.html
