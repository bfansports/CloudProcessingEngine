## Cloud Processing Engine (CPE) Client example

This folder contains an example of CPE client implementation in PHP.

A CPE client must be composed of two parts:
   - A Listener: This daemon polls SQS messages coming from the CPE stack. Activities updates, statuses, errors, etc.
   - A Commander: This code allow you to send commands to the stack: start_job only for now.

This client makes use of the Client SDK for CPE: https://github.com/sportarchive/CloudProcessingEngine-Client-SDK-PHP
