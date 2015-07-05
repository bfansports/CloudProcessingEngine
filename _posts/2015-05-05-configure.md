---
layout: page
title: "Install & Configure"
category: start
date: 2015-05-05 20:13:46
order: 3
---

Now you need to install the stack and configure it so the test client can use it.

### Requirements

<b>You need a 64bits machine if you want to run the DEV environment.</b> If you have a 32bits it will not work. We use Docker and it requires a 64bits machine.

You can still run the stack without Docker if you want, in this case, you'll have to dig little deeper. *It's only three daemons to start, it's not very hard.*

We will run the stack locally in a Virtual Machine.
We are using VirtualBox in this example, but VMWare works as well. 

We are using Vagrant to start the VM and to configure it correctly for you.

#### VirtualBox and Vagrant

Install both applications on your local computer.

   - [Install VirtualBox](https://www.virtualbox.org/wiki/Downloads) 
   - [Install Vagrant](http://www.vagrantup.com/downloads) 

### Install the stack

Just clone the CPE project locally somewhere:

    $> git clone https://github.com/sportarchive/CloudProcessingEngine.git

The "decider" folder in the project is a Git sub module. You must initialize it.

    $> git submodule update decider

Check if the "decider" folder contains the proper code. You can make sure it is up to date (in case the module reference is outdated) by running:

    $> cd decider
    $> git pull origin master

### Configure

FIXME TYLER

#### Install activities

The stack needs activities so it can process stuffs. In your config file, you need to make reference to each activity PHP file. Each activity is in charge of a specific task in your workflow.

For the purpose of this example we will use the Cloud Transcode activities. Head to the Cloud Transcode project on Github: https://github.com/sportarchive/CloudTranscode

Clone it locally somewhere:

    $> git clone https://github.com/sportarchive/CloudTranscode.git

Then edit your configuration file and add couple activities:

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
            "description": "Check input command and asset to be transcoded.",
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

Now CPE knows two types of activity workers and where the code is:

   - ValidateInputAndAsset
   - TranscodeAsset

We can now create a workflow plan that will use those two activities to transcode files.

#### Create a Plan

The decider expects a plan so it can make its decisions. A plan describe your workflow. It is a YAML file that describes your workflow steps and the activities that will be used to process them.

We will create a simple plan containing two steps:

   - **Step 1:** Validate and probe an input video
   - **Step 2:** Transcode the input video into a new video
    
This plan has been created for you so you can get started. Open `plans/ct_plan.yml` and take a look at the plan YAML syntax.

For more information about the syntax and what you can do with your workflow, **head to the Decider documentation here:** http://sportarchive.github.io/CloudProcessingEngine-Decider

*The input data describing where the input video file is and which output video format we need, will be crafted in JSON and submitted to the workflow as input. Then the workflow will pass along this input to our Activity workers. The workers will read this input and will perform the transcoding we want.*

<br>

<p>
<h4><a href="local-stack.html">Next: Run the stack</a></h4>
</p>
