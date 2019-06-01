# `Laminas\Amazon\Sqs`

[Amazon Simple Queue Service (Amazon SQS)](https://aws.amazon.com/sqs/)
offers a reliable, highly scalable, hosted queue for storing messages as
they travel between computers. By using Amazon SQS, developers can
simply move data between distributed components of their applications
that perform different tasks, without losing messages or requiring each
component to be always available. Amazon SQS makes it easy to build an
automated workflow, working in close conjunction with the Amazon Elastic
Compute Cloud (Amazon EC2) and the other AWS infrastructure web services.

Amazon SQS works by exposing Amazon's web-scale messaging infrastructure
as a web service. Any computer on the Internet can add or read messages
without any installed software or special firewall configurations.
Components of applications using Amazon SQS can run independently, and
do not need to be on the same network, developed with the same
technologies, or running at the same time.

## Registering with Amazon SQS

Before you can get started with `Laminas\Amazon\Sqs`, you must first
register for an account. Please see the [SQS FAQ](https://aws.amazon.com/sqs/faqs/)
page on the Amazon website for more information.

After registering, you will receive an application key and a secret key.
You will need both to access the SQS service.

## API Documentation

The `Laminas\Amazon\Sqs` class provides the *PHP* wrapper to the
Amazon SQS REST interface. Please consult the [Amazon SQS
documentation](https://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Welcome.html)
for detailed description of the service. You will need to be familiar
with basic concepts in order to use this service.

## Features

`Laminas\Amazon\Sqs` provides the following functionality:

- A single point for configuring your amazon.sqs authentication
  credentials that can be used across the amazon.sqs namespaces.
- A proxy object that is more convenient to use than an HTTP client
  alone, mostly removing the need to manually construct HTTP POST
  requests to access the REST service.
- A response wrapper that parses each response body and throws an
  exception if an error occurred, alleviating the need to repeatedly
  check the success of many commands.
- Additional convenience methods for some of the more common
  operations.

## Getting Started

Once you have registered with Amazon SQS, you're ready to create your
queue and store some messages on SQS. Each queue can contain unlimited
amount of messages, identified by name.

The following example demonstrates creating a queue, storing and
retrieving messages.

### Usage Example

```php
$sqs = new Laminas\Amazon\Sqs($myAwsKey, $myAwsSecretKey);

$queueUrl = $sqs->create('test');

$message = 'this is a test';
$messageId = $sqs->send($queueUrl, $message);

foreach ($sqs->receive($queueUrl) as $message) {
    echo $message['body'] . '<br/>';
}
```

Since the `Laminas\Amazon\Sqs` service requires authentication, you
should pass your credentials (AWS key and secret key) to the
constructor. If you only use one account, you can set default
credentials for the service:

```php
Laminas\Amazon\Sqs::setKeys($myAwsKey, $myAwsSecretKey);
$sqs = new Laminas\Amazon\Sqs();
```

## Queue operations

All messages SQS are stored in queues. A queue has to be created before
any message operations. Queue names must be unique under your access key
and secret key.

Queue names can contain lowercase letters, digits, periods (`.`),
underscores (`_`), and dashes (`-`). No other symbols allowed. Queue names
can be a maximum of 80 characters.

- `create()` creates a new queue.
- `delete()` removes all messages in the queue.

  **Queue Removal Example**

  ```php
  $sqs = new Laminas\Amazon\Sqs($myAwsKey, $myAwsSecretKey);
  $queueUrl = $sqs->create('test_1');
  $sqs->delete($queueUrl);
  ```

- `count()` gets the approximate number of messages in the queue.

  **Queue Count Example**

  ```php
  $sqs = new Laminas\Amazon\Sqs($myAwsKey, $myAwsSecretKey);
  $queueUrl = $sqs->create('test_1');
  $sqs->send($queueUrl, 'this is a test');
  $count = $sqs->count($queueUrl); // Returns '1'
  ```

- `getQueues()` returns the list of the names of all queues belonging to the user.

  **Queue Listing Example**

  ```php
  $sqs = new Laminas\Amazon\Sqs($myAwsKey, $myAwsSecretKey);
  $list = $sqs->getQueues();
  foreach ($list as $queue) {
     echo "I have queue $queue\n";
  }
  ```

## Message operations

After a queue is created, simple messages can be sent into the queue
then received at a later point in time. Messages can be up to 8KB in
length. If longer messages are needed please see
[S3](https://docs.laminas.dev/laminas-amazon/s3/).
There is no limit to the number of messages a queue can contain.

- `sent($queueUrl, $message)` send the `$message` to the `$queueUrl`
  SQS queue *URL*.

  **Message Send Example**

  ```php
  $sqs = new Laminas\Amazon\Sqs($myAwsKey, $myAwsSecretKey);
  $queueUrl = $sqs->create('test_queue');
  $sqs->send($queueUrl, 'this is a test message');
  ```

- `receive($queueUrl)` retrieves messages from the queue.

  **Message Receive Example**

  ```php
  $sqs = new Laminas\Amazon\Sqs($myAwsKey, $myAwsSecretKey);
  $queueUrl = $sqs->create('test_queue');
  $sqs->send($queueUrl, 'this is a test message');
  foreach ($sqs->receive($queueUrl) as $message) {
      echo 'got message ' . $message['body'] . '<br/>';
  }
  ```

- `deleteMessage($queueUrl, $handle)` deletes a message from a queue.
  A message must first be received using the `receive()` method before
  it can be deleted.

  **Message Delete Example**

  ```php
  $sqs = new Laminas\Amazon\Sqs($myAwsKey, $myAwsSecretKey);
  $queueUrl = $sqs->create('test_queue');
  $sqs->send($queueUrl, 'this is a test message');
  foreach ($sqs->receive($queueUrl) as $message) {
      echo 'got message ' . $message['body'] . '<br/>';

      if ($sqs->deleteMessage($queueUrl, $message['handle'])) {
          echo 'Message deleted';
      } else {
          echo 'Message not deleted';
      }
  }
  ```
