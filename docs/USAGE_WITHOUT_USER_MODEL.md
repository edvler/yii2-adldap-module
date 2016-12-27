## Examples for usage of the Adldap2 Module without a user model.

This code can be added in any class to query or update informations from the Active Directory. For example in a Controller or in a View.

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
