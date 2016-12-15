# yii2-adldap-module

[Yii2](http://www.yiiframework.com) extension for Adldap2 (https://packagist.org/packages/adldap2/adldap2)

[![Latest Stable Version](https://poser.pugx.org/edvlerblog/yii2-adldap-module/v/stable)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Total Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/downloads)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)
[![Monthly Downloads](https://poser.pugx.org/edvlerblog/yii2-adldap-module/d/monthly)](https://packagist.org/packages/edvlerblog/yii2-adldap-module)

## Version

Current Version:
yii2-adldap-module Releases beginning with tag v2.*.* are reserved for Adldap2 v6.*
The corresponding Adldap2 repository is https://github.com/Adldap2/Adldap2/tree/v6.1

**Keep this in mind if you are browsing the GitHub Repository of Adldap2**


## Installation


### Composer

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run

	php composer.phar require edvlerblog/yii2-adldap-module "^2.0.0"

or add

	"edvlerblog/yii2-adldap-module": "^2.0.0"

to the require section of your composer.json


## Configuration

Add this code in your components section of the application configuration (eg. config/main.php for advanced template or config/web.php for basic template):

    'components' => [
        'ad' => [
            'class' => 'Edvlerblog\Adldap2\Adldap2Wrapper',
            
            /*
             * ADLap2 v6.X.X can handle multiple providers to different Active Directory sources.
             * Each provider has it's own config.
             * 
             * In the providers section it's possible to define multiple providers as listed as example below.
             * But it's enough to only define the "default" provider!
	     *
	     * 
             */
            'providers' => [
                /*
                 * Always add a default provider!
                 * 
                 * You can get the provider with:
                 * $provider = \Yii::$app->ad->getDefaultProvider();
                 * or with $provider = \Yii::$app->ad->getProvider('default');
                 */
                'default' => [
                    // Connect this provider on initialisation of the LdapWrapper Class automatically
                    'autoconnect' => true,
                    
                    // The config has to be defined as described in the Adldap2 documentation.
                    // e.g. https://github.com/Adldap2/Adldap2/blob/v6.1/docs/quick-start.md
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
                'another_provider' => [
                    // Connect this provider on initialisation of the LdapWrapper Class automatically
                    'autoconnect' => false,
                    
                    // The config has to be defined as described in the Adldap2 documentation.
                    // e.g. https://github.com/Adldap2/Adldap2/blob/v6.1/docs/quick-start.md                  
                    'config' => [
                        // Your account suffix, for example: matthias.maderer@test.lan
                        'account_suffix'        => 'test.lan',

                        // You can use the host name or the IP address of your controllers.
                        'domain_controllers'    => ['server1.test.lan', 'server2'],

                        // Your base DN. This is usually your account suffix.
                        'base_dn'               => 'dc=test,dc=lan',

                        // The account to use for querying / modifying users. This
                        // does not need to be an actual admin account.
                        'admin_username'        => 'username_ldap_access',
                        'admin_password'        => 'password_ldap_access',
                    ]
                ],
                
                
            ],
        ],
	
More abount config options: https://github.com/Adldap2/Adldap2/blob/v6.1/docs/configuration.md


## Syntax

For almost all operations you need a provider. You can access the provider in the following ways.

	$provider = \Yii::$app->ad->getDefaultProvider();
	$search = $provider->search();  //start a search
	$search = $search->select(['cn', 'samaccountname', 'telephone', 'mail']); //Only query this attributes
	$search = $search->where('samaccountname', '=', 'matthias');
	$result = $search->get();
	
the same in one line.

	$result = \Yii::$app->ad->getDefaultProvider()->search()->select(['cn', 'samaccountname', 'telephone', 'mail'])->where('samaccountname', '=', 'matthias')->get();


## Examples

Authenticate user
https://github.com/Adldap2/Adldap2/blob/v6.1/docs/authenticating.md

	$un = 'testuser';
	$pw = 'VeryStrongPw';
	if(\Yii::$app->ad->getDefaultProvider()->auth()->attempt($un,$pw)) {
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
yii2-adldap-module is only a wrapper class. Feel free to learn more about the underlying Adldap2 Module.

You can find the documentation here: https://github.com/Adldap2/Adldap2/tree/v6.1/docs
