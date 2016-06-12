# yii2-adldap-module

[Yii2](http://www.yiiframework.com) extension for adLDAP (https://packagist.org/packages/adldap2/adldap2)

[![Latest Stable Version](https://poser.pugx.org/edvlerblog/yii2-adldap-module/v/stable)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Total Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/downloads)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Monthly Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/d/monthly)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)

## Version

yii2-adldap-module Releases beginning with tag v1.*.* are reserved for Adldap2 v5.*
The corresponding Adldap2 repository is https://github.com/Adldap2/Adldap2/tree/v5.2/

**Keep this in mind if you are browsing the GitHub Repository of Adldap2**


## Installation


### Composer

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run

	php composer.phar require edvlerblog/yii2-adldap-module "v1.1.0"

or add

	"edvlerblog/yii2-adldap-module": "v1.1.0"

to the require section of your composer.json


## Configuration

Add this code in your components section of the application configuration (eg. config/main.php):

	'components' => [
		..... 
		
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
		
		...
	]
	
[More abount config options](https://github.com/Adldap2/Adldap2/blob/v5.2/docs/CONFIGURATION.md)


## Examples

Authenticate user
https://github.com/Adldap2/Adldap2/blob/v5.2/docs/GETTING-STARTED.md

	$un = 'testuser';
	$pw = 'VeryStrongPw';
	if(\Yii::$app->ldap->authenticate($un,$pw)) {
	    echo 'User successfully authenticated';
	} else {
	    echo 'User or Password wrong';
	}


Retrive all informations about a user
https://github.com/Adldap2/Adldap2/blob/v5.2/docs/classes/USERS.md
or https://github.com/Adldap2/Adldap2/blob/v5.2/docs/SEARCH.md

	$un = 'testuser';
	$user = \Yii::$app->ldap->users()->find($un);
	
	//print all informations of the user object
	echo '<pre>';
	echo var_dump($user);
	echo '</pre>'; 

Check if user is in group
with foreach

	$un = 'testuser';
	$user = \Yii::$app->ldap->users()->find($un);

	$gn = 'the-group-name';
	foreach($user->getMemberOf() as $group)
	{
	    if($gn == $group->getName()) {
	    	echo 'TRUE';
	    }
	}

with inGroup function

	$un = 'testuser';
	$user = \Yii::$app->ldap->users()->find($un);

	$gn = 'the-group-name';
	if ($user->inGroup($gn)) {
	    echo 'User in Group ' . $gn;
	} else {
	    echo 'User NOT in Group ' . $gn;
	}

...

## DOCUMENTATION
yii2-adldap-module is only a wrapper class. Feel free to learn more about the underlying adLDAP.

You can find the website at https://github.com/Adldap2/Adldap2/tree/v5.2/docs
