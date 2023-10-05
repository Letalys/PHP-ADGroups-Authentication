
# PHP : Authenticate with AD Group

I created this script in order to be able to perform access checks based on Active Directory groups for people trying to connect to an internal administration WEB page.

It was simple to go up the list of user groups, but I had to do a recursive function to find if the groups they belonged to were themselves part of the groups authorized to connect.

## How to use

### Apache Module

You need this module active on your apache server

`mod_ldap` `php-ldap`


### Script configuration

This script is to be used as an ajax request and returns a JSON object

You need to change the settings according to your AD structure into `auth_process.php`

```
//Variable
	$ldap_domain = "MyCompany.fr";
	$ldap_user = $user_username;
	$ldap_base_dn = 'DC=MyCompany,DC=fr';

//Array containing all authorized group DistinguishedName (separate by ,)
$allowedGroups = array(
		'CN=MyGroup1,OU=GROUPS,DC=MyCompany,DC=fr'
    );
```

Adapt the `ajax-login.js` to put your own process in success/error.

## Links
https://github.com/Letalys/PHP-ADGroups-Authentication


## Autor
- [@Letalys (GitHUb)](https://www.github.com/Letalys)
