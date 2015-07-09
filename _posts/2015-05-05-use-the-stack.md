---
layout: page
title: "Pilot the stack"
category: start
date: 2015-05-03 18:55:05
order: 5
---

You now have a running stack! Nice. But unless you can send jobs to it, it's useless.

In order to send jobs to the Stack your clients app must send a `start_job` order through its SQS input queue. To receive updates from the stack, your app must also poll its SQS output queue for incoming messages.

The stack and client apps send and receive JSON messages though SQS:

   - The client apps send JSON commands to the `input` SQS queue. The stack reads from the `input` queue. (InputPoller)
   - The Stack sends JSON messages to the client apps`output` SQS queues. The clients read the `output` SQS queue.

All messages have a defined JSON format that must be respected.

A CPE Client SDK has been implemented in PHP for you application to send properly formatted JSON messages. It can be implemented in any languages though (feel free to contribute).

The Client SDK code is here: https://github.com/sportarchive/CloudProcessingEngine-Client-SDK-PHP<br>
The Documentation is here: http://sportarchive.github.io/CloudProcessingEngine-Client-SDK-PHP/

### Client example

An example of client implementation using this SDK is available in the `client_example` folder of the CPE project.

First you need PHP (obviously) and you must install the PHP dependencies this client example needs. To get the dependencies Run:

    $> make

This will install composer and will install the dependencies in the `vendor` folder.

Then, copy the configuration file sample `clientConfigSample.json` to `clientConfig.json` and edit it. Replace the SQS queues by the SQS queues that you configured.

#### ClientPoller.php

This program polls SQS for new messages coming from the stack.

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

    php ClientPoller.php -c clientConfig.json -d

It should start polling your output SQS queue for incoming messages.

#### ClientCommander.php

This program sends commands to the stack. It can start a new job. It is interactive and will ask you to submit a JSON payload to send to your workflow as input data.

Couple JSON files are available in the `input_samples` folder. It is the JSON paylod we will send to our workflow. You MUST edit them to reference your own file in S3. In fact Cloud Transcode activities are transcoding files located in AWS S3. 

The format of these files is proper to the Cloud Transcode activities. If you create your own activities, you will define your own payload format.

For Cloud Transcode, you must specify an input file (bucket and key) and output (bucket and key). So for the purpose of this test, create two buckets in S3, one for input and the other for output. Then upload a video file to the input bucket and reference it in the JSON files.


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
    Command [enter]: start_job input_samples/input_video.json

The script will take the content of your JSON file and will send it to your stack using your SQS input queue.

Now monitor your stack logs, you should things happening. Monitor also your ClientPoller client app as the stack will be sending messages back it.

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
