# yii2-adldap-module

[Yii2](http://www.yiiframework.com) extension for adLDAP (https://packagist.org/packages/adldap/adldap)

## Installation

### Composer

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run

	php composer.phar require edvlerblog/yii2-adldap-module "dev-master"

or add

	"edvlerblog/yii2-adldap-module": "dev-master"

to the require section of your composer.json

Add code in your components section of application configuration:

	'ldap' => [
		'class'=>'Edvlerblog\Ldap',
		'options'=> [
				'ad_port'      => 389,
				'domain_controllers'    => array('AdServerName'),
				'account_suffix' =>  '@test.lan',
				'base_dn' => "DC=test,DC=lan",
		// for basic functionality this could be a standard, non privileged domain user (required)
				'admin_username' => 'ActiveDirectoryUser',
				'admin_password' => 'StrongPassword'
			]
	]

## Examples

Authenticate User:

    \Yii::$app->ldap->authenticate("username","password");