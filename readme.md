# yii2-adldap-module

[Yii2](http://www.yiiframework.com) extension for adLDAP (https://packagist.org/packages/adldap2/adldap2)

[![Latest Stable Version](https://poser.pugx.org/edvlerblog/yii2-adldap-module/v/stable)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Total Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/downloads)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Monthly Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/d/monthly)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)

## Version

yii2-adldap-module Releases beginning with tag v1.*.* are reserved for Adldap2 v5.*.  
The corresponding Adldap2 repository is https://github.com/Adldap2/Adldap2/tree/v5.2/

**Keep this in mind if you are browsing the GitHub Repository of Adldap2**


## Installation

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run
```
php composer.phar require edvlerblog/yii2-adldap-module "^1.1.2"
```
or add
```
"edvlerblog/yii2-adldap-module": "^1.1.2"
```
to the require section of your composer.json


## Configuration

Add this code in your components section of the application configuration (eg. config/main.php):
```php
'components' => [
//..... 

'ldap' => [
	'class'=>'Edvlerblog\Ldap',
	'options'=> [
			'ad_port'      => 389,
			'domain_controllers'    => array('AdServerName1','AdServerName2'),
			'account_suffix' =>  '@test.lan',
			'base_dn' => "DC=test,DC=lan",
			// for basic functionality this could be a standard, non privileged domain user (required)
			'admin_username' => 'ActiveDirectoryUser',
			'admin_password' => 'StrongPassword'
		],
	//Connect on Adldap instance creation (default). If you don't want to set password via main.php you can
	//set autoConnect => false and set the admin_username and admin_password with
	//\Yii::$app->ldap->connect('admin_username', 'admin_password');
	//See function connect() in https://github.com/Adldap2/Adldap2/blob/v5.2/src/Adldap.php

	'autoConnect' => true
]

//...
]
```

See official documentation for all config options.  
https://github.com/Adldap2/Adldap2/blob/v5.2/docs/CONFIGURATION.md


## Usage - Method 1 and/or Method 2

### Usage method 1: Simple usage without a user model
If you are need to query some informations for a user from the Active Directory this would be best way.
No additional configuration is needed and the only thing to do is to add the configuration as described above to your components section.

You only call the the component as usual.
```php
//...
$un = 'testuser';
$pw = 'VeryStrongPw';
$user = \Yii::$app->ldap->users()->find($un);
print_r($user); //print all informations retrieved from Active Directory
//...
```

**Further Documentation with examples:** [docs/USAGE_WITHOUT_USER_MODEL.md](docs/USAGE_WITHOUT_USER_MODEL.md)

---

### Usage method 2: Deep integration into the yii2 framework with a user model
The second method gives you the ability to authenticate users against Active Directory with a special user model. It intgerates very well into the RBAC security concept of yii2 (http://www.yiiframework.com/doc-2.0/guide-security-authorization.html#rbac).

If you use the [Edvlerblog\model\UserDbLdap.php](src/model/UserDbLdap.php) class you can do things like login with a user into yii2 **without createing them** in yii2. Tasks like creating a user, assigning roles and check password against Active Directory all automatically done from [Edvlerblog\model\UserDbLdap.php](src/model/UserDbLdap.php) class.  

For example imagine the following:  
- You create a user in Active Directory and assign this user to a group starting with **yii2_** (e.g. yii2_example_group).
- In yii2 a role with the same name exists (yii2_example_group). The role has some permissions assigned.

If you try to login with your new user, the user is created **automatically** in yii2 and role yii2_example_group is assigned **automatically** on login.  
For the user this is transparent. The only feedback to the user is a successull login and that it is possible to use the functions which he has permissions to access.

**Further Documentation with setup and examples:** [docs/USAGE_WITH_USER_MODEL.md](docs/USAGE_WITH_USER_MODEL.md)
