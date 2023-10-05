<?php
//Get Windows User Credential
$user_username = isset($_POST['user_username']) ? trim($_POST['user_username']) : '';
$user_password = isset($_POST['user_password']) ? trim($_POST['user_password']) : '';

//Variable
	$ldap_domain = "MyCompany.fr";
	$ldap_user = $user_username;
	$ldap_base_dn = 'DC=MyCompany,DC=fr';

//Array containing all authorized group DistinguishedName (separate by ,)
$allowedGroups = array(
		'CN=MyGroup1,OU=GROUPS,DC=MyCompany,DC=fr'
    );
//Function
function getUserGroupsInRecursive($ldap_connection, $userGroups, &$foundGroups = []) {
    foreach ($userGroups as $CurrentGroupDN) {
        if (!in_array($CurrentGroupDN, $foundGroups)) {
            $foundGroups[] = $CurrentGroupDN;
            
            //Recursive search
            $search_filter = '(&(objectCategory=group)(distinguishedName=' . $CurrentGroupDN . '))';
            $result = ldap_search($ldap_connection, $CurrentGroupDN, $search_filter);

            if ($result) {
                $entries = ldap_get_entries($ldap_connection, $result);
				
				$current_group_memberof = $entries[0]['memberof'];
				
				$currentgroupsofGroups = [];
				for ($i = 0; $i < $current_group_memberof['count']; $i++) {
					$currentgroupsofGroups[] = $current_group_memberof[$i];
					$foundGroups[] = $current_group_memberof[$i];
					
					getUserGroupsInRecursive($ldap_connection, $currentgroupsofGroups,$foundGroups);
				}
            }
        }
    }
    return $foundGroups;
}

//Program
try{
	if (empty($user_username)){ throw new Exception('Empty Credentials');}
		
	//Connect to LDAP AD
	$ldap_connection = ldap_connect($ldap_domain);
		
	//LDAP Connexion options
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
		
	if (!$ldap_connection) {throw new Exception('AD Connection failed');}
	
	//Bin ldap connexion with current user credentials
	if (ldap_bind($ldap_connection, "$ldap_user@$ldap_domain", $user_password)) {
		//Check if current user are in authorized group or sub group
		$userIsInAllowedGroup = false;
		
		//Get AD USer object
		$ldap_search_filter = '(&(objectCategory=person)(sAMAccountName='.$ldap_user.'))';
		$ldap_results = ldap_search($ldap_connection, $ldap_base_dn, $ldap_search_filter);
		$ldap_entries = ldap_get_entries($ldap_connection, $ldap_results);
		
		if ($ldap_entries['count']==1){	
			$ldap_user_memberof = $ldap_entries[0]['memberof'];
			
			$userGroups = [];
			for ($i = 0; $i < $ldap_user_memberof['count']; $i++) {
				$userGroups[] = $ldap_user_memberof[$i];
			}
			
			// Get recursive group for the user
			$globalArrayGroup = array(); //Initialize the reference array passing to the function.
			$globalArrayGroup = getUserGroupsInRecursive($ldap_connection, $userGroups, $globalArrayGroup);
			sort($globalArrayGroup);
			
			//Debugging
			//$totalgroup = implode("<br>", $globalArrayGroup);
			//throw new Exception("Forbidden access")
			
			$commonValueIntoArray = array_intersect($allowedGroups, $globalArrayGroup);
			
			if (!empty($commonValueIntoArray)) { //if one or more group match between all user group and authorized group			
				
				$LDAP_LastName = "";
				if (!empty($ldap_entries[0]['sn'])) {
					$LDAP_LastName = $ldap_entries[0]['sn'][0];
					if ($LDAP_LastName == "NULL"){
						$LDAP_LastName = "";
					}
				}
				
				$LDAP_FirstName = "";
				if (!empty($ldap_entries[0]['givenname'][0])) {
					$LDAP_FirstName = $$ldap_entries[0]['givenname'][0];
					if ($LDAP_FirstName == "NULL"){
						$LDAP_FirstName = "";
					}
				}
				
				//Configurate session cookie
				ini_set('session.cookie_lifetime', 0); //Session expire when browser closed
				ini_set('session.use_strict_mode', 1); //Using strict mode
				
				//Session start
				session_start();
				$_SESSION['Authenticate'] = true;
				$_SESSION['CurrentUser'] = $LDAP_LastName. ' ' .$LDAP_FirstName ;
				
				$response = array('status' => 'success', 'message' => 'Acces Granted');
				
			} else {
				throw new Exception("Forbidden Access");
			}
		}else{
			throw new Exception('No entry found');
		}		
	} else {
		throw new Exception('Incorrect credentials');
	}
		
}catch(Exception $e){
	$response = array('status' => 'error', 'message' => $e->getMessage());
}finally{
	ldap_unbind($ldap_connection);
	echo json_encode($response);
}
?>