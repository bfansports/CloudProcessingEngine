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

##### Build a docker image

You can start the Decider daemon by hand or wrap it in a Docker image. Here is some information about how to create a Docker image in order to run the Decider.

Create a folder where you put:

   - `configs/`: folder that contains your config files
   - `CloudProcessingEngine-Decider/`: folder that contains a CloudProcessingEngine-Decider repo clone. This project contains already a Dockerfile that will launch the Decider for you
   - A New Dockerfile that references the base image and copies the configs in the image. We don't want to put the config in the base image as they contain custom info.

The Docker file should look as follow:

```
FROM sportarc/cloudprocessingengine-decider:latest
MAINTAINER Sport Archive, Inc.

COPY configs/* /etc/cloudprocessingengine/
```

This builds another image on top of the base image. This new image contains your configs and the CPE Decider code.

##### Decider command line

> If not using Docker, Install the Python dependencies by running `./setup.py install`

Let's start the decider:

```
   $> cd CloudProcessingEngine/decider/
   $> ./bin/decider.py -d MyDomain -t basic_transcode --plan docs/examples/ct_plan.yml &
   $> tail -f /var/tmp/logs/cpe/decider.log
```

This command starts the decider in the background and will tail the logs so you can see what's going on.

The decider loads the ct_plan.yml. It will process workflows for the `MyDomain` domain and will listen to Decision TaskList `basic_transcode`. Only jobs sent to this domain and for this TaskList will be processed by this Decider.

If you use Docker, then use the following argument to pass tor `Docker run`:

` --domain My_SWFDomain --task_list my_SWFTaskList --plan /etc/cloudprocessingengine/my_plan.yml --log_file /var/log/deciders/decider.log --output_queue https://sqs.us-east-1.amazonaws.com/xxxxxxxx/CloudProcessingEngine-OutputQueue`

#### Daemons Pollers

There are two Pollers:

   - InputPoller: Listens to SQS queues and start ay workflows. 
   - ActivityPoller: Processes an Activity Task

Both are part of the same CPE code base.

##### Build a docker image

You can start the Pollers by hand or wrap them in a Docker image. Here is some information about how to create a Docker image in order to run the two pollers.

Create a folder where you put:

   - `configs/`: folder that contains your config files. Transcoding configurations such as this:
   
`configs/TranscodeAsset-Assets-v6.json`:

```
{
    "activities": [
        {
            "name": "TranscodeAsset",
            "version": "v6",
            "description": "Perform transcoding on media assets and generate output vide
os or images",
	    "defaultTaskStartToCloseTimeout": "NONE",
	    "defaultTaskScheduleToStartTimeout": "NONE",
	    "defaultTaskScheduleToCloseTimeout": "NONE",
            "file": "/usr/src/cloudtranscode/src/activities/TranscodeAssetActivity.php",
            "class": "TranscodeAssetActivity"
        }
    ]
}
```

   - `CloudProcessingEngine-Pollers/`: folder that contains a CloudProcessingEngine-Pollers repo clone. This project contains already a base Dockerfile that will launch the requested pollers for you
   - `CloudTranscode/`: folder that contains a CloudTranscode repo clone. This project contains source code for CT and the logic for transcoding. The config files must reference those PHP files. Here they will go into: `/usr/src/cloudtranscode/`. We will configure our Dockerfile to copy them there.
   - A New Dockerfile that references the base CloudTranscode image that contains all the FFMpeg libraries, copies the configs in the image, copies CPE code, and CT code too. We don't want to put the config in the base image as they contain custom info. The base image only contains FFMpeg libraries, we built it for you.

This base image is located on Dockerhub here: https://hub.docker.com/r/sportarc/cloudtranscode-base/

The new Docker file you put in your folder should look as follow:

```
FROM sportarc/cloudtranscode-base:latest
MAINTAINER Sport Archive, Inc.

RUN echo "date.timezone = UTC" >> /usr/local/etc/php/conf.d/timezone.ini
RUN apt-get update \
    && apt-get install -y zlib1g-dev autoconf \
    && docker-php-ext-install zip

COPY CloudProcessingEngine-Pollers/pollers /usr/src/cloudprocessingengine
WORKDIR /usr/src/cloudprocessingengine
RUN DEBIAN_FRONTEND=noninteractive TERM=screen \
    apt-get update \
    && apt-get install -y git \
    && make \
    && apt-get purge -y git \
    && apt-get autoremove -y

COPY CloudTranscode /usr/src/cloudtranscode
WORKDIR /usr/src/cloudtranscode
RUN DEBIAN_FRONTEND=noninteractive TERM=screen \
    apt-get update \
    && apt-get install -y git \
    && make \
    && apt-get purge -y git \
    && apt-get autoremove -y

COPY configs/* /etc/cloudtranscode/

ENTRYPOINT ["/usr/src/cloudprocessingengine/bootstrap.sh"]

```

This builds another image on top of the base FFMpeg image `cloudtranscode-base`. This new image contains your configs, the CPE poller code and the CT transcoding logic.


#### InputPoller command line

To run this command, you must have the default configuration file `cpeConfig.json` all set in the `pollers/config/` folder or use the -c to reference a config file.
If you use Docker as above, then your configs will but copied to `/etc/cloudtranscode/myconf.json`

> If you don't use Docker, Install the PHP dependencies by running `make` in the `CloudProcessingEngine/pollers` folder

```
    $> cd CloudProcessingEngine/pollers
    $> php InputPoller.php -d &
    $> tail -f /var/tmp/logs/cpe/InputPoller.php.log
```

When your client app will send a new job to the stack you will see it being picked up here.

If you use Docker, then use this arguments to start the poller

`InputPoller -n my_client -l /var/log/pollers/ -d`

#### ActivityPoller command line

To run these commands, you must have the default configuration file `cpeConfig.json` all set in the `pollers/config/` folder or use the -c to reference a config file.

> If you don't use Docker, Install the PHP dependencies by running `make` in the `CloudProcessingEngine/pollers` folder.

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

If you use Docker, then use this arguments to start the poller with `Docker run`

`ActivityPoller -c /etc/cloudtranscode/my_config.json -D MY_SWFDomain -T MyTaskDef-version -A MyActivity -V activityVersion -l /var/log/pollers/ -d`

### Logs

Without Docker, all the logs are in `/var/tmp/logs/cpe`. 

With Docker you can control where your logs are going.

<br>

<p>
<h4><a href="use-the-stack.html">Next: Pilot the stack</a></h4>
</p>
