# Usage method 1: Simple usage without a user model

## This is only a Quick-Start-Guide!
yii2-adldap-module is only a wrapper class. The examples below are all taken from the official documentation of the Adldap2 repository.

You can find the documentation here: https://github.com/Adldap2/Adldap2/tree/master/docs

## Syntax basics

### Different ways to call Adldap2 functions
```php
//...
$un = 'testuser';

/*
There are three ways available to call Adldap2 function. 
If you use more providers (multiple Active Directory connections)
you make one as default and you can call this one with Method1 or Method2
and the second one will be called with Method3.
*/

// Method 1: uses the default provider given in the configuration above (array key defaultProvider)
$user = \Yii::$app->ad->search()->findBy('sAMAccountname', $un); 
// Method 2: uses the default provider given in the configuration above (array key defaultProvider)
$user = \Yii::$app->ad->getDefaultProvider()->search()->findBy('sAMAccountname', $un);
// Method 3: get the provider by name (here name default is used).
$user = \Yii::$app->ad->getProvider('default')->search()->findBy('sAMAccountname', $un);
print_r($user); //print all informations retrieved from Active Directory
//...
```

### Multiline example
For almost all operations you need a provider. You can access the provider in the following ways.
```php
$provider = \Yii::$app->ad->getDefaultProvider();
$search = $provider->search();  //start a search
$search = $search->select(['cn', 'samaccountname', 'telephone', 'mail']); //Only query this attributes
$search = $search->where('samaccountname', '=', 'matthias');
$result = $search->get();

echo '<pre>';
echo print_r($result,true);
echo '</pre>';	
```	
### Oneline example
```php
$result = \Yii::$app->ad->getDefaultProvider()->search()->select(['cn', 'samaccountname', 'telephone', 'mail'])->where('samaccountname', '=', 'matthias')->get();

echo '<pre>';
echo print_r($result,true);
echo '</pre>';	
```

### Oneline example without getDefaultProvider()
```php
$result = \Yii::$app->ad->search()->select(['cn', 'samaccountname', 'telephone', 'mail'])->where('samaccountname', '=', 'matthias')->get();

echo '<pre>';
echo print_r($result,true);
echo '</pre>';	
```

---

## Examples

### Authenticate user  
https://adldap2.github.io/Adldap2/#/setup?id=authenticating
```php
$un = 'testuser';
$pw = 'VeryStrongPw';
if(\Yii::$app->ad->auth()->attempt($un,$pw)) {
    echo 'User successfully authenticated';
} else {
    echo 'User or Password wrong';
}
```

### Find records
#### With findBy() function
Finding a specific record by a specific attribute. We're looking for a record with the 'samaccountname' of 'testuser'. This euqals to the username in Active Directory.  
https://adldap2.github.io/Adldap2/#/searching?id=finding-a-record-by-a-specific-attribute
```php
$un = 'testuser';
$user = \Yii::$app->ad->search()->findBy('sAMAccountname', $un);

//print all informations of the user object
echo '<pre>';
echo print_r($user,true);
echo '</pre>';
```

#### With get() function
```php
$un = 'testuser';
$user = \Yii::$app->ad->search()->where('sAMAccountName', '=', $un)->get();

//print all informations of the user object
echo '<pre>';
echo print_r($user,true);
echo '</pre>';
```

### Group Membership  
See sourcecode function getGroups() or inGroup().  
https://github.com/Adldap2/Adldap2/blob/master/src/Models/Concerns/HasMemberOf.php

#### Check if user is in group with getGroups() function.
```php
$un = 'testuser';
$user = \Yii::$app->ad->search()->findBy('sAMAccountname', $un);

$gn = 'the-group-name';
foreach($user->getGroups() as $group)
{
    if($gn == $group->getName()) {
	echo 'TRUE';
    }
}
```
#### Check if user is in group with inGroup() function.
```php
$un = 'testuser';
$user = \Yii::$app->ad->search()->findBy('sAMAccountname', $un);

$gn = 'the-group-name';
if ($user->inGroup($gn)) {
    echo 'User in Group ' . $gn;
} else {
    echo 'User NOT in Group ' . $gn;
}
```

## More Examples
yii2-adldap-module is only a wrapper class. The examples above are all taken from the official documentation of the Adldap2 repository.

You can find the documentation here: https://github.com/Adldap2/Adldap2/tree/master/docs
