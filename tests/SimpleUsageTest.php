<?php
include_once ('base\TestVariables.php');

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

    
    public function testUserAuthentication() {
        $this->assertFalse(\Yii::$app->ad->auth()->attempt(TestVariables::$TEST_NOT_EXISTING_USERNAME,'Login with NOT existing user has to be false!'));
        $this->assertTrue(\Yii::$app->ad->auth()->attempt(TestVariables::$TEST_USER,TestConfig::$TEST_USER_PASSWORD),'Login with existing user failed! Does the user exists in Active Directory as described in top of the class SimpleUsageTest?');
    }

    public function testUserObject() {
        $this->assertNull(\Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_NOT_EXISTING_USERNAME),'User Object is NOT NULL'); //Try to get NOT existing user.        
        
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER);
        $this->assertNotNull($userObject,'User Object is NULL'); //User not found?
        $this->assertObjectHasAttribute('attributes',$userObject,'Attribute "attributes" of Class not found.'); //attributes exists
        $this->assertArrayHasKey('cn',$userObject['attributes'],'Key cn not found in Array of LDAP Attributes!'); //Key cn vorhanden
        $this->assertEquals($userObject['attributes']['sn'][0],TestVariables::$TEST_SURNAME,'Key sn not found!'); //Surname is same as defined
        $this->assertEquals($userObject['attributes']['givenname'][0],TestVariables::$TEST_GIVEN_NAME,'Key givenname not found!'); //Givenname is same as defined        
        $this->assertTrue($userObject->inGroup(TestVariables::$TEST_GROUP_NAME),'User is not member of group ' . TestVariables::$TEST_GROUP_NAME); //Test if user is member of group
    }
}
