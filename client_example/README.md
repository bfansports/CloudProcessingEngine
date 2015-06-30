## Cloud Processing Engine (CPE) Example

This contains an example of client implementation in PHP.

A CPE client is composed of two parts:
   - A Listener: This part polls SQS messages coming from the CPE stack. Activities updates, statuses, errors, etc.
   - A Commander: This part sends commands to the stack: Start job, cancel job, etc.

You can implement this part in any language you want as the comnunication is done through AWS SQS.

However, a PHP SDK exists to help you communicate through SQS. This SDK is very simple though and we hope the community will come up with other implementations. 
