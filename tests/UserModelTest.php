<?php

require_once(__DIR__ . '/../../../../models/LoginForm.php');
use app\models\LoginForm;

class UserModelTest extends TestCase
{
    private $testUsername = 'yii2testuser';
    private $testPassword = 'TestTest123';
    private $testGivenName = 'Yii2';
    private $testGroupName = 'yii2_example_group';
    private $testNestedGroupName = 'yii2_nested_group';
    
    private $testDisabledUser = 'yii2testuserdis';
    
    //After each test clear
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
        $this->assertContains($this->testGroupName, $groups,'Group ' . $this->testGroupName . ' is missing!');
    }
    
    public function testFindByUsernameDisabledUser() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testDisabledUser);

        $this->assertNull($userObject,'A disabled user cannot be returned by findByUsername');
    }
    
    public function testFindByUsername() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userName = $userObject->queryLdapUserObject()['attributes']['samaccountname'][0];
        
        $this->assertEquals($userName,$this->testUsername,'No correct instance of the test user ' . $this->testUsername . ' returned by findByUsername');
    }     
    
    public function testFindIdentityNotExistingUserId() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findIdentity(9999);

        $this->assertNull($userObject,'Not existing identity cannot be found!');
    }
    
    public function testFindIdentityWithId() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userObjectById = Edvlerblog\Adldap2\model\UserDbLdap::findIdentity($userObject->getId());

        $userName = $userObjectById->queryLdapUserObject()['attributes']['samaccountname'][0];
        
        $this->assertEquals($userName,$this->testUsername,'No correct instance of the test user ' . $this->testUsername . ' returned by findByUsername');
    }
    
    public function testUpdateAccountStatus() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userObject->status = Edvlerblog\Adldap2\model\UserDbLdap::STATUS_DISABLED;
        $userObject->save();
        
        $this->assertEquals($userObject->status,Edvlerblog\Adldap2\model\UserDbLdap::STATUS_DISABLED, 'User should be disabled.');
        $userObject->updateAccountStatus();
        
        $this->assertEquals($userObject->status,Edvlerblog\Adldap2\model\UserDbLdap::STATUS_ENABLED, 'User should be reenabled after updateAccountStatus.');
    }

    public function testCheckAllowedToLoginWithNullUser() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername('NOTEXISTINGACCOUNT');
        $userObject2 = Edvlerblog\Adldap2\model\UserDbLdap::checkAllowedToLogin($userObject);
        
        $this->assertEquals($userObject,$userObject2, 'The object returned for a successfull login by checkAllowedToLogin has to be euqal to the instance given as parameter');
    }   
    
    public function testCheckAllowedToLogin() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $userObject2 = Edvlerblog\Adldap2\model\UserDbLdap::checkAllowedToLogin($userObject);
        
        $this->assertEquals($userObject,$userObject2, 'The object returned for a successfull login by checkAllowedToLogin has to be euqal to the instance given as parameter');
    }
    
    public function testUpdateGroupAssignment() {
        $userObject = Edvlerblog\Adldap2\model\UserDbLdap::findByUsername($this->testUsername);
        $yiiRolesAssignedToUser = Yii::$app->authManager->getRolesByUser($userObject->getId()); //Get all roles assigned to user
        
        //User has only group yii2_example_group assinged.
        $this->assertArrayHasKey($this->testGroupName,$yiiRolesAssignedToUser,'Role ' . $this->testGroupName . ' has to be assigned.');
        $this->assertArrayNotHasKey($this->testNestedGroupName,$yiiRolesAssignedToUser,'Nested group ' . $this->testNestedGroupName . ' has NOT to be assigned.');
        
        //Search for nested groups
        $userObject->setIndividualGroupAssignmentOptions(
                ['SEARCH_NESTED_GROUPS' => true]
                );
        
        //Test nested Group search. 
        //Nested group cannot bes assigned to user beacause no role in yii2 exists.
        $groupsPossible = $userObject->getGroupsAssignedInLdap();
        
        $this->assertContains($this->testGroupName,$groupsPossible,'Group ' . $this->testGroupName . ' has to be found in AD.');
        $this->assertContains($this->testNestedGroupName,$groupsPossible,'Nested group ' . $this->testNestedGroupName . ' has to be found in AD.');
    }    
    
    public function testUserTestLogin() {
        $model = new LoginForm();
        
        //Test a real login
        $this->assertTrue($model->load(['LoginForm'=> ['username' => $this->testUsername, 'password' => $this->testPassword]]),'Load data into model failed');
        $this->assertTrue($model->login(),'Login with LDAP failed');
        $this->assertFalse(Yii::$app->user->isGuest,'User is not logged in');
        
        //Save id
        $idOfUser = Yii::$app->user->getId();      
        
        //Simulate a request after a successfull Login
        $userObjectById = Edvlerblog\Adldap2\model\UserDbLdap::findIdentity($idOfUser);
        $userName = $userObjectById->queryLdapUserObject()['attributes']['samaccountname'][0];
        $this->assertEquals($userName,$this->testUsername,'No correct instance of the test user ' . $this->testUsername . ' returned by queryLdapUserObject');
        
        //Logout
        Yii::$app->user->logout();
        $this->assertTrue(Yii::$app->user->isGuest,'User is not a guest');
        
        //Try a second login
        $this->assertTrue($model->load(['LoginForm'=> ['username' => $this->testUsername, 'password' => $this->testPassword]]),'Load data into model failed');
        $this->assertTrue($model->login(),'Login with LDAP failed');
        
        //Try permissions
        $this->assertTrue(Yii::$app->user->can('permissionDisplayDetailedAbout'),'Permission cannot be found');
    }
}
