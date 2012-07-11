<?php

    /*******************************************
    * AWSome.php
    * 
    * Mike Arpaia
    * mike@arpaia.co
    ********************************************/
    
    require_once './sdk-1.5.8.2/sdk.class.php';

    $key = "ADD_YOUR_KEY_HERE";
    $secret = "ADD_YOUR_SECRET_HERE";
    
    CFCredentials::set(array(
        'credentials' => array(
            'key' => $key,
            'secret' => $secret,
            'default_cache_config' => '',
            'certificate_authority' => false
        ),
        '@default' => 'credentials'
    ));
    
    $ec2 = new AmazonEC2();
    
    /******************************************
    * Getting information about security groups
    *******************************************/
    // gather information about security groups
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
    
    // build the output
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
        foreach ($instances as $instance) {
            if (((string) $instance['securityGroupName'][0]) === ((string) $group['groupName'])) {
                echo "--------------------------------------------------------------------------------\n";
                echo "    [+] Instance ID:            " . $instance['instanceId'] . "\n";
                echo "        [-] Public IP Address:  " . $instance['publicIp'] . "\n";
                echo "        [-] Public DNS Name:    " . $instance['dns'] . "\n";
                echo "        [-] Private IP Address: " . $instance['privateIp'] . "\n";
                echo "        [-] Private DNS Name:   " . $instance['privateDns'] . "\n";
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
        echo "================================================================================\n\n";
    }

?>