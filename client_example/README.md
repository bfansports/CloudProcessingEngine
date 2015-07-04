## Cloud Processing Engine (CPE) Client example

This folder contains an example of CPE client implementation in PHP.

A CPE client is composed of two parts:
   - A Listener: This daemon polls SQS messages coming from the CPE stack. Activities updates, statuses, errors, etc.
   - A Commander: This code allow you to send commands to the stack: start_job only for now.

A PHP SDK can be used to help you communicate through SQS. This SDK is a very simple wrapper on top of SQS and we hope the community will come up with other implementations in other languages. 
