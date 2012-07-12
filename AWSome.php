<?php

    /*******************************************
    * AWSome.php
    * 
    * Mike Arpaia
    * mike@arpaia.co
    *
    * The only thing you have to do to make this
    * work is set your IAM credentials below and
    * make sure that the path to sdk.class.php 
    * is correct.
    *
    * Usage: php AWSome.php
    ********************************************/
    
    // use your IAM credentials here
    $key = "ADD_YOUR_KEY_HERE";
    $secret = "ADD_YOUR_SECRET_HERE";
    
    // include the sdk.class.php file
    // you can find it in the root 
    // of the AWS PHP SDK
    require_once './sdk-1.5.8.2/sdk.class.php';
    
    // set the credentials
    CFCredentials::set(array(
        'credentials' => array(
            'key' => $key,
            'secret' => $secret,
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
            if ($attr->fromPort == $attr->toPort) { $rule = "$attr->ipProtocol $attr->fromPort from "; }
            else { $rule = "Forwards data from $attr->ipProtocol port $attr->fromPort to $attr->toPort from "; }
            $arr = (array($attr->ipRanges->item->cidrIp));
            $groupName = array($attr->groups->item->groupName);
            if (!is_null($arr[0]) and !is_null($groupName[0])) { $rule .= "$arr[0] and $groupName[0]"; }
            else if (!is_null($arr[0]) and is_null($groupName[0])) { $rule .= "$arr[0]"; }
            else if (!is_null($groupName[0]) and is_null($arr[0])) { $rule .= "$groupName[0]"; }
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
    // enumerate instances
    foreach ($ins->body->reservationSet->item as $attr) {
        //instantiate a temp array to build instance information
        $instance = array();
        $arr = array($attr->instancesSet->item);
        $arr = $arr[0];
        $instance['instanceId'] = $arr->instanceId;
        $instance['ami'] = $arr->imageId;
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
    
    function stdOutput($securityGroups, $instances, $keys){
        // this function builds the output that is echo'd to stdout
        //
        // if you want the code to output differently, just
        // add a function and call it at the end of the script
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
                if (((string) $instance['securityGroupName'][0]) === ((string) $group['groupName'])) {
                    $i = true;
                    echo "    [+] Instance ID:            " . $instance['instanceId'] . "\n";
                    if ($instance['publicIp'] != '') { 
                        echo "        [-] Instance Status:    Instance is up.\n";
                        echo "        [-] Public IP Address:  " . $instance['publicIp'] . "\n";
                        array_push($ips, $instance['publicIp']);
                        echo "        [-] Public DNS Name:    " . $instance['dns'] . "\n";
                        echo "        [-] Private IP Address: " . $instance['privateIp'] . "\n";
                        echo "        [-] Private DNS Name:   " . $instance['privateDns'] . "\n";
                    } else{ echo "        [!] Instance Status:    Instance is stopped.\n"; }
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
            if (!$i) { echo "    [!] There are no instances in this security group.\n"; }
            if ($i) { 
                echo "--------------------------------------------------------------------------------\n";
                echo "  IP addresses of instances in this security group\n";
                echo "--------------------------------------------------------------------------------\n";
                foreach ($ips as $ip) { echo "$ip\n"; }
            }
            echo "================================================================================\n\n";
        }
    }
    
    // this takes the arrays that have been generated and passes them 
    // to the function that displays the plain text output to stdout
    //
    // if you want the code to output differently, just
    // add a function and call it here
    stdOutput($securityGroups, $instances, $keys);

?>