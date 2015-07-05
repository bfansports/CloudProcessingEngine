---
layout: page
title: "Run the stack"
category: start
date: 2015-05-05 16:45:18
order: 4
---

Now let's get to the fun part. We'll get the stack running.

### Start the VM

Within the project directory where the VagrantFile is located, run:

    $> vagrant up --provision

Vagrant will start a VM on your machine, install Ubuntu on it, and then Docker. The Docker container will run the whole stack. This command will take some time the first time you run it. Monitor the Vagrant output in your terminal.

Once Vagrant started the VM, you can verify if the stack runs correctly by logging into your VM:

    $> vagrant ssh

Once in the VM you can run the `docker ps` command to see the docker containers running. You should see this:

```
FIXME TYLER
```

You can see that there are four Docker containers running. Each of them runs a daemon.


### Stack logs

Check the log files located in the `logs` folder.

You should have four log files:

   - Decider.php.log
   - InputPoller.php.log
   - ActivityPoller.php-ValidateAsset-v2.log
   - ActivityPoller.php-TranscodeAsset-v2.log

Each log file, contains the output of each daemons. To see the stack in action, open four terminals and `tail -f` each log files.

<br>

<p>
<h4><a href="use-the-stack.html">Next: Pilot the stack</a></h4>
</p>
