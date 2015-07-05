---
layout: default
title: "Cloud Processing Engine"
---

## Welcome

In this documentation you will find the necessary information to get started with the Cloud Processing Engine (CPE) project.

<br>

### What is the CPE?

CPE allows you to process tasks at scale in a distributed way.

Tasks are part of workflows. The workflow orchestrate the tasks execution and make sure tasks are exeucted in order.

To achive this, we rely on the following AWS services:

   - SWF: To manage workflow and task distribution
   - SQS: For queuing and messaging

CPE can be used to run any type of tasks that need to scale with the load.

### Some example

   - **File transcoding:** You need to transcode files in different formats (videos, images, audio, etc). This use case is the reason why CPE was created. The **Cloud Transcode** project implements the activiy workers for doing transcoding. Those workers are used by the CPE stack. See: https://github.com/sportarchive/CloudTranscode
   - **Billing process:** You have many steps in your billing process and one step depends on the other. Based on the outcome of one step you need to start another. CPE allows you to build a dynamic conditional workflows very declaratively using a YAML plan. 

### How can I use it?

Check the Getting Started documentation to get going quickly.

We provide a Vagrant image to run the stack locally on your computer and a Docker container for running the different daemons that compose the CPE stack.

### Architecture

<img src="https://github.com/sportarchive/CloudProcessingEngine/raw/images/high_level_arch.png?raw=true">
