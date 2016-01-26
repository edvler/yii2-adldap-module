# yii2-adldap-module

[Yii2](http://www.yiiframework.com) extension for adLDAP (https://packagist.org/packages/adldap/adldap)

![Build Status](https://travis-ci.org/edvler/yii2-adldap-module.svg?branch=master)

## Installation


### Composer

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run

	php composer.phar require edvlerblog/yii2-adldap-module "v1.0.0"

or add

	"edvlerblog/yii2-adldap-module": "v1.0.0"

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
				]
		]
		
		...
	]
	
[More abount config options](http://adldap.sourceforge.net/wiki/doku.php?id=documentation_configuration)


## Examples

To use the yii2-adldap-module you need only one line. 
You can use the yii2-adldap-module everywhere where \Yii::$app works (Controllers, Widgets,...).

Authenticate User:

    $authUser = \Yii::$app->ldap->authenticate("username","password");
	var_dump ($authUser);

Group membership of a User:

	$groups = \Yii::$app->ldap->user()->groups("username");
	var_dump($groups);  
	
Get informations about a Group:

	$groupinfo= \Yii::$app->ldap->group()->info("example_group");
	var_dump($groupinfo);  

....
	
[More examples](https://github.com/adldap/adLDAP/blob/master/examples/examples.php)


## DOCUMENTATION
yii2-adldap-module is only a wrapper class. Feel free to learn more about the underlying adLDAP.

You can find the website at https://github.com/adldap/adLDAP/ or the class documentation at

https://github.com/adldap/adLDAP/wiki/adLDAP-Developer-API-Reference
