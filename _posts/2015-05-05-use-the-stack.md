---
layout: page
title: "Pilot the stack"
category: start
date: 2015-05-03 18:55:05
order: 5
---

You now have a running stack! Nice. But unless you can send jobs to it, it's useless.

In order to send jobs to the Stack you must send a 'start_job' order through your SQS input queue. To receive updates from the stack, you must also poll you SQS output queue for incoming messages.

The stack and the client apps send and receive JSON messages though SQS:

   - The clients send JSON commands to the 'input' SQS queue. The stack reads from the `input` queue. (InputPoller)
   - The Stack sends JSON messages to the 'output' SQS queue. The clients read the `output` SQS queue.

All messages have a defined JSON format that must be respected.

A Client SDK has been implemented in PHP to send properly formatted JSON messages. It can be implemented in any languages. The specifications are here: http://sportarchive.github.io/CloudProcessingEngine-Client-SDK/

### Client example

An example of client implementation using this SDK is available in the `client_example` folder.

First you need to install the PHP dependencies these programs need. Run:

    $> make

This will install composer and will install the dependencies in the `vendor` folder.

Then, copy the configuration file sample `clientConfigSample.json` to `clientConfig.json` and edit it. Replace the SQS queues by your own SQS queues that you configured.

#### ClientPoller.php

This program polls SQS for new messages coming from the stack.

*Note: You need PHP installed on your machine to run this test.*

```
Usage: php ClientPoller.php -c configFile [-h] [-k <key>] [-s <secret>] [-r <region>]
-h: Print this help
-d: Debug mode
-c: configFile
-k <AWS key>: Optional. Will use env variables by default
-s <AWS secret>: Optional. Will use env variables by default
-r <AWS region>: Optional. Will use env variables by default
```

Run it as follow:

    php ClientPoller.php -c clientConfig.json

It should start polling your output SQS queue for incoming messages.

#### ClientCommander.php

This program can start a new job. It is interactive and will ask you to submit a JSON payload to send to your new workflow.

Couple JSON input payload are available in the `input_samples` folder. You MUST edit them to reference your own files.

The format of these files is proper to the Cloud Transcode activities. If you create your own activities, you will define your payload format.

For Cloud Transcode, you must specify an input file (bucket and key) and output (bucket and key). So for the purpose of this test, create two buckets in S3, one for input and the other for output. Then upload a video file to the input bucket.

Once done, edit the input JSON files.

```
Usage: php ClientCommander.php -c configFile [-h] [-k <key>] [-s <secret>] [-r <region>]
-h: Print this help
-d: Debug mode
-c: configFile
-k <AWS key>: Optional. Will use env variables by default
-s <AWS secret>: Optional. Will use env variables by default
-r <AWS region>: Optional. Will use env variables by default

Use the following commands to send messages to the stack.

Commands:
start_job <filepath>: Start a new job. Pass a JSON file containing the instruction (see: input_samples folder)
```

Run it as follow:

    php ClientCommander.php -c clientConfig.json
    Command [enter]: start_job input_samples/input_thumbs1.json

The script will take the content of your JSON file and will send it to your stack using your SQS input queue.

Now monitor your stack logs, you should things happening. Monitor also your ClientPoller as the stack will be sending messages back.

### Check the result

Once both ActivityPollers are done processing, the workflow will end and you should have our result file in your output S3 bucket.

If you see errors or encounter issues with this example, let us know and we will fix the documentation or the code.

If you want to use Cloud Transcode, head to the project GitHub page: https://github.com/sportarchive/CloudTranscode

### Sequence

As soon as we submit a new job through SQS the following sequence will happen:

   - InputPoller will receive the SQS message requesting a new workflow to be started. IT will ask SWF to start a workflow of the Type mentioned in your JSON input payload.
   - Decider will receive a decision task saying that a new workflow has started. It will make the decision of what to do next based on your plan. It will decide to execute the first step which is ValidateAsset. It sends its decision to SWF.
   - ActivityPoller (ValidateAsset) which listens to the sa_validate TaskList (see SWF documentation) will receive a new task from SWF. It will take the input payload coming with the task request and do its job. Once done it will returns an output, the metadata information about the video. It sends progress and updates to SQS back to the client.
   - ActivityPoller (TranscodeAsset) which listens to the sa_transcode TaskList will receive a new task from SWF. It will take the input payload (which contains the output of the previous task) and will start transcoding the asset using the parameters provided in input. It sends progress and updates to SQS back to the client.
   - Decider sees the last activity has finished. It marks the workflow as completed.

### Create your activities

If you create activities that can benefit the community, send us a message and we will mention your work on the CPE project page.

To get started with creating your activities, keep reading this documentation and check the Cloud Transcode project for example.

### Contribute

If you wish to contribute to the CPE project, make sure you follow the contribution instructions here: https://github.com/sportarchive/CloudProcessingEngine/blob/master/CONTRIBUTING.md

We all appreciate your help, thank you.
