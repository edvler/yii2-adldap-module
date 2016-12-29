## Usage method 1: Simple usage without a user model

### Authenticate user  
https://github.com/Adldap2/Adldap2/blob/v5.2/docs/GETTING-STARTED.md

```php
$un = 'testuser';
$pw = 'VeryStrongPw';
if(\Yii::$app->ldap->authenticate($un,$pw)) {
    echo 'User successfully authenticated';
} else {
    echo 'User or Password wrong';
}
```

### Retrive all informations about a user  
https://github.com/Adldap2/Adldap2/blob/v5.2/docs/classes/USERS.md
or https://github.com/Adldap2/Adldap2/blob/v5.2/docs/SEARCH.md

```php
$un = 'testuser';
$user = \Yii::$app->ldap->users()->find($un);

//print all informations of the user object
echo '<pre>';
echo var_dump($user);
echo '</pre>'; 
```

### Check if user is in group with foreach  

```php
$un = 'testuser';
$user = \Yii::$app->ldap->users()->find($un);

$gn = 'the-group-name';
foreach($user->getMemberOf() as $group)
{
    if($gn == $group->getName()) {
	echo 'TRUE';
    }
}
```

### Check if user is in group with inGroup() function  

```php
$un = 'testuser';
$user = \Yii::$app->ldap->users()->find($un);

$gn = 'the-group-name';
if ($user->inGroup($gn)) {
    echo 'User in Group ' . $gn;
} else {
    echo 'User NOT in Group ' . $gn;
}
```

### Further documentation of other functions
yii2-adldap-module is only a wrapper class. The examples above are all taken from the official documentation of the Adldap2 repository.

You can find the docs at https://github.com/Adldap2/Adldap2/tree/v5.2/docs
