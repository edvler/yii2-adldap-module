# yii2-adldap-module v6 (wrapper for Adldap2 v10)
[Yii2](http://www.yiiframework.com) extension for Adldap2 (https://packagist.org/packages/adldap2/adldap2)

[![Latest Stable Version](https://poser.pugx.org/edvlerblog/yii2-adldap-module/v/stable)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Total Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/downloads)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Monthly Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/d/monthly)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Daily Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/d/daily)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![License](https://poser.pugx.org/phpunit/phpunit/license)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)

* Query Active Directory users, groups, computers, organizational units, ...
* RBAC user model
* Create/Update/Edit Active Directory objects
* Extensive test suite

## Please read this if you upgrade from older versions to v5 or v6
Adldap2 changed option keys in version 9. If you upgrade from a previous version you have to change your config/web.conf (basic template) OR common/config/main.conf (advanced template) and
your config/console.conf (basic template) OR console/config/main.conf (advanced template).

For all Adldap 2 options see https://adldap2.github.io/Adldap2/#/setup?id=array-example-with-all-options.

The mandatory changed options are:
* admin_username: renamed to username
* admin_passwort: renamed to passwort
* domain_controllers: renamed to hosts

If you configure your username append your domain with **@domain.name**. Otherwise you maybe get
**Adldap\Auth\Bindexception: Invalid Credentials**.

```php
...
 'username' => 'username_ldap_access@example.lan',
...
```
See [Configuration](#configuration) section for example.

The surname 

## Howto contribute or support the extension
As you as delevoper know, it's **not only source code** that matters. The best code is worthless if no **documentation** exists. 
My focus is to provide a comprehensive documentation for this extension. This should help **YOU** to do your task fast and without strugle.
Updating this extension take days starting with programming, writing the docs and write test for the code and the docs.

**I'am glad to see that many persons use the plugin!**

If you want to help you can do the following:
* Extend or correct the docs and create a Pull-Request
* Fix or extend the plugins source code and create a Pull-Request
* Add further tests and create a Pull-Request
* Open a issue for questions or problems

**If this project help you reduce time to develop, you can spend me a cup of coffee :)**

[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WVHQ2539QZGRU)

## List of content
* **Overview**
	* [Version](#version)
	* [Functions of the extension](#functions-of-the-extension)

* **Installation and configuration**
	* [Installation](#installation)
	* [Configuration](#configuration)

* **Usage Methods**
	* [Method 1](#usage-method-1-simple-usage-without-a-user-model) Query informations
	* [Method 2](#usage-method-2-deep-integration-into-the-yii2-framework-with-a-user-model) RBAC user model
	* [Method 3](#usage-method-3-create-and-modify-active-directory-objects) Create and modify objects

* **For developers**
	* [Testing](#testing)

## Version

Current Version:
yii2-adldap-module Releases beginning with tag v6.*.* are reserved for Adldap2 v10.*
The corresponding Adldap2 repository is https://github.com/Adldap2/Adldap2/tree/master

**Keep this in mind if you are browsing the GitHub Repository of Adldap2**

## Functions of the extension
It has been a long way since 29. Jan 2014, many functions has been added. I noticed for myself that a short overview might help everyone to see whats possible.

**The simple [Method 1](#usage-method-1-simple-usage-without-a-user-model)**
* Query only informations from Active Directory.

**The deep integration with [Method 2](#usage-method-2-deep-integration-into-the-yii2-framework-with-a-user-model)**
* Sign in with a Active Directory User is possible **without doing anything in yii2**. The only action needed is creating a Active Directory User and add it to a group in Active Directory. 
* Full support of the RBAC-concept from yii2
* Default is to login with the sAMAccountName [Edvlerblog\Adldap2\model\UserDbLdap.php::findByUsername($username)](src/model/UserDbLdap.php). But using any attribute is possible [Edvlerblog\Adldap2\model\UserDbLdap.php::findByAttribute($attribute,$searchValue)](src/model/UserDbLdap.php).
* Default is, that on login the Active Directory Account status and the group assignments are checked. Based on the results the login is possible or not.
* You can access every Active Directory attribute of the user. [Method 2](#usage-method-2-deep-integration-into-the-yii2-framework-with-a-user-model)
* This yii2-extension is highly configurable.

**Create, modify or delete Active Directory objects with [Method 3: docs/CREATE_MODIFY_DELETE_OBJECTS.md](docs/CREATE_MODIFY_DELETE_OBJECTS.md)**
* Thanks to Adldap2, it's easy to create, modify or delete objects.

**How to start??**
* My suggestion is that you should start with Method 1. Start with a configration as described below and do some simple querys. If you see how it works, you can try Method 2.

**If you have some questions...**
* Please see the the separeted howto's for each Method. 
* [Method 1: docs/USAGE_WITHOUT_USER_MODEL.md](docs/USAGE_WITHOUT_USER_MODEL.md)
* [Method 2: docs/USAGE_WITH_USER_MODEL.md](docs/USAGE_WITH_USER_MODEL.md)
* [Method 3: docs/CREATE_MODIFY_DELETE_OBJECTS.md](docs/CREATE_MODIFY_DELETE_OBJECTS.md)
* Open a issue or a pull request.

## Installation

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run
```
php composer.phar require edvlerblog/yii2-adldap-module "^6.0.0"
```
or add
```
"edvlerblog/yii2-adldap-module": "^6.0.0"
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
	     * Adlapd2 can handle multiple providers to different Active Directory sources.
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
				'hosts'    => ['server01.example.lan', 'server02.example.lan'],

				// Your base DN. This is usually your account suffix.
				'base_dn'               => 'dc=example,dc=lan',

				// The account to use for querying / modifying users. This
				// does not need to be an actual admin account.
				'username'        => 'username_ldap_access@example.lan',
				'password'        => 'password_ldap_access!',

                                // To enable SSL/TLS read the docs/SSL_TLS_AD.md and uncomment
                                // the variables below
                                //'port' => 636,
                                //'use_ssl' => true,
                                //'use_tls' => true,                                
			    ]
			],

			/*
			 * Another Provider
			 * You don't have to define another provider if you don't need it. It's just an example.
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
				'hosts'    => ['server1.test.lan', 'server2'],

				// Your base DN. This is usually your account suffix.
				'base_dn'               => 'dc=test,dc=lan',

				// The account to use for querying / modifying users. This
				// does not need to be an actual admin account.
				'username'        => 'username_ldap_access@test.lan',
				'password'        => 'password_ldap_access',

                                // To enable SSL/TLS read the docs/SSL_TLS_AD.md and uncomment
                                // the variables below
                                //'port' => 636,
                                //'use_ssl' => true,
                                //'use_tls' => true, 
			    ] // close config
			], // close provider
	    ], // close providers array
	], //close ad
```

See official documentation for all config options.  
https://adldap2.github.io/Adldap2/#/setup?id=options

## Usage - Method 1, Method 2 and Method 3

### Usage method 1: Simple usage without a user model
If you are need to query some informations for a user from the Active Directory this would be best way.
No additional configuration is needed and the only thing to do is to add the [configuration](#configuration) as described above to your components section.

You only use the extension in the regular Yii2 style:
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
$surname = $ldapObject['sn'][0];
$displayname = $ldapObject['displayname'][0];
$telephone = $ldapObject['telephonenumber'][0];

echo 'gn: ' . $givenName . ' sn: ' . $surname . 
 ' dispname: ' . $displayname . ' phone: ' . $telephone;

//Print all possible attributes
echo '<pre>' . print_r($ldapObject,true) . '</pre>';

// More ways to get attributes: 
// https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#getting-attributes
```

**Further documentation with examples:** [docs/USAGE_WITHOUT_USER_MODEL.md](docs/USAGE_WITHOUT_USER_MODEL.md)

Modify of attributes is also possible. See [Method 3](#usage-method-3-create-modify-and-delete-active-directory-objects).

---

### Usage method 2: Deep integration into the yii2 framework with a user model
The second method gives you the ability to authenticate users against Active Directory with a special user model. It intgerates very well into the RBAC security concept of yii2 (http://www.yiiframework.com/doc-2.0/guide-security-authorization.html#rbac).

You can use all features of the yii2 user integration.

Some Examples:
```php
//...
//Has user a permission?
$hasPermission = \Yii::$app->user->can('permissionDisplayDetailedAbout');


//Query informations from Active Directory. You can use it in a controller, a view, everywhere in yii2!
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

    // More ways to get attributes of a user model:
    // https://adldap2.github.io/Adldap2/#/models/user
}
//...
```

If you use the [Edvlerblog\Adldap2\model\UserDbLdap.php](src/model/UserDbLdap.php) class you can do things like login with a user into yii2 **without createing them** in yii2. Tasks like creating a user, assigning roles and check password against Active Directory all automatically done from [Edvlerblog\Adldap2\model\UserDbLdap.php](src/model/UserDbLdap.php) class.  

For example imagine the following:  
- You create a user in Active Directory and assign this user to a group starting with **yii2_** (e.g. yii2_example_group).
- In yii2 a role with the same name exists (yii2_example_group). The role has some permissions assigned.

If you try to login with your new user, the user is created **automatically** in yii2 and role yii2_example_group is assigned **automatically** on login.  
For the human this is transparent. The only feedback to the human is a successfull login and that it is possible to use the functions which he has permissions to access.

**Further documentation with setup and examples:** [docs/USAGE_WITH_USER_MODEL.md](docs/USAGE_WITH_USER_MODEL.md)

---

### Usage method 3: Create, modify and delete Active Directory objects
Adldap2 offers the option to create, modify and delete Active Directory objects.
See https://adldap2.github.io/Adldap2/#/models/model for documentation.

**Prequesits**
* To create or modify attributes of a Active Directory object use a bind user in your [configuration](#configuration) with rights to change the attributes of the objects (a dirty but **very discourraged** way is to add the bind user to the domain-admins group)!
* For some actions, like change the password, you need a SSL/TLS connection. See [configuration](#configuration) for further hints.

**One example:** Modify the displayname of a user

```php
// https://adldap2.github.io/Adldap2/#/searching?id=finding-a-record-by-a-specific-attribute
// Step 1: Query the ldap object (via method 1 or method 2) 
$un = 'testuser';
$ldapObject = \Yii::$app->ad->getProvider('default')->search()->findBy('sAMAccountname', $un);

// Step 2: Update the attribute
// 
$ldapObject->setDisplayName('Fancy New Displayname');

// Step 3: Save an check return value
// https://adldap2.github.io/Adldap2/#/models/model?id=attributes
// https://adldap2.github.io/Adldap2/#/models/model?id=updating-attributes
if ($ldapObject->save()) {
    echo "// Displayname successfully updated.";
} else {
    echo "// There was an issue updating this user.";
} 
```

**Further documentation:** [docs/CREATE_MODIFY_DELETE_OBJECTS.md](docs/CREATE_MODIFY_DELETE_OBJECTS.md)

---

### Testing
This section is only for developers, that may extend the functionality.

These test classes exists:
* tests/InitialTest.php: Delete, create and modify users and groups and check results
* tests/UserModelTest.php: Test the [src/model/UserDbLdap.php](src/model/UserDbLdap.php)

For the UserModelTest test it's neccessary to setup the deep integration as described here: [docs/USAGE_WITH_USER_MODEL.md](docs/USAGE_WITH_USER_MODEL.md)

**Usage:**
* Use the phpunit from yii2. Its placed in vendor\bin\phpunit.
* Create the config class tests\base\TestConfig.php from the template tests\base\TestConfigSample.php.

Start the tests in windows with:
```cmd
// WARNING!! NOT RUN ON PRODUCTION!!
// TABLES ARE TRUNCATED AND ACTIVE DIRECTORY IS MODIFIED!
// TAKE A LOOK AT THE SOURCE CODE BEFORE RUNNING THE TESTS.
cd vendor/edvlerblog/yii2-adldap-module
..\..\bin\phpunit -v --debug
..\..\bin\phpunit --testdox
```
