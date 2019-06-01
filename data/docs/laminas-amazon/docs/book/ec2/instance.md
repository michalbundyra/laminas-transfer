# Instances

Amazon EC2 instances are grouped into two families: standard and
High-CPU. Standard instances have memory to CPU ratios suitable for
most general purpose applications; High-CPU instances have
proportionally more CPU resources than memory (RAM) and are well
suited for compute-intensive applications. When selecting instance
types, you might want to use less powerful instance types for your web
server instances and more powerful instance types for your database
instances. Additionally, you might want to run CPU instance types for
CPU-intensive data processing tasks.

One of the advantages of EC2 is that you pay by the instance hour, which
makes it convenient and inexpensive to test the performance of your
application on different instance families and types. One good way to
determine the most appropriate instance family and instance type is to
launch test instances and benchmark your application.

> #### Instance Types
>
> The instance types are defined as constants in the code. Column eight
> in the table is the defined constant name

Type                 | CPU                                                                    | Memory | Storage                                                          | Platform | I/O      | Name      | Constant Name
-------------------- | ---------------------------------------------------------------------- | ------ | ---------------------------------------------------------------- | -------- | -------- | --------- | -------------
Small                | 1 EC2 Compute Unit (1 virtual core with 1 EC2 Compute Unit)            | 1.7 GB | 160 GB instance storage (150 GB plus 10 GB root partition)       | 32=bit   | Moderate | m1.small  | `Laminas\Amazon\Ec2Instance::SMALL`
Large                | 4 EC2 Compute Units (2 virtual cores with 2 EC2 Compute Units each)    | 7.5 GB | 850 GB instance storage (2 x 420 GB plus 10 GB root partition)   | 64-bit   | High     | m1.large  | `Laminas\Amazon\Ec2Instance::LARGE`
Extra Large          | 8 EC2 Compute Units (4 virtual cores with 2 EC2 Compute Units each)    | 15 GB  | 1,690 GB instance storage (4 x 420 GB plus 10 GB root partition) | 64-bit   | High     | m1.xlarge | `Laminas\Amazon\Ec2Instance::XLARGE`
High-CPU Medium      | 5 EC2 Compute Units (2 virtual cores with 2.5 EC2 Compute Units each)  | 1.7 GB | 350 GB instance storage (340 GB plus 10 GB root partition)       | 32-bit   | Moderate | c1.medium | `Laminas\Amazon\Ec2Instance::HCPU_MEDIUM`
High-CPU Extra Large | 20 EC2 Compute Units (8 virtual cores with 2.5 EC2 Compute Units each) | 7 GB   | 1,690 GB instance storage (4 x 420 GB plus 10 GB root partition) | 64-bit   | High     | c1.xlarge | `Laminas\Amazon\Ec2Instance::HCPU_XLARGE`

## Running Amazon EC2 Instances

This section describes the operation methods for maintaining Amazon EC2
Instances.

### Starting New Ec2 Instances

`run` will launch a specified number of EC2 Instances. `run` takes an
array of parameters to start, below is a table containing the valid
values.

**Valid Run Options**

Name                     | Description                                                                                                                      | Required
------------------------ | -------------------------------------------------------------------------------------------------------------------------------- | --------
`imageId`                | ID of the AMI with which to launch instances.                                                                                    | Yes
`minCount`               | Minimum number of instances to launch. Default: 1                                                                                | No
`maxCount`               | Maximum number of instances to launch. Default: 1                                                                                | No
`keyName`                | Name of the key pair with which to launch instances. If you do not provide a key, all instances will be inaccessible.            | No
`securityGroup`          | Names of the security groups with which to associate the instances.                                                              | No
`userData`               | The user data available to the launched instances. This should not be Base64 encoded.                                            | No
`instanceType`           | Specifies the instance type. Default: m1.small                                                                                   | No
`placement`              | Specifies the availability zone in which to launch the instance(s). By default, Amazon EC2 selects an availability zone for you. | No
`kernelId`               | The ID of the kernel with which to launch the instance.                                                                          | No
`ramdiskId`              | The ID of the RAM disk with which to launch the instance.                                                                        | No
`blockDeviceVirtualName` | Specifies the virtual name to map to the corresponding device name. For example: instancestore0                                  | No
`blockDeviceName`        | Specifies the device to which you are mapping a virtual name. For example: sdb                                                   | No
`monitor`                | Turn on AWS CloudWatch Instance Monitoring                                                                                       | No

`run` will return information about each instance that is starting up.

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->run([
    'imageId' => 'ami-509320',
    'keyName' => 'myKey',
    'securityGroup' => ['web', 'default'],
]);
```

### Rebooting an Ec2 Instances

`reboot` will reboot one or more instances.

This operation is asynchronous; it only queues a request to reboot the
specified instance(s). The operation will succeed if the instances are
valid and belong to the user. Requests to reboot terminated instances
are ignored.

`reboot` returns boolean `true` or `false`

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->reboot('instanceId');
```

### Terminating an Ec2 Instances

`terminate` shuts down one or more instances. This operation is
idempotent; if you terminate an instance more than once, each call will
succeed.

`terminate` returns boolean `true` or `false`

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->terminate('instanceId');
```

> #### Terminated Instances
>
> Terminated instances will remain visible after termination
> (approximately one hour).

## Amazon Instance Utilities

In this section you will find out how to retrieve information, the
console output and see if an instance contains a product code.

### Describing Instances

`describe` returns information about instances that you own.

If you specify one or more instance IDs, Amazon EC2 returns information
for those instances. If you do not specify instance IDs, Amazon EC2
returns information for all relevant instances. If you specify an
invalid instance ID, a fault is returned. If you specify an instance
that you do not own, it will not be included in the returned results.

`describe` will return an array containing information on the instance.

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->describe('instanceId');
```

> #### Terminated Instances
>
> Recently terminated instances might appear in the returned results.
> This interval is usually less than one hour. If you do not want
> terminated instances to be returned, pass in a second variable of
> boolean `true` to `describe` and the terminated instances will be
> ignored.

### Describing Instances By Image Id

`describeByImageId` is functionally the same as `describe` but it will
only return the instances that are using the provided imageId.

`describeByImageId` will return an array containing information on the
instances there were started by the passed in imageId

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->describeByImageId('imageId');
```

> #### Terminated Instances
>
> Recently terminated instances might appear in the returned results.
> This interval is usually less than one hour. If you do not want
> terminated instances to be returned, pass in a second variable of
> boolean `true` to `describe` and the terminated instances will be
> ignored.

### Retrieving Console Output

`consoleOutput` retrieves console output for the specified instance.

Instance console output is buffered and posted shortly after instance
boot, reboot, and termination. Amazon EC2 preserves the most recent 64
KB output which will be available for at least one hour after the most
recent post.

`consoleOutput` returns an array containing the `instanceId`,
`timestamp` from the last output and the `output` from the console.

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->consoleOutput('instanceId');
```

### Confirm Product Code on an Instance

`confirmProduct` returns `true` if the specified product code is
attached to the specified instance. The operation returns `false` if the
product code is not attached to the instance.

The `confirmProduct` operation can only be executed by the owner of the
AMI. This feature is useful when an AMI owner is providing support
and wants to verify whether a user's instance is eligible.

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->confirmProduct('productCode', 'instanceId');
```

### Turn on CloudWatch Monitoring on an Instance(s)

`monitor` returns the list of instances and their current state of the
CloudWatch Monitoring. If the instance does not currently have
Monitoring enabled it will be turned on.

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->monitor('instanceId');
```

### Turn off CloudWatch Monitoring on an Instance(s)

`unmonitor` returns the list of instances and their current state of the
CloudWatch Monitoring. If the instance currently has Monitoring enabled
it will be turned off.

```php
$ec2Instance = new Laminas\Amazon\Ec2\Instance('aws_key', 'aws_secret_key');
$return = $ec2Instance->unmonitor('instanceId');
```
