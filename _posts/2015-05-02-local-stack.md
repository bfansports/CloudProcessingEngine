---
layout: page
title: "Run the stack"
category: start
date: 2015-05-05 16:45:18
order: 4
---

Now let's get to the fun part. We'll get the stack running.

### Start the Daemons

**We will now start 4 daemons:**

   - Decider
   - InputPoller
   - ActivityPoller - ValidateAsset (worker probing videos)
   - ActivityPoller - TranscodeAsset (worker transcoding videos)

We will show how to start them with and without Docker. Running without Docker requires you to correctly install the dependencies and environment to run the stack. IT is not detailed here.

#### Decider

##### Without Docker

> Install the Python dependencies by running `./setup.py install`

Let's start the decider:

```
   $> cd CloudProcessingEngine/decider/
   $> ./bin/decider.py -d MyDomain -t basic_transcode --plan docs/examples/ct_plan.yml &
   $> tail -f /var/tmp/logs/cpe/decider.log
```

This command starts the decider in the background and will tail the logs so you can see what's going on.

The decider loads the ct_plan.yml. It will process workflows for the `MyDomain` domain and will listen to Decision TaskList `basic_transcode`. Only jobs sent to this domain and for this TaskList will be processed by this Decider.
 
##### With Docker

FIXME


#### InputPoller

To run this command, you must have the default configuration file `cpeConfig.json` all set in the `pollers/config/` folder.

##### Without Docker

> Install the PHP dependencies by running `make` in the `CloudProcessingEngine/pollers` folder

```
    $> cd CloudProcessingEngine/pollers
    $> php InputPoller.php -d &
    $> tail -f /var/tmp/logs/cpe/InputPoller.php.log
```

When your client app will send a new job to the stack you will see it being picked up here.

##### With Docker

FIXME


#### ActivityPoller

To run these commands, you must have the default configuration file `cpeConfig.json` all set in the `pollers/config/` folder.

##### Without Docker

> Install the PHP dependencies by running `make` in the `CloudProcessingEngine/pollers` folder.

> **IMPORTANT:** The Cloud Transcode activities also have dependencies. Without Docker you need to install them as well. Go to the `CloudTranscode` project and run `make` as well.

**Let's start the `ValidateAsset` worker:**

```
    $> cd CloudProcessingEngine/pollers
    $> php ActivityPoller.php -d -D MyDomain -T ValidateAsset-v2 -A ValidateAsset -V v2 &
    $> tail -f /var/tmp/logs/cpe/ActivityPoller.php-ValidateAsset.log
```

When this worker receives a task to process you will see output in the log file.

**Let's start the `TranscodeAsset` worker:**

```
    $> cd CloudProcessingEngine/pollers
    $> php ActivityPoller.php -d -D MyDomain -T TranscodeAsset-v2 -A TranscodeAsset -V v2 &
    $> tail -f /var/tmp/logs/cpe/ActivityPoller.php-TranscodeAsset.log
```

##### With Docker

FIXME 

### Logs

Without Docker, all the logs are in `/var/tmp/logs/cpe`. 

FIXME for Docker

<br>

<p>
<h4><a href="use-the-stack.html">Next: Pilot the stack</a></h4>
</p>
