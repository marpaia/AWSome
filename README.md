AWSome
======

AWSome is a tool for penetration testing AWS EC2 configurations.

Dependencies
------------

### To use AWSome, you need the following:                                      
* AWS PHP SDK (You can download the AWS PHP SDK from http://aws.amazon.com/sdkforphp/)
* Appropriate IAM Credentials                                           

Tips
----
### Auth

* If you're going to use AWSome more than a few times, you should hard code the
path to the SDK file and your AWS IAM credentials into AWSome
* You can find that the variables $sdkClassPhp, $k, and $s all get defined at
the begining of the file. Adjust those variable based on your usage
* If you're less interested in hard coding the variables yourself see the usage

Usage
----
`--verbose` or `-v` gives you more information about each instance              

	php AWSome.php -v

`--ip` or `-i` adds a list of each IP associated with a security group. This is useful for feeding IPs to additional tools

	php AWSome.php --ip

`--help` or `-h` shows you the help text

	php AWSome.php --help

`--config` or `-c` let's you define a config file that has the IAM key on the first line, the IAM secret on the next line and the path to the sdk.class.php file on the last (third) line. If you don't want to hard code your IAM credentials, use this option 	

	php AWSome.php -cMyConfig.txt
    php AWSome.php --config MyConfig.txt                  

`--ami` or `-a` returns each AMI and the IP addresses associated with it. This is useful if you identify a vulnerable AMI while testing

	php AWSome.php --ami

Contact                                                                   
-------

Mike Arpaia                                                             

mike@arpaia.co

http://arpaia.co
