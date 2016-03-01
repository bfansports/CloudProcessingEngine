# Pollers? What are they?

There are two pollers:
   * InputPoller.php: This daemon listens to the INPUT_QUEUE for incoming command from the client application. It is the daemon that start SWF workflows. You can have many input pollers. One for each client application.
   * ActivityPoller.php: This daemon is your worker. It listens to an Activity TaskList for incoming task to execute. The Decider request SWF to start tasks. SWF will then select a worker to execute the task. You can have many activity pollers, each processing different kind of tasks.

# How to use them

In `src` you will find the two pollers. Just execute them to see their usage. You will the to set the proper environment variables for them to work correctly:
   * AWS Key/Secret: You can you an AWS IAM role or just declare those variables manually
   * AWS Region
   * INPUT_QUEUE: The SQS input queue that the InputPoller will listen to
   * OUTPUT_QUEUE: The SQS output queue the ActivityPoller and Decider will use to send out messages to the client applications.


