<?php

require_once(__DIR__ . '/../../../../models/LoginForm.php');
use app\models\LoginForm;

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
class UserModelTest extends TestCase
{
    private $testUsername = 'yii2testuser';
    private $testPassword = 'TestTest123';
    private $testGivenName = 'Yii2';
    private $testSurName = 'Testuser';
    private $testGroupName = 'yii2_example_group';
    private $testNestedGroupName = 'yii2_nested_group';
    
    private $testDisabledUser = 'yii2testuserdis';
    
    //After each test clea
    public function tearDown() {
        \Yii::$app->db->createCommand()->truncateTable("auth_assignment")->execute();
        \Yii::$app->db->createCommand()->truncateTable("user")->execute();
        parent::tearDown();
    }
    
    public function setUp() {
        parent::setUp();
        \Yii::$app->db->createCommand()->truncateTable("auth_assignment")->execute();
        \Yii::$app->db->createCommand()->truncateTable("user")->execute();
    }
    
    private function getNewUserModel() {
        $ldapmodel = new Edvlerblog\Adldap2\model\UserDbLdap();
        $ldapmodel->username = $this->testUsername;
        return $ldapmodel;
    }
    
    public function testQueryLdapUserObject() {
        $userObject = $this->getNewUserModel()->queryLdapUserObject();
        $this->assertEquals($userObject['attributes']['givenname'][0],$this->testGivenName,'Key givenname not found!'); //Givenname is same as defined             
    }
    
    public function testQueryLdapUserGroupMembership() {
        $groups = $this->getNewUserModel()->getGroupsAssignedInLdap();
        $this->assertContains($this->testGroupName, $groups);
    }
    
    public function testFindByUsernameDisabledUser() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testDisabledUser);

        $this->assertNull($userObject);
    }
    
    public function testFindByUsername() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userName = $userObject->queryLdapUserObject()['attributes']['samaccountname'][0];
        
        $this->assertEquals($userName,$this->testUsername);
    }     
    
    public function testFindIdentityNotExistingUserId() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findIdentity(9999);

        $this->assertNull($userObject);
    }
    
    public function testFindIdentityWithId() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userObjectById = Edvlerblog\Adldap2\model\UserDbLdap::findIdentity($userObject->getId());

        $userName = $userObjectById->queryLdapUserObject()['attributes']['samaccountname'][0];
        
        $this->assertEquals($userName,$this->testUsername);
    }
    
    public function testUpdateAccountStatus() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userObject->status = Edvlerblog\Adldap2\model\UserDbLdap::STATUS_DISABLED;
        $userObject->save();
        
        $this->assertEquals($userObject->status,Edvlerblog\Adldap2\model\UserDbLdap::STATUS_DISABLED);
        $userObject->updateAccountStatus();
        
        $this->assertEquals($userObject->status,Edvlerblog\Adldap2\model\UserDbLdap::STATUS_ENABLED);
    }

    public function testCheckAllowedToLoginWithNullUser() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername('NOTEXISTINGACCOUNT');
        $userObject2 = Edvlerblog\Adldap2\model\UserDbLdap::checkAllowedToLogin($userObject);
        
        $this->assertEquals($userObject,$userObject2);
    }   
    
    public function testCheckAllowedToLogin() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userObject2 = Edvlerblog\Adldap2\model\UserDbLdap::checkAllowedToLogin($userObject);
        
        $this->assertEquals($userObject,$userObject2);
    }
    
    public function testUpdateGroupAssignment() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $yiiRolesAssignedToUser = Yii::$app->authManager->getRolesByUser($userObject->getId()); //Get all roles assigned to user
        
        //User has only group yii2_example_group assinged.
        $this->assertArrayHasKey($this->testGroupName,$yiiRolesAssignedToUser);
        $this->assertArrayNotHasKey($this->testNestedGroupName,$yiiRolesAssignedToUser);
        
        //Search for nested groups
        $userObject->setIndividualGroupAssignmentOptions(
                ['SEARCH_NESTED_GROUPS' => true]
                );
        
        //Test nested Group search. 
        //Nested group cannot bes assigned to user beacause no role in yii2 exists.
        $groupsPossible = $userObject->getGroupsAssignedInLdap();
        
        $this->assertContains($this->testGroupName,$groupsPossible);
        $this->assertContains($this->testNestedGroupName,$groupsPossible);
    }    
    
    public function testUserTestLogin() {
        $model = new LoginForm();
        
        $this->assertTrue($model->load(['LoginForm'=> ['username' => $this->testUsername, 'password' => $this->testPassword]]),'Load data into model failed');
        $this->assertTrue($model->login(),'Login with LDAP failed');
        $this->assertFalse(Yii::$app->user->isGuest);

        $idOfUser = Yii::$app->user->getId();      
        
        $userObjectById = Edvlerblog\Adldap2\model\UserDbLdap::findIdentity($idOfUser);
        $userName = $userObjectById->queryLdapUserObject()['attributes']['samaccountname'][0];
        $this->assertEquals($userName,$this->testUsername);
        
        Yii::$app->user->logout();
        $this->assertTrue(Yii::$app->user->isGuest);
        
        $this->assertTrue($model->load(['LoginForm'=> ['username' => $this->testUsername, 'password' => $this->testPassword]]),'Load data into model failed');
        $this->assertTrue($model->login(),'Login with LDAP failed');        
        
        $this->sendtoStdErr(Yii::$app->user->identity->queryLdapUserObject()['attributes']['samaccountname'][0]);
    }
}
