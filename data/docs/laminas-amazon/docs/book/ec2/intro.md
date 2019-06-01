# `Laminas\Amazon\Ec2`

`Laminas\Amazon\Ec2` provides an interface to Amazon Elastic Cloud Computing (EC2).

## What is Amazon Ec2?

Amazon EC2 is a web service that enables you to launch and manage server
instances in Amazon's data centers using APIs or available tools and
utilities. You can use Amazon EC2 server instances at any time, for as
long as you need, and for any legal purpose.

## Static Methods

To make using the Ec2 class easier to use there are two static methods
that can be invoked from any of the Ec2 Elements. The first static
method is `setKeys` which will defind you AWS Access Keys as default
keys. When you then create any new object you don't need to pass in any
keys to the constructor.

### `setKeys()` Example

```php
Laminas\Amazon\Ec2\Ebs::setKeys('aws_key', 'aws_secret_key');
```

To set the region that you are working in you can call the `setRegion`
to set which Amazon Ec2 Region you are working in. Currently there is
only two region available us-east-1 and eu-west-1. If an invalid value
is passed it will throw an exception stating that.

### `setRegion()` Example

```php
Laminas\Amazon\Ec2\Ebs::setRegion('us-east-1');
```

> #### Set Amazon Ec2 Region
>
> Alternatively you can set the region when you create each class as the
> third parameter in the constructor method.
