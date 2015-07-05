---
layout: page
title: "ActivityPoller"
category: struct
date: 2015-05-06 17:50:14
order: 200
---

The ActivityPoller is a worker and execute activity tasks. It listens to a specified Activity TaskList (See SWF documentation) and execute incoming tasks.

They run independently anywhere, on machines with Internet access. They poll the AWS SWF workflow for incoming tasks. 

There can be N ActivityPollers. They can listen to the same TaskList (for scaling) or to different TaskList if they have different roles. They all process one task at a time.

SWF ensure that a task will be executed only once.

### Activities

The ActivityPoller is just a daemon listening to SWF for specific tasks. It doesn't contain any logic.

In you configuration file, you will list the different activities you support and the path to their PHP code. You will then start the ActivityPoller and from the command line you will tell it to runs a specific activity. It will dynamically load the activity code you mentioned in the config file and will use it to process your task.

Activities are started by the ActivityPoller when a new task that can be handled is received. The ActivityPoller will instanciate an activity class (see 'activities' folder) and will execute the 'do_activity' method.

Transcoding activities will executes "transcoders". Different transcoders can be found in the 'activities/transcoders' folder.

There is one transcoder per filetype: VIDEO, IMG, AUDIO, DOC

### SWF documentation

http://docs.aws.amazon.com/amazonswf/latest/developerguide/swf-dg-develop-activity.html
