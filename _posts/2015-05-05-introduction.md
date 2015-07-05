---
layout: page
title: "Introduction"
category: start
date: 2015-05-05 19:41:37
order: 1
---


The beauty of Cloud services is that you can use them accross the Internet. Wherever you are.

<b>The Cloud Processing Engine (CPE) is your own cloud service for processing tasks at scale.</b><br>
You run it Locally or in the Cloud and your client applications can use it from anywhere.

CPE uses the following Cloud services to make this happen:

   - [SWF](http://aws.amazon.com/swf/): Handles workflow and execution sequences. You can orchestrate the execution of your tasks, create dependencies, sequences, run them in parallel, etc. You can many type of activity workers performing different tasks.
   - [SQS](http://aws.amazon.com/sqs/): Handles communication between the CPE stack and your client application which initiate jobs and listen for incoming updates from CPE.

As long as you have an Internet connection and an Amazon AWS account, <b>you can run this CPE stack anywhere and use it from anywhere.</b>

### Concept

On one side there is the CPE Stack, running somewhere in the Cloud or Locally. On the other side, there are client applications, using CPE and its workers for processing stuffs. They commands to the stack through SQS and listen to SQS for updates (progress statuses, errors, output, etc).

The stack uses AWS credentials to use AWS services, and should be segregated on its own AWS account. 

Client applications should use their own AWS account and should not share the same account as the CPE stack. This is the recommended setup in production, but you're free to run everything on the same account.

### Implementation

The CPE stack is composed of three components:

   - **Decider:** The Decider is a daemon that connects to SWF and follow the progress of your workflows. It makes the decisions on what the "next step" is in your workflows based on the Execution Plan you have created. Details about the Decider and Plan definition are detailed in the [Decider section](/struct/decider.html).
   - **ActivityPoller:** The ActivityPoller is the worker that gets the processing done. You can have many of them. They will execute the type of tasks they're meant to execute. Each of them are listening to an Activity TaskList (See SWF documentation). They use custom code that you will implement yourself to process the tasks you want. Each ActivityPoller process one tasks at a time.
   - **InputPoller:** The InputPoller listens to your client application input SQS queue. When a client app sends a commend to CPE through SQS, the InputPoller get that message and perform the proper action, start a new workflow for example.


### Communication: SQS & JSON

Client app and CPE communicate through SQS using JSON messages.

Each client is assigned two SQS queues:

   - **input queue:** Used by the client to send commands to the stack. The InputPoller listens to it.
   - **output queue:** Used by the stack to send messages back to the client app. The client apps listen to their respective 'output' queue.

You are responsible for managing your SQS queues. Using the AWS console, create the proper queues and entitle the proper AWS accounts to read and write.

<br>

<p>
<h4><a href="setup-aws.html">Next: Setup AWS</a></h4>
</p>
