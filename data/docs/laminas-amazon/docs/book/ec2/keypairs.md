# Keypairs

Keypairs are used to access instances.

## Creating a new Amazon Keypair

`create`, creates a new 2048 bit RSA key pair and returns a unique ID
that can be used to reference this key pair when launching new
instances.

`create` returns an array which contains the keyName, keyFingerprint and
keyMaterial.

```php
$ec2Kp = new Laminas\Amazon\Ec2\Keypair('aws_key', 'aws_secret_key');
$return = $ec2Kp->create('my-new-key');
```

## Deleting an Amazon Keypair

`delete`, will delete the key pair. This will only prevent it from being
used with new instances. Instances currently running with the keypair
will still allow you to access them.

`delete` returns boolean `true` or `false`

```php
$ec2Kp = new Laminas\Amazon\Ec2\Keypair('aws_key', 'aws_secret_key');
$return = $ec2Kp->delete('my-new-key');
```

## Describe an Amazon Keypair

`describe` returns information about key pairs available to you. If you
specify key pairs, information about those key pairs is returned.
Otherwise, information for all registered key pairs is returned.

`describe` returns an array which contains keyName and keyFingerprint

```php
$ec2Kp = new Laminas\Amazon\Ec2\Keypair('aws_key', 'aws_secret_key');
$return = $ec2Kp->describe('my-new-key');
```
