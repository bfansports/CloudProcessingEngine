# What is the Cloud Processing Engine (CPE) ?

[![Join the chat at https://gitter.im/sportarchive/CloudProcessingEngine](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/sportarchive/CloudProcessingEngine?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

CPE allows you to distribute and scale processing accross many machines located anywhere (any cloud provider or local). If you have processes that need to scale, then CPE is for you. 

Example:
- Transcoding media files (videos, audio, documents, etc) requires processing power on demand and must scale if a lot of transcoding is requiered. This is the original need that gave birth to CPE. See Cloud Transcode: https://github.com/sportarchive/CloudTranscode

# Technology

We use the following AWS services:
- SWF: Workflow mangement. SWF allows you to create processing workflows so you can chain activities. Each activity is handled by a worker who will process a certain task using input data.
- SQS: Messaging and communication. Clients using the CPE stack can send commands to the stack using SQS to initiate a new workflow for example. They receive job updates, progress, and output results from SQS as well.

# High Level Architecture
![Alt text](/../images/high_level_arch.png?raw=true "High Level Architecture")

# Documentation

http://sportarchive.github.io/CloudProcessingEngine/

# Task tracking
Check the project status and tasks in the pipe on Pivotal Tracker:
- https://www.pivotaltracker.com/n/projects/1044000


