# laminas-google-gcm

> On April 10, 2018, Google deprecated GCM. The GCM server and client APIs were removed on May 29, 2019.
> Migrate GCM apps to [Firebase Cloud Messaging (FCM)](https://firebase.google.com/docs/cloud-messaging/),
> which inherits the reliable and scalable GCM infrastructure, plus many new features.
> See the [migration guide](https://developers.google.com/cloud-messaging/android/android-migrate-fcm) to
> learn more.

`Laminas\Google\Gcm` provides a client for the Google Cloud Messaging (GCM) API.
`Laminas\Google\Gcm\Client` allows you to send data from servers to your Android Applications
on Android devices (Google API driven).

In order to leverage GCM you **must** create your project in the Google API Console and enable the GCM service
on your device. To get started with GCM prior to building out the 3rd-party server please see
[GCM: Getting Started](https://developers.google.com/cloud-messaging/).

The service is composed of 3 distinct parts:

- The Client: `Laminas\Google\Gcm\Client`
- The Message: `Laminas\Google\Gcm\Message`
- The Response: `Laminas\Google\Gcm\Response`

The Client is the broker that sends the message to the GCM server and returns the response. The Message
is where you define all of the message specific data that you would like to send. The response is the feedback
given back from the GCM server on success, failures and any new canonical id's that must be updated.

## Quick Start

In order to send messages; you must have your API key ready and available. Here we will setup the client and
prepare ourselves to send out messages.

```php
use Laminas\Google\Gcm\Client;
use Laminas\Google\Gcm\Message;
use Laminas\Google\Exception\RuntimeException;

$client = new Client();
$client->setApiKey('the-api-key-for-gcm');
```

So now that we have the client setup and available, it is time to define out the message that we intend to
send to our registration id's that have registered for push notifications on our server. Note that many of
the methods specified are not required but are here to give an inclusive look into the message.

```php
$message = new Message();

// up to 100 registration ids can be sent to at once
$message->setRegistrationIds([
    '1-an-id-from-gcm',
    '2-an-id-from-gcm',
]);

// optional fields
$message->setData([
    'pull-request' => '1',
]);
$message->setCollapseKey('pull-request');
$message->setRestrictedPackageName('com.zf.manual');
$message->setDelayWhileIdle(false);
$message->setTimeToLive(600);
$message->setDryRun(false);
```

Now that we have the message taken care of, all we need to do next is send out the message. Each message
comes back with a set of data that allows us to understand what happened with our push notification as well
as throwing exceptions in the cases of server failures.

```php
try {
    $response = $client->send($message);
} catch (RuntimeException $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}
echo 'Successful: ' . $response->getSuccessCount() . PHP_EOL;
echo 'Failures: ' . $response->getFailureCount() . PHP_EOL;
echo 'Canonicals: ' . $response->getCanonicalCount() . PHP_EOL;
```
