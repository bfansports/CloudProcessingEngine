---
layout: default
title: "Cloud Processing Engine"
---

## Welcome

Here you will find the necessary information to get going with Cloud Processing Engine.

<br>

### What is the Cloud Processing Engine?

This is an engine to run distributed tasks in the Cloud. It uses:
   - SWF: To manage workflow and task distribution
   - SQS: For queuing and messaging

It can be used for running any kind of tasks anywhere. The only dependency is an Internet connection and an AWS account.

### How can I use it?

Check the Getting Started documentation to get going quickly.

We provide a Docker container for running the different programs that compose the processing stack.

### Usage example

This is the core engine of Cloud Transcode. We created this engine for dispatching transcoding jobs.

Ultimatly the core engine could be used for any kind of task so we decided to make a seperate project.
