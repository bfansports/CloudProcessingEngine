---
layout: page
title: "Config files"
category: config
date: 2015-05-05 23:43:56
order: 1
---

The CPE project is composed of a Decider and two Pollers.

   - The Decider uses a Plan.yaml as configuration file.
   - The Pollers (InputPoller and ActivityPoller) use a JSON configuration file located in `CloudProcessingEngine/pollers/config/`

### cpeConfig.json

In `CloudProcessingEngine/pollers/config/` you will find a configuration file example: `cpeConfigSample.json`. Rename it to `cpeConfig.json`.

The Pollers are expecting a file named 'cpeConfig.json' by default. However using the `-c <config_file path>` command line parameter you can load any config file you want.

### Config details

You must customize the config file for your need:

   - Edit the SQS queues listed to use the SQS queues you created for your client application
   - List the Activities your ActivityPoller can use and where the code reside.

The `cpeConfigSample.json` template uses the Cloud Transcode activities as example. We list them and reference the PHP code implementing the Activity logic.

See the Cloud Transcode project to see how the activities are implemented: https://github.com/sportarchive/CloudTranscode

The config file looks as follow:

```json
{
    "clients": [
        {
            "name": "[CLIENT NAME]",
            "queues": {
                "input": "[INPUT QUEUE]",
                "output": "[OUTPUT QUEUE]"
            }
        }
    ],
    "activities": [
        {
            "name": "ValidateInputAndAsset",
            "version": "v1",
            "description": "Check input command and asset to be transcoded. FFProbe the input file.",
            "file": "[PATH TO CT PROJECT AND FILE]/ValidateInputAndAssetActivity.php",
            "class": "ValidateInputAndAssetActivity",
        },
        {
            "name": "TranscodeAsset",
            "version": "v1",
            "description": "Perform transcoding on input asset and generate output file(s)",
            "file": "[PATH TO CT PROJECT AND FILE]/TranscodeAssetActivity.php",
            "class": "TranscodeAssetActivity"
        }
    ]
}
```

The 'clients' section is self explanatory. It is an array as you can have several client apps using the stack. They should all have their own SQS queues.

#### Activity anatomy

Activities are defined in the configuration file so they can be loaded by the ActivityPoller and regiestered in SWF.

**The activities are defined as follow:**

   - **"name":** The name of the activity as it will be registered in SWF
   - **"version":** The version of the activity as it will be registered in SWF. You can run diffrent version of the same logic for example. Pretty cool for testing new code in production without disrupting everything.
   - **"description":** A description about your activity. Purely informative.
   - **"file":** The path to the PHP file that implement your activity.
   - **"class":** The name of the class in that file that implement your activity.

Your activities can be located anywhere on the file system as long as the ActivityPoller can access and read them.
