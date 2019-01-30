# Usage method 3: Create and modify objects

## This is only a Quick-Start-Guide!
yii2-adldap-module is only a wrapper class. The examples below are all taken from the official documentation of the Adldap2 repository.

You can find the documentation here: https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md OR https://adldap2.github.io/Adldap2/#/searching

**Don't forget, to set passwords on Active Directory users, you need a SSL/TLS connection!**
See [/readme.md#configuration](/readme.md#configuration) ssl/tls parameters.

## Some examples (taken from testsuite [/tests/InitialTest.php](/tests/InitialTest.php))

### Create user
```php
        // https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#saving
        // create user
        $user = \Yii::$app->ad->make()->user([
            'cn' => 'Snoop Einstein',
        ]);

        // set attributes with set... function
        $user->setAccountName('snoopstein');
        $user->setDisplayName('Snoop Einstein');
        $user->setFirstName('Snoop');
        $user->setLastName('Einstein');
        $user->setUserPrincipalName('snoopstein@test.lan');
        $user->setInfo('Cat and dog since 06.12.2017');        

        // set attribute with setAttribute(..)
        $user->setAttribute('initials', 'ES');
        
        // set attribute with fill
        $user->fill([
            'company' => 'Animal plc.',
            'department' => 'Dept. C and D',
            'description' => 'Einstein is a cat and Snoop is a dog!',
        ]);

        // create dn
        $dn = $user->getDnBuilder();
        $dn->addCn($user->getCommonName());
        $dn->addCN('Users');
        $user->setDn($dn);

        // save an check return value
        if ($user->save()) {
            echo "// saved successfully.";
        } else {
            echo "// There was an issue saving this user.";
        } 

        // Enable account (has to be an extra step!)
        // https://github.com/Adldap2/Adldap2/blob/master/src/Models/Attributes/AccountControl.php
        $ac = new Adldap\Models\Attributes\AccountControl();
        $ac->accountIsNormal();        
        $user->setUserAccountControl($ac);
        $user->setPassword('VeryStrong123'); //Needs ssl/tls
        if ($user->save()) {
            echo "// saved successfully.";
        } else {
            echo "// There was an issue saving this user.";
        }  

        // test if objects realy exists
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', 'snoopstein');
        echo $userObject->exists;
        echo $userObject->getDisplayName();
        echo '<pre>' . print_r($userObject,true) . '</pre>';
```

### Modify user
```php
// Step 1: query the ldap object (via method 1 or method 2) 
$ldapObject = \Yii::$app->ad->getProvider('default')->search()->findBy('sAMAccountname', 'snoopstein');

// Step 2: Update the attribute
// Further documentation: 
// https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#setting-attributes
$ldapObject->setDisplayName('Fancy New Displayname');
// .... modify other attributes ....

// Step 3: Save an check return value
if ($ldapObject->save()) {
    echo "// Displayname successfully updated.";
} else {
    echo "// There was an issue updating this user.";
} 
```

### Delete user
```php       
        // https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#deleting
        // check if account exists
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', 'snoopstein');
        if($userObject != null && $userObject->exists) {
            // delete if exists
            if ($userObject->delete()) {
                echo "// deleted successfully.";
            } else {
                echo "// There was an issue deleting this user.";
            }  
        }
```

### Create a group
```php
        // https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#available-make-methods
        // create the group
        $group1 = \Yii::$app->ad->make()->group([
            'cn' => 'Cat and Dog',
        ]);

        // create dn
        $dn = $group1->getDnBuilder();
        $dn->addCn($group1->getCommonName());
        $dn->addCN('Users');
        $group1->setDn($dn);

        if ($group1->save()) {
            echo "// created successfully.";
        } else {
            echo "// There was an issue creating this group.";
        }  
```

### Add user to group
```php
        // https://github.com/Adldap2/Adldap2/blob/master/docs/models/traits/has-member-of.md#adding-a-group
        // find user
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', 'snoopstein');
        
        // find group
        $groupObject = \Yii::$app->ad->search()->findBy('cn', 'Cat and Dog');
        
        // add group to user
        $userObject->addGroup($groupObject);

        if ($userObject->save()) {
            echo "// added successfully.";
        } else {
            echo "// There was an issue adding this group.";
        }  
```

### Delete group
```php
        // https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#deleting
        // check if group exists
        $groupObject = \Yii::$app->ad->search()->findBy('cn', 'Cat and Dog');
        if($groupObject != null && $groupObject->exists) {
            // delete if exists
            if ($groupObject->delete()) {
                echo "// deleted successfully.";
            } else {
                echo "// There was an issue deleted this group.";
            }
        }
```