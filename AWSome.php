<?php

    /*******************************************
    * AWSome.php
    * 
    * Mike Arpaia
    * mike@arpaia.co
    ********************************************/
    
    // include the path to the sdk.class.php file
    // you can find it in the root of the AWS PHP SDK
    $sdkClassPhp = "./sdk/sdk.class.php";
    
    // set your IAM credentials here
    $k = "key";
    $s = "secret";

    // the function that prints the help 
    // and then stops execution
    function help(){
        echo "
===============================================================================
=                                                                             =
=               Oo    o          `O .oOOOo.                                   =
=              o  O   O           o o     o                                   =
=             O    o  o           O O.                                        =
=            oOooOoOo O           O  `OOoo.                                   =
=            o      O o     o     o       `O .oOo. `oOOoOO. .oOo.             =
=            O      o O     O     O        o O   o  O  o  o OooO'             =
=            o      O `o   O o   O' O.    .O o   O  o  O  O O                 =
=            O.     O  `OoO' `OoO'   `oooO'  `OoO'  O  o  o `OoO'             =
=                                                                             =
===============================================================================
=                                                                             =
= To use AWSome, you need the following:                                      =
=     * AWS PHP SDK                                                           =
=       You can download the AWS PHP SDK from                                 =
=       http://aws.amazon.com/sdkforphp/                                      =
=                                                                             =
=     * Appropriate IAM Credentials                                           =
=                                                                             =
= If you're going to use AWSome more than a few times, you should hard code   =
= the path to the SDK file and your AWS IAM credentials into AWSome           =
=                                                                             =
= You can find that the variables \$sdkClassPhp, \$k, and \$s all get            =
= defined at the begining of the file. Adjust those variable based on usage   =
=                                                                             =
= If you're less interested in hard coding the variables yourself see the     =
= usage section of this help menu                                             =
=                                                                             =
=                                                                             =
===============================================================================
=                                                                             =
= Usage:                                                                      =
=     php AWSome.php -v or --verbose                                          =
=     -v gives you more information about each instance                       =
=                                                                             =
=     php AWSome.php --ip or -i                                               =
=      -ip adds a <CR> deliminated list of each IP associated with a          =
=      security group. this is useful for feeding IPs to additional tools     =
=                                                                             =
=     php AWSome.php --help or -h                                             =
=      -h shows you the help that you're reading now                          =
=                                                                             =
=     php AWSome.php -cMyConfig.txt or --config MyConfig.txt                  =
=      -c let's you define a config file that has the IAM key on the first    =
=      line, the IAM secret on the next line and the path to the              =
=      sdk.class.php file on the last (third) line. If you don't want to hard = 
=      code your IAM credentials, use this option                             =
=                                                                             =
=     php AWSome.php --ami or -a                                              =
=      --ami returns each AMI and the IP addresses associated with it in a    =
=      <CR> deliminated list. This is useful if you identify a vulnerable AMI =
=      while testing                                                          =
=                                                                             =
===============================================================================
=                                                                             =
= Contact:                                                                    =
=     Mike Arpaia                                                             =
=     mike@arpaia.co                                                          =
=                                                                             =
===============================================================================\n";
        die();
    }
    
    // this file reads the config file that
    // is passed to the --config or -c option
    // and returns the relevant information
    function readConfig($config){
        //try opening the file
        try { $c = fopen("$config", 'r'); }
        catch (Exception $e){ die("Couln't open supplied config file.\n"); }
        
        // get the relevant data out of the file
        $k = trim((string)fgets($c), "\n");
        $s = trim((string)fgets($c), "\n");
        $sdkClassPhp = trim((string)fgets($c), "\n");
        return array($k, $s, $sdkClassPhp);
    }
    
    // argument parsing
    $shortopts = "vhic:a";
    $longopts = array(
        "verbose" , 
        "help" ,
        "ip" ,
        "config:" ,
        "ami"
    );
    $opts = getopt($shortopts, $longopts);
    $verbose = false;
    $ipDisplay = false;
    $amiEnum = false;
    foreach ($opts as $key => $value) {
        if ($key == "v" or $key == "verbose") { $verbose = true; }
        if ($key == "i" or $key == "ip") { $ipDisplay = true; }
        if ($key == "h" or $key == "help") { help(); }
        if ($key == "c" or $key == "config") { 
            $config = readConfig($value); 
            $k = $config[0];
            $s = $config[1];
            $sdkClassPhp = $config[2];
        }
        if ($key == "a" or $key == "ami") { $amiEnum = true; }
    }
    $options = array(
        $verbose ,
        $ipDisplay , 
        $amiEnum
    );    

    // attempting to require_once the SDK file    
    if (file_exists($sdkClassPhp)) { require_once($sdkClassPhp); } 
    else { die("AWSome can't find the SDK\n"); }

    // set the credentials
    CFCredentials::set(array(
        'credentials' => array(
            'key' => $k,
            'secret' => $s,
            'default_cache_config' => '',
            'certificate_authority' => false
        ),
        '@default' => 'credentials'
    ));
    
    // instantiate the AmazonEC2 object
    $ec2 = new AmazonEC2();
    
    /******************************************
    * Getting information about security groups
    *******************************************/
    // gather information about security groups
    // and store it in $securityGroups
    $sg = $ec2->describe_security_groups();
    $securityGroups = array();
    if ($sg->status == 401) { 
        die("Received a HTTP 401 error. Check your credentials.\n");
    }
    foreach ($sg->body->securityGroupInfo->item as $attr) {
        // instantiate a temp array
        $array = array();
        // enumerate security group properties
        // add security group properties to the temp array
        $array['groupName'] = $attr->groupName;
        $array['groupId'] = $attr->groupId;
        $array['groupDescription'] = $attr->groupDescription;

        $rules = array();
        // enumerate rules
        foreach ($attr->ipPermissions->item as $attr) {
            // build each rule as a string
            $rule = "$attr->ipProtocol port $attr->fromPort-$attr->toPort from ";
            $arr = (array($attr->ipRanges->item->cidrIp));
            $groupName = array($attr->groups->item->groupName);
            if (!is_null($arr[0]) and !is_null($groupName[0])) {
                $rule .= "$arr[0] and $groupName[0]"; 
            }
            else if (!is_null($arr[0]) and is_null($groupName[0])) {
                $rule .= "$arr[0]";
            }
            else if (!is_null($groupName[0]) and is_null($arr[0])) {
                $rule .= "$groupName[0]"; 
            }
            // push each rule onto the general rules array
            array_push($rules, $rule);
        }
        // add the rules array to the temp array
        $array['rules'] = $rules;
        // once the security group information has been gathered,
        // add it as a nested array to the securityGroups array
        array_push($securityGroups, $array);
    }
    
    /************************************
    * Getting information about instances
    *************************************/
    // gather information about instances
    // and store it in $instances
    $ins = $ec2->describe_instances();
    $instances = array();
    $uniqueAmis = array();
    // enumerate instances
    foreach ($ins->body->reservationSet->item as $attr) {
        //instantiate a temp array to build instance information
        $instance = array();
        $arr = array($attr->instancesSet->item);
        $arr = $arr[0];
        $instance['instanceId'] = $arr->instanceId;
        $instance['ami'] = $arr->imageId;
        $instance['state'] = (string)$arr->instanceState->name[0];
        if (!in_array((string)$arr->imageId[0], $uniqueAmis)) {
            array_push($uniqueAmis, (string)$arr->imageId[0]);
        }
        $instance['privateDns'] = $arr->privateDnsName;
        $instance['privateIp'] = $arr->privateIpAddress;
        $instance['dns'] = $arr->dnsName;
        $instance['publicIp'] = $arr->ipAddress;
        $instance['sshKey'] = $arr->keyName;
        $instance['kernelId'] = $arr->kernelId;
        $instance['architecture'] = $arr->architecture;
        $instance['hypervisor'] = $arr->hypervisor;
        $sg = array($attr->groupSet->item);
        $sg = $sg[0];
        $instance['securityGroupId'] = $sg->groupId;
        $instance['securityGroupName'] = $sg->groupName;
        // once the instance information has been gathered,
        // add it as a nested array to the instances array
        array_push($instances, $instance);
    }
    
    /*******************************
    * Getting information about keys
    ********************************/
    // gather information about key pairs
    // and store it in $keys
    $k = $ec2->describeKeyPairs();
    $keys = array();
    // enumerate keys
    foreach ($k->body->keySet->item as $attr) {
        // instantiate a temp array to build key information
        $key = array();
        $arr = array($attr);
        $arr = $arr[0];
        $key['keyName'] = $arr->keyName;
        $key['keyFingerprint'] = $arr->keyFingerprint;
        // once the ket information has been gathered,
        // add it as a nested array to the kets array
        array_push($keys, $key);
    }

    function stdOutput($securityGroups, $instances, $uniqueAmis, $keys, $options){
        $verbose = $options[0];
        $ipDisplay = $options[1];
        $amiEnum = $options[2];
        // this function builds the output that is echo'd to stdout
        //
        // if you want the code to output differently, just write a
        // different function and call it at the end of the script
        // instead of calling stdOutput
        if ($amiEnum and ($verbose or $ipDisplay)) { die("You can't set --ami with either -v or --ip\n"); }
        if (!$amiEnum) {
            foreach ($securityGroups as $group) {
                echo "================================================================================\n";
                echo "Security Group: " . $group['groupName'] . "\n";
                echo "--------------------------------------------------------------------------------\n";
                echo "[+] Group ID:          " . $group['groupId'] . "\n";
                echo "[+] Group Description: " . $group['groupDescription'] . "\n";
                echo "--------------------------------------------------------------------------------\n";
                echo "  Rules that are defined for " . $group['groupName'] . "\n";
                echo "--------------------------------------------------------------------------------\n";
                foreach ($group['rules'] as $rule) {
                    echo "    [+] Rule:          " . $rule . "\n";
                }
                echo "--------------------------------------------------------------------------------\n";
                echo "  Instances that use this security group:\n";
                echo "--------------------------------------------------------------------------------\n";
                $i = false;
                $ips = array();
                foreach ($instances as $instance) {
                    foreach ($instance['securityGroupName'] as $g) {
                        if (((string) $g) === ((string) $group['groupName'])) {
                            $i = true;
                            echo "    [+] Instance ID:            " . $instance['instanceId'] . "\n";
                            if ($instance['publicIp'] != '') { 
                                echo "        [-] Instance Status:    " . $instance['state'] . "\n";
                                echo "        [-] Public IP Address:  " . $instance['publicIp'] . "\n";
                                array_push($ips, $instance['publicIp']);
                                if ($verbose){ 
                                    echo "        [-] Public DNS Name:    " . $instance['dns'] . "\n";
                                    echo "        [-] Private IP Address: " . $instance['privateIp'] . "\n";
                                    echo "        [-] Private DNS Name:   " . $instance['privateDns'] . "\n"; 
                                }
                            } else{ echo "        [!] Instance Status:    " . $instance['state'] . "\n"; }
                            if ($verbose){ 
                                echo "        [-] Architecture:       " . $instance['architecture'] . "\n";
                                echo "        [-] AMI:                " . $instance['ami'] . "\n";
                                echo "        [-] SSH Key:            " . $instance['sshKey'];
                                // If you want to output the key fingerprint along with the name of the SSH key,
                                // comment out the next line and uncomment the foreach loop
                                echo "\n";
                                //foreach ($keys as $key) {
                                //	if ((string)$key['keyName'] == $instance['sshKey']){ 
                                //        echo " (" . $key['keyFingerprint'] . ")\n"; 
                                //    }
                                //}
                            }
                        }
                    }
                }
                if (!$i) { echo "    [!] There are no instances in this security group.\n"; }
                else if ($i and $ipDisplay) { 
                    echo "--------------------------------------------------------------------------------\n";
                    echo "  IP addresses of instances in this security group\n";
                    echo "--------------------------------------------------------------------------------\n";
                    foreach ($ips as $ip) { echo "$ip\n"; }
                }
                echo "================================================================================\n\n";
            }
        }
            // if $amiEnum is set:
            else {
                foreach ($uniqueAmis as $ami){
                    echo "================================================================================\n";
                    echo "  IP addresses of all instances of $ami\n";
                    echo "--------------------------------------------------------------------------------\n";
                    $i = false;
                    foreach ($instances as $instance){
                        if ((string)$instance['ami'][0] == $ami){
                            if ((string)$instance['publicIp'][0] !== '') { 
                                $i = true;
                                echo $instance['publicIp'] . "\n";
                            }
                        }
                    }
                    if (!$i) { echo "    [!] There are no live instances of this AMI.\n"; }
                    echo "================================================================================\n\n";
            }
        }
    }
    
    // this takes the arrays that have been generated and passes them 
    // to the function that displays the plain text output to stdout
    //
    // if you want the code to output differently, just
    // write a different function and call it here
    stdOutput($securityGroups, $instances, $uniqueAmis, $keys, $options);
    
?>
