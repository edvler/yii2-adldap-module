# yii2-adldap-module

[Yii2](http://www.yiiframework.com) extension for Adldap2 (https://packagist.org/packages/adldap2/adldap2)

[![Latest Stable Version](https://poser.pugx.org/edvlerblog/yii2-adldap-module/v/stable)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Total Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/downloads)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Monthly Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/d/monthly)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![License](https://poser.pugx.org/phpunit/phpunit/license)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)

## Version

Current Version:
yii2-adldap-module Releases beginning with tag v3.*.* are reserved for Adldap2 v7.*
The corresponding Adldap2 repository is https://github.com/Adldap2/Adldap2/tree/master

**Keep this in mind if you are browsing the GitHub Repository of Adldap2**


## Installation

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run
```
php composer.phar require edvlerblog/yii2-adldap-module "^3.0.0"
```
or add
```
"edvlerblog/yii2-adldap-module": "^3.0.0"
```
to the require section of your composer.json


## Configuration

Add this code in your components section of the application configuration (eg. config/main.php for advanced template or config/web.php for basic template):
```php
'components' => [
	//.....
	// other components ...
	//.....
	'ad' => [
	    'class' => 'Edvlerblog\Adldap2\Adldap2Wrapper',

	    /*
	     * Set the default provider to one of the providers defined in the
	     * providers array.
	     *
	     * If this is commented out, the entry 'default' in the providers array is
	     * used.
	     *
	     * See https://github.com/Adldap2/Adldap2/blob/master/docs/connecting.md
	     * Setting a default connection
	     *
	     */
	     // 'defaultProvider' => 'another_provider',

	    /*
	     * Adlapd2 v7.X.X can handle multiple providers to different Active Directory sources.
	     * Each provider has it's own config.
	     *
	     * In the providers section it's possible to define multiple providers as listed as example below.
	     * But it's enough to only define the "default" provider!
	     */
	    'providers' => [
			/*
			 * Always add a default provider!
			 *
			 * You can get the provider with:
			 * $provider = \Yii::$app->ad->getDefaultProvider();
			 * or with $provider = \Yii::$app->ad->getProvider('default');
			 */
			'default' => [ //Providername default
			    // Connect this provider on initialisation of the LdapWrapper Class automatically
			    'autoconnect' => true,

			    // The provider's schema. Default is \Adldap\Schemas\ActiveDirectory set in https://github.com/Adldap2/Adldap2/blob/master/src/Connections/Provider.php#L112
			    // You can make your own https://github.com/Adldap2/Adldap2/blob/master/docs/schema.md or use one from https://github.com/Adldap2/Adldap2/tree/master/src/Schemas
			    // Example to set it to OpenLDAP:
			    // 'schema' => new \Adldap\Schemas\OpenLDAP(),

			    // The config has to be defined as described in the Adldap2 documentation.
			    // https://github.com/Adldap2/Adldap2/blob/master/docs/configuration.md
			    'config' => [
				// Your account suffix, for example: matthias.maderer@example.lan
				'account_suffix'        => '@example.lan',

				// You can use the host name or the IP address of your controllers.
				'domain_controllers'    => ['server01.example.lan', 'server02.example.lan'],

				// Your base DN. This is usually your account suffix.
				'base_dn'               => 'dc=example,dc=lan',

				// The account to use for querying / modifying users. This
				// does not need to be an actual admin account.
				'admin_username'        => 'username_ldap_access',
				'admin_password'        => 'password_ldap_access!',
			    ]
			],

			/*
			 * Another Provider
			 * You don't have to another provider if you don't need it. It's just an example.
			 *
			 * You can get the provider with:
			 * or with $provider = \Yii::$app->ad->getProvider('another_provider');
			 */
			'another_provider' => [ //Providername another_provider
			    // Connect this provider on initialisation of the LdapWrapper Class automatically
			    'autoconnect' => false,

			    // The provider's schema. Default is \Adldap\Schemas\ActiveDirectory set in https://github.com/Adldap2/Adldap2/blob/master/src/Connections/Provider.php#L112
			    // You can make your own https://github.com/Adldap2/Adldap2/blob/master/docs/schema.md or use one from https://github.com/Adldap2/Adldap2/tree/master/src/Schemas
			    // Example to set it to OpenLDAP:
			    // 'schema' => new \Adldap\Schemas\OpenLDAP(),

			    // The config has to be defined as described in the Adldap2 documentation.
			    // https://github.com/Adldap2/Adldap2/blob/master/docs/configuration.md               
			    'config' => [
				// Your account suffix, for example: matthias.maderer@test.lan
				'account_suffix'        => '@test.lan',

				// You can use the host name or the IP address of your controllers.
				'domain_controllers'    => ['server1.test.lan', 'server2'],

				// Your base DN. This is usually your account suffix.
				'base_dn'               => 'dc=test,dc=lan',

				// The account to use for querying / modifying users. This
				// does not need to be an actual admin account.
				'admin_username'        => 'username_ldap_access',
				'admin_password'        => 'password_ldap_access',
			    ] // close config
			], // close provider
	    ], // close providers array
	], //close ad
```

See official documentation for all config options.  
https://github.com/Adldap2/Adldap2/blob/master/docs/configuration.md

## Usage - Method 1 and/or Method 2

### Usage method 1: Simple usage without a user model
If you are need to query some informations for a user from the Active Directory this would be best way.
No additional configuration is needed and the only thing to do is to add the configuration as described above to your components section.

You only call the the component as usual.
```php
//...
$un = 'testuser';

/*
There are three ways available to call Adldap2 function.
If you use more providers (multiple Active Directory connections)
you make one as default and you can call this one with Method1 or Method2
and the second one will be called with Method3.
*/

//Get the Ldap object for the user.
//$ldapObject holds a class of type Adldap\Models\User from the Adldap project!
// Method 1: uses the default provider given in the configuration above (array key defaultProvider)
$ldapObject = \Yii::$app->ad->search()->findBy('sAMAccountname', $un);
// Method 2: uses the default provider given in the configuration above (array key defaultProvider)
$ldapObject = \Yii::$app->ad->getDefaultProvider()->search()->findBy('sAMAccountname', $un);
// Method 3: get the provider by name (here name default is used).
$ldapObject = \Yii::$app->ad->getProvider('default')->search()->findBy('sAMAccountname', $un);

//Examples
//Please note that all fields from ldap are arrays!
//Access it with ..[0] if it is a single value field.
$givenName = $ldapObject['givenname'][0];
$surname = $ldapObject['surname'][0];
$displayname = $ldapObject['displayname'][0];
$telephone = $ldapObject['telephonenumber'][0];

echo 'gn: ' . $givenName . ' sn: ' . $surname . 
 ' dispname: ' . $displayname . ' phone: ' . $telephone;

//Print all possible attributes
echo '<pre>' . print_r($ldapObject,true) . '</pre>';

//...
```

**Further Documentation with examples:** [docs/USAGE_WITHOUT_USER_MODEL.md](docs/USAGE_WITHOUT_USER_MODEL.md)

---

### Usage method 2: Deep integration into the yii2 framework with a user model
The second method gives you the ability to authenticate users against Active Directory with a special user model. It intgerates very well into the RBAC security concept of yii2 (http://www.yiiframework.com/doc-2.0/guide-security-authorization.html#rbac).

You can use all features of the yii2 user integration.

Some Examples:
```php
//...
//Has user a permission?
$hasPermission = \Yii::$app->user->can('permissionDisplayDetailedAbout');


//Query informations from Active Directory. You can use in a controller, a view, everywhere in yii2!
if (!\Yii::$app->user->isGuest) {
    //Get the yii2 identitiy, which was set by the Yii::$app->user->login(..,..) function
    //See model/LoginForm.php in the basic template for the login logic
    $yii2IdentityObject = \Yii::$app->user->identity;
    
    $rolesOfUser = \Yii::$app->authManager->getRolesByUser($yii2IdentityObject->getId());
    echo '<pre>' . print_r($rolesOfUser,true) . '</pre>';
    
    //Get the Ldap object for the user.
    //$ldapObject holds a class of type Adldap\Models\User from the Adldap project!
    //No performance issues, because the queryLdapUserObject function uses a cache.
    $ldapObject = $yii2IdentityObject->queryLdapUserObject();
    
    //Examples
    //Please note that all fields from ldap are arrays!
    //Access it with ..[0] if it is a single value field.
    $givenName = $ldapObject['givenname'][0];
    $surname = $ldapObject['surname'][0];
    $displayname = $ldapObject['displayname'][0];
    $telephone = $ldapObject['telephonenumber'][0];
    
    echo 'gn: ' . $givenName . ' sn: ' . $surname . 
         ' dispname: ' . $displayname . ' phone: ' . $telephone;
    
    //Print all possible attributes
    echo '<pre>' . print_r($ldapObject,true) . '</pre>';
}
//...
```

If you use the [Edvlerblog\Adldap2\model\UserDbLdap.php](src/model/UserDbLdap.php) class you can do things like login with a user into yii2 **without createing them** in yii2. Tasks like creating a user, assigning roles and check password against Active Directory all automatically done from [Edvlerblog\Adldap2\model\UserDbLdap.php](src/model/UserDbLdap.php) class.  

For example imagine the following:  
- You create a user in Active Directory and assign this user to a group starting with **yii2_** (e.g. yii2_example_group).
- In yii2 a role with the same name exists (yii2_example_group). The role has some permissions assigned.

If you try to login with your new user, the user is created **automatically** in yii2 and role yii2_example_group is assigned **automatically** on login.  
For the user this is transparent. The only feedback to the user is a successull login and that it is possible to use the functions which he has permissions to access.

**Further Documentation with setup and examples:** [docs/USAGE_WITH_USER_MODEL.md](docs/USAGE_WITH_USER_MODEL.md)



### Unit-Tests: Not needed for normal users of the yii2-adldap-module
There exists two Unit-Tests.
- One for Method 1: tests/SimpleUsageTest.php
- One for Method 2: tests/UserModelTest.php

For Method 2 it's neccessary to setup the deep integration as described here: [docs/USAGE_WITH_USER_MODEL.md](docs/USAGE_WITH_USER_MODEL.md)

**Usage:**
Use the phpunit from yii2. Its placed in vendor\bin\phpunit.
Start in the tests in windows with
```cmd
cd vendor/edvlerblog/yii2-adldap-module
..\..\bin\phpunit
..\..\bin\phpunit --testdox
```