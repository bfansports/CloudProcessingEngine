---
layout: page
title: "Install & Configure"
category: start
date: 2015-05-05 20:13:46
order: 3
---

Now you need to install the stack and configure it so our client app can use it.

### Requirements

We will run the stack locally using Docker. So get Docker installed on your machine: https://docs.docker.com/installation/

<b>You MUST use a 64bits machine if you want to use Docker.</b> If you have a 32bits Docker will **NOT** work. 

You can still run the stack without Docker if you want, in this case, you'll have to install the dependencies yourself on your machines.

### Install the stack

Just clone the CPE project locally somewhere:

    $> git clone https://github.com/sportarchive/CloudProcessingEngine.git

The CPE project is built in PHP and includes the ActivityPoller and InputPoller scripts.

Then install the "decider" project in another location:

    $> git clone https://github.com/sportarchive/CloudProcessingEngine-Decider

The Decider is built in Python and includes the Decider that communicates with SWF and starts Activities.

### Install activities

The ActivityPoller (worker) needs activities so it has some logic to execute stuffs. In your config file, you will make reference to each activity code file. Each activity is in charge of a specific task in your workflow so you can have as many as you want.

For the purpose of this example we will use the Cloud Transcode activities. Head to the Cloud Transcode project on Github: https://github.com/sportarchive/CloudTranscode

Clone the project locally somewhere:

    $> git clone https://github.com/sportarchive/CloudTranscode.git

> You will now create your configuration file to reference the activity files located in the CT project in `src/activities/`.

### Configure

> Back in the CPE project.

One configuration file is needed for the pollers. A default config file named `cpeConfigTemplate.json` is located in `pollers/config` folder. Rename this file `cpeConfig.json` and open it in an editor.

There you must configure:

   - Your client app SQS queue that you must have created. 
   - The list of activities your Workflow is going to handle and where their code is located.

Check what the configuration file look like in the [Config files](/CloudProcessingEngine/config/config-files.html) section.

**Cloud Transcode declares two types of activity workers:**

   - ValidateInputAndAsset
   - TranscodeAsset

Make sure you put the correct path to `ValidateInputAndAssetActivity.php` and `TranscodeAssetActivity.php`.

> We can now create a Decider YAML Plan to create a workflow that will use those two activities to transcode files.

### Create a Plan

The decider expects a plan so it can make its decisions. A plan describes your workflow. It is a YAML file that describes your workflow steps and the activities that will be used to process them.

**We will create a simple plan containing two steps:**

   - **Step 1:** Validate and probe an input video
   - **Step 2:** Transcode the input video into a new video
    
A plan has been created for you so you can get started quickly. In the `decider` folder, open `docs/examples/ct_plan.yml` and take a look at the plan YAML syntax.

For more information about the Decider, check the Decider entry: http://sportarchive.github.io/CloudProcessingEngine/comp/decider.html

For syntax details and what you can do with your workflows, **head to the Decider documentation here:** http://sportarchive.github.io/CloudProcessingEngine-Decider

*The input data describing the job to do (where the input video file is and which output video format we need), will be crafted in JSON and submitted to the workflow as input by our client app. Then the workflow will pass along this input to our Activity workers. The workers will read this input and will perform the transcoding we want.*

<br>

<p>
<h4><a href="local-stack.html">Next: Run the stack</a></h4>
</p>
