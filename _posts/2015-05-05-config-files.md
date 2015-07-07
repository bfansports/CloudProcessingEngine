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

### cloudTranscodeConfig.json

In `CloudProcessingEngine/pollers/config/` you will find a configuration file example: `cloudTranscodeConfigSample.json`. Rename it to `cloudTranscodeConfig.json`. The Pollers are expecting a file named 'cloudTranscodeConfig.json' by default. However using the `-c <config_file path>` command line parameter you can load any config file you want.

### Config details

You must customize the config file for your need:

   - Edit the SQS listed to use your the SQS queues you created for your client application
   - List the Activities your ActivityPoller can use and where the code reside.

The `cloudTranscodeConfigSample.json` template uses the Cloud Transcode activities as example. We list them and reference the PHP code implementing the Activity logic.

See the Cloud Transcode project to see how the activities are implemented: https://github.com/sportarchive/CloudTranscode


