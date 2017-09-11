<?php
/*
 * This is the testclass for the Edvlerblog\Adldap2\SimpleUsageTest (file yii2-adldap-module/src/Adldap2Wrapper.php)
 * 
 * For a successfull test there are some prerequisites.
 * In TestCase.php
 *  - Change the configuration to your Active Directory
 * 
 * In Active Directory:
 *  - Create a user with
 *      * First Name (givenname): Yii2
 *      * Surname (sn): Testuser
 *      * Login Name (sAMAccountname): yii2testuser
 *      * Password: TestTest123
 *      * Make the user member of a group with the name yii2_example_group
 */
class SimpleUsageTest extends TestCase
{
    private $testUsername = 'yii2testuser';
    private $testPassword = 'TestTest123';
    private $testGivenName = 'Yii2';
    private $testSurName = 'Testuser';
    private $testGroupName = 'yii2_example_group';
    
    private $notExistingUsername = 'someuserNotExists';
    
    public function testUserAuthentication() {
        $this->assertFalse(\Yii::$app->ad->auth()->attempt($this->notExistingUsername,'Login with NOT existing user has to be false!'));
        $this->assertTrue(\Yii::$app->ad->auth()->attempt($this->testUsername,$this->testPassword),'Login with existing user failed! Does the user exists in Active Directory as described in top of the class SimpleUsageTest?');
    }

    public function testUserObject() {
        $this->assertNull(\Yii::$app->ad->search()->findBy('sAMAccountname', $this->notExistingUsername),'User Object is NOT NULL'); //Try to get NOT existing user.        
        
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', $this->testUsername);
        $this->assertNotNull($userObject,'User Object is NULL'); //User not found?
        $this->assertObjectHasAttribute('attributes',$userObject,'Attribute "attributes" of Class not found.'); //attributes exists
        $this->assertArrayHasKey('cn',$userObject['attributes'],'Key cn not found in Array of LDAP Attributes!'); //Key cn vorhanden
        $this->assertEquals($userObject['attributes']['sn'][0],$this->testSurName,'Key sn not found!'); //Surname is same as defined
        $this->assertEquals($userObject['attributes']['givenname'][0],$this->testGivenName,'Key givenname not found!'); //Givenname is same as defined        
        $this->assertTrue($userObject->inGroup($this->testGroupName),'User is not member of group ' . $this->testGroupName); //Test if user is member of group
    }
}
