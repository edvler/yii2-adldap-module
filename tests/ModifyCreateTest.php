<?php
include_once ('base\TestVariables.php');
require_once(__DIR__ . '/../../../../models/LoginForm.php');
use app\models\LoginForm;

class ModifyCreateTest extends TestCase
{
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
        $ldapmodel->username = TestVariables::$TEST_USER;
        return $ldapmodel;
    }
    
    public function testUpdateDisplayName() {
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER);
        $this->assertEquals($userObject['attributes']['displayname'][0],TestVariables::$TEST_DISPLAY_NAME,'Display name wrong!');   
        
        $userObject->setDisplayName(TestVariables::$TEST_DISPLAY_NAME_MOD);
        $this->assertTrue($userObject->save(),'Update displayname failed');
        $this->assertEquals($userObject['attributes']['displayname'][0],TestVariables::$TEST_DISPLAY_NAME_MOD,'Display name wrong after Update!');

        $userObject->setDisplayName(TestVariables::$TEST_DISPLAY_NAME);
        $this->assertTrue($userObject->update(),'Update displayname failed');
        $this->assertEquals($userObject['attributes']['displayname'][0],TestVariables::$TEST_DISPLAY_NAME,'Display name wrong after Update!');  
    }

}
