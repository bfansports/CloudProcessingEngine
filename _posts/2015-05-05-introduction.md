---
layout: page
title: "Introduction"
category: start
date: 2015-05-05 19:41:37
order: 1
---


The beauty of Cloud services is that you can use them accross the Internet. Wherever you are.

<b>The Cloud Processing Engine (CPE) is your own cloud service for processing tasks at scale.</b> You run it Locally or in the Cloud and your client applications can use it from anywhere. As long as machines have an Internet connection, <b>you can run this CPE stack anywhere and use it from anywhere.</b>

You can deploy your workers locally, in the cloud, in a private cloud, or on your laptop for development. Or all of the above at the same time. Everything will still communicate and work together.

### Concept

On one side there is the CPE Stack. All its daemons are running somewhere in the Cloud or Locally. On the other side, there are client applications that need to initiate jobs for processing stuffs. To use CPE, the client apps send JSON commands to the stack through SQS. For starting new workflows for example, they will send a `start_job` command. They also listen to SQS for updates (progress, errors, output, etc).

The CPE stack should run in its own AWS account and be segregated from other applications. This is the recommended setup in production, but you're free to run everything on the same account if you want.

### Implementation

The CPE stack has three components:

   - **[Decider](/CloudProcessingEngine/comp/decider.html):** The Decider is a daemon that connects to SWF and follow the progress of your workflows. It makes the decisions on what the "next step" is in your workflows based on the Execution Plan you have created. The Decider is a Git project on its own. See: https://github.com/sportarchive/CloudProcessingEngine-Decider
   - **[InputPoller](/CloudProcessingEngine/comp/inputpoller.html):** The InputPoller listens to your client applications input SQS queues. When a client app sends a command to CPE through SQS, the InputPoller get that message and perform the proper action, like starting a new workflow.
   - **[ActivityPoller](/CloudProcessingEngine/comp/activitypoller.html):** The ActivityPoller is the worker that gets the processing done. You can (and should) run many of them. They will execute the type of tasks they're meant to execute. Each of them are listening to a SWF queue (an Activity TaskList - see SWF documentation). They load custom code that you implement yourself to process you logic. Each ActivityPoller process one tasks at a time.

Each component is a daemon that run on a machine or in a Docker container. They are deployed and managed by you.

#### Scaling

Some activities may require more processing power than others. So group your ActivityPollers together by Activity types and make them scale accordingly. For running your ActivityPollers (workers) at scale you can put them in a Scaling group on AWS and start new machines if needed. Using CloudWath you can achieve that by monitoring your SWF workflows to scale up or down your stack. See: https://aws.amazon.com/blogs/aws/cloudwatch-metrics-for-simple-workflow/

#### Jobs segregation

You must start as many deciders as you have workflows. It's like having several stacks running diffrent jobs as the Decider is the center piece of your stack. Each decider listens to a queue (a Decision TaskList) for SWF to send decision tasks and makes decisions upon them. So when your client app send a `start_job` command through SQS, the InputPoller will create a new workflow of the specified type. This workflow will be automatically associated to the Decider handling this particular type of workflow.

Workers are segregated using a queue as well (Activity TaskList). Each worker listen to a specific queue for a specific task type. SWF is aware of all workers and will send tasks to the correct available workers. You can have workers that process tasks for different workflows using different Deciders. They just listen for Tasks of a certain type, no matter what they're for.

### Communication: SQS & JSON

Client apps and CPE communicate through SQS using JSON messages.

To each client app is assigned two SQS queues:

   - **input queue:** Used by the client app to send commands to the stack. The InputPoller listens to it.
   - **output queue:** Used by the CPE stack to send messages back to the client app. The client app listens to the 'output' queue using the CPE-Client-SDK (see: [Pilot the stack](/CloudProcessingEngine/start/use-the-stack.html)).

You are responsible for creating and managing your SQS queues. Using the AWS console, create the proper queues and entitle the proper AWS accounts to read and write. See [SQS Entitlements](/CloudProcessingEngine/extra/sqs-entitlements.html) for more details.

<br>

<p>
<h4><a href="setup-aws.html">Next: Setup AWS</a></h4>
</p>
