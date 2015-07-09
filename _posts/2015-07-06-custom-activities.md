---
layout: page
title: "Custom Activities"
category: activ
date: 2015-07-06 18:10:45
---

To use CPE you need activities, the logic for your workers. The ActivityPoller will load your activity code and will use it to process incoming tasks.

To create your own activities, follow this documentation.

### Requirements

**To create a custom activity you must:**

   - Use PHP. The ActivityPoller is in PHP and will only load PHP code.
   - Use the CloudProcessingEngine-SDK. It provides the classes and methods you need to implement your activities.
   - Make your Activity code available to the ActivityPoller on the machine where it runs.
   - Edit your configuration file to reference your activity PHP file.
   
> Hey you can always implement the ActivityPoller in another language :) it's quite simple.

### CPE SDK

The SDK is the center piece that allows the integration of custom code into CPE.

**The ActivityPoller will dynamically load your PHP activities' code and will call the following methods in order:**

   - do_task_check($activityTask);
   - do_input_validation($activityTask);
   - do_init($activityTask);
   - do_activity($activityTask);

To implement your activity easily, we've created the `CpeActivity` Class located in the SDK. This is a boilerplate and your activity must `extends` this class.

**The CpeActivity class already implement the following for you:**

   - do_task_check($activityTask);
   - do_init($activityTask);
   - do_input_validation($activityTask);

You can extend them if you wish but they work as is. **You must implement the do_activity though. This is where your logic will reside.**

See the SDK source code and documentation for more details: https://github.com/sportarchive/CloudProcessingEngine-SDK

### Implementation Example

```php
<?php

use SA\CpeSdk;

class BasicActivity extends CpeSdk\CpeActivity
{
    function __construct($params, $debug)
    {
        parent::__construct($params, $debug);
        
      	// Custom code for constructor
    }

    // OVerride method, add more validation. 
    public function do_input_validation($task)
    {
        parent::do_input_validation($task);

        // Custom code for input validation
    }

    // Execute the activity logic
    public function do_activity($task)
    {
        parent::do_activity($task);

        // Custom code doing the processing
    }	
}
```

Check the Cloud Transcode project for an example of working implementation.<br>
**See:** https://github.com/sportarchive/CloudTranscode

### Input data

The Input data for your Activity Task is contained in the $task variable and comes straight from SWF. $task contains a JSON object crafted by the `CloudProcessingEngine-Client-SDK` your client app uses. This object contains some metadata and your client application data.

The JSON object is passed to SWF when your client application sends a `start_job` command. The command contains the JSON payload that your application crafted and that your workflow and activities can interpret.

The Decider will receive the JSON object and will pass it along to the first activity OR will transform it using the Plan.yaml syntax detailed in the Decider documentation. 

Your activity will receive that input and it's your responsability to interpret it, validate it and do whatever processing you want to do.

### Output data

Once the `do_activity` is over, your activity code can return output data of any type. The result will be serialized in JSON and will be send back to SWF.

SWF will notify the Decider that the Task is now over and will keep the resulting data. The Decider then initiates the "next step" (or end the workflow based on your Plan) and can pass the output of the first activity to the next. 

You Decider plan determine what is passed between Activities.

