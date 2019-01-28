<?php
include_once ('base\TestVariables.php');

class InitialTest extends TestCase
{

    public function testCleanup() {
        $this->assertTrue($this->checkAndDeleteUser());
    }
    
    public function testCreateUser() {
        // https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#saving
        // create user
        $user = \Yii::$app->ad->make()->user([
            'cn' => TestVariables::$TEST_USER_CN,
        ]);
        // set attributes with set... function
        $user->setAccountName(TestVariables::$TEST_USER_ACCOUNT_NAME);
        $user->setInfo(TestVariables::$TEST_USER_INFO);
        $user->setDisplayName(TestVariables::$TEST_USER_DISPLAY_NAME);
        $user->setFirstName(TestVariables::$TEST_USER_GIVEN_NAME);
        $user->setLastName(TestVariables::$TEST_USER_SURNAME);
        $user->setTitle(TestVariables::$TEST_USER_TITLE);
        $user->setTelephoneNumber(TestVariables::$TEST_USER_PHONE);
        $user->setPassword(TestConfig::$TEST_USER_PASSWORD); //Needs ssl/tls
        $user->setStreetAddress(TestVariables::$TEST_USER_STREET);
        $user->setPostalCode(TestVariables::$TEST_USER_POSTALCODE);
        $user->setUserPrincipalName(TestConfig::$TEST_USER_PRINCIPAL_NAME);
        
        // set attribute with setAttribute(..)
        $user->setAttribute('initials', TestVariables::$TEST_USER_INITITALS);
        
        // set attribute with fill
        $user->fill([
            'company' => TestVariables::$TEST_USER_COMPANY,
            'department' => TestVariables::$TEST_USER_DEPARTMENT,
            'description' => TestVariables::$TEST_USER_DESCRIPTION,
        ]);

        // create dn
        $dn = $user->getDnBuilder();
        $dn->addCn($user->getCommonName());
        $dn->addCN('Users');
        $user->setDn($dn);

        // save user
        $this->assertTrue($user->save(),'Create user failed');
        
        // check if account exists
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER_ACCOUNT_NAME);
        $this->assertTrue($userObject->exists,'User not found after create');
        $this->assertEquals($userObject->getDisplayName(), TestVariables::$TEST_USER_DISPLAY_NAME, 'Display name is not as expected');

        // set account control and set password!
        // set account control only on a user, which already exists
        // https://github.com/Adldap2/Adldap2/blob/master/src/Models/Attributes/AccountControl.php
        $ac = new Adldap\Models\Attributes\AccountControl();
        $ac->accountIsNormal();        
        $userObject->setUserAccountControl($ac);
        $userObject->setPassword(TestConfig::$TEST_USER_PASSWORD);
        $this->assertTrue($userObject->save(),'Account cannot be activated');
    }   
    
    public function testCreateGroups() {
        // create the group
        $group1 = \Yii::$app->ad->make()->group([
            'cn' => TestVariables::$TEST_GROUP_NAME,
        ]);

        // create dn
        $dn = $group1->getDnBuilder();
        $dn->addCn($group1->getCommonName());
        $dn->addCN('Users');
        $group1->setDn($dn);

        $this->assertTrue($group1->save(),'Create group ' . TestVariables::$TEST_GROUP_NAME . ' failed');
        
        // create nested group
        $group_nested = \Yii::$app->ad->make()->group([
            'cn' => TestVariables::$TEST_NESTED_GROUP_NAME,
        ]);

        // create dn
        $dn = $group_nested->getDnBuilder();
        $dn->addCn($group_nested->getCommonName());
        $dn->addCN('Users');
        $group_nested->setDn($dn);

        $this->assertTrue($group_nested->save(),'Create ' . TestVariables::$TEST_NESTED_GROUP_NAME . ' group failed');         
    }
    
    public function testAddUserToGroup() {
        // find user
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER_ACCOUNT_NAME);
        $this->assertTrue($userObject->exists,'User not found after create');
        $this->assertEquals($userObject->getDisplayName(), TestVariables::$TEST_USER_DISPLAY_NAME, 'Display name is not as expected');
        
        // find group
        $groupObject = \Yii::$app->ad->search()->findBy('cn', TestVariables::$TEST_GROUP_NAME);
        
        // add group to user
        $userObject->addGroup($groupObject);
        $this->assertTrue($userObject->save(),'Group cannot be added to user');
    }
    
    public function testAddNestedGroup() {
        // search groups
        $groupObject = \Yii::$app->ad->search()->findBy('cn', TestVariables::$TEST_GROUP_NAME);      
        $nestedgroupObject = \Yii::$app->ad->search()->findBy('cn', TestVariables::$TEST_NESTED_GROUP_NAME);
        
        // add group to nested group
        $nestedgroupObject->addMember($groupObject);
        $this->assertTrue($groupObject->save(),'Group ' . TestVariables::$TEST_NESTED_GROUP_NAME . ' cannot be added to group ' . TestVariables::$TEST_GROUP_NAME);
    }
    
    public function testSimpleAuthentication() {
        // test simple auth
        $this->assertFalse(\Yii::$app->ad->auth()->attempt(TestVariables::$TEST_NOT_EXISTING_USERNAME,'Login with NOT existing user has to be false!'));
        $this->assertTrue(\Yii::$app->ad->auth()->attempt(TestVariables::$TEST_USER_ACCOUNT_NAME,TestConfig::$TEST_USER_PASSWORD),'Login with existing user failed! Does the user exists in Active Directory as described in top of the class SimpleUsageTest?');
    }

    public function testUserObject() {
        // search for non existing user
        $this->assertFalse(\Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_NOT_EXISTING_USERNAME),'User Object is NOT NULL'); //Try to get NOT existing user.        
        
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER_ACCOUNT_NAME);
        $this->assertNotNull($userObject,'User Object is NULL'); //User not found?
        $this->assertObjectHasAttribute('attributes',$userObject,'Attribute "attributes" of Class not found.'); //attributes exists
        $this->assertArrayHasKey('cn',$userObject['attributes'],'Key cn not found in Array of LDAP Attributes!'); //Key cn vorhanden
        $this->assertEquals($userObject['attributes']['sn'][0],TestVariables::$TEST_USER_SURNAME,'Key sn not found!'); //Surname is same as defined
        $this->assertEquals($userObject['attributes']['givenname'][0],TestVariables::$TEST_USER_GIVEN_NAME,'Key givenname not found!'); //Givenname is same as defined        
        $this->assertTrue($userObject->inGroup(TestVariables::$TEST_GROUP_NAME),'User is not member of group ' . TestVariables::$TEST_GROUP_NAME); //Test if user is member of group
        $this->assertFalse($userObject->inGroup(TestVariables::$TEST_NESTED_GROUP_NAME),'User should no be in nested group ' . TestVariables::$TEST_NESTED_GROUP_NAME); //Test if user is member of group
        $this->assertTrue($userObject->inGroup(TestVariables::$TEST_NESTED_GROUP_NAME,true),'User should no be in nested group ' . TestVariables::$TEST_NESTED_GROUP_NAME); //Test if user is member of group
    }     
    
    /**
     * Documentation
     * https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#setting-attributes
     */
    public function testUpdateDisplayName() {
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER_ACCOUNT_NAME);
        $this->assertEquals($userObject['attributes']['displayname'][0],TestVariables::$TEST_USER_DISPLAY_NAME,'Display name wrong!');   
        
        $userObject->setDisplayName(TestVariables::$TEST_USER_DISPLAY_NAME_MOD);
        $this->assertTrue($userObject->save(),'Update displayname failed');
        $this->assertEquals($userObject['attributes']['displayname'][0],TestVariables::$TEST_USER_DISPLAY_NAME_MOD,'Display name wrong after Update!');

        $userObject->setDisplayName(TestVariables::$TEST_USER_DISPLAY_NAME);
        $this->assertTrue($userObject->update(),'Update displayname failed');
        $this->assertEquals($userObject['attributes']['displayname'][0],TestVariables::$TEST_USER_DISPLAY_NAME,'Display name wrong after Update!');  
    }    
    
    
    /**
     * Documentation
     * https://github.com/Adldap2/Adldap2/blob/master/docs/models/model.md#setting-attributes
     */    
    public function testMassUpdate() {
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER_ACCOUNT_NAME);
        $this->assertEquals($userObject->getDisplayName(),TestVariables::$TEST_USER_DISPLAY_NAME,'Display name wrong!');   
        
        //Assume nothing is set
        $this->assertEquals($userObject->getFirstAttribute('mail'),'','Mail should not be set');
        $this->assertEquals($userObject->getAttribute('homephone',0),'','Homephone should not be set');
        
        // Mass setting attributes:
        $userObject->fill([
            'homephone' => '01234567',
            'mail' => 'test@test.lan',
        ]);        
        $this->assertTrue($userObject->save(),'Update attributes failed');
        
        //Assume the values are set
        $this->assertEquals($userObject->getFirstAttribute('01234567'),'','Mail should not be set');
        $this->assertEquals($userObject->getAttribute('test@test.lan',0),'','Homephone should not be set');        
        
        // Unset values
        $userObject->fill([
            'homephone' => null,
            'mail' => null,
        ]);   
        $this->assertTrue($userObject->save(),'Update attributes failed');
    }
}
