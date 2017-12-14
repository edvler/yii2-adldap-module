<?php

include_once('TestConfig.php');

use yii\di\Container;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase {

    protected function setUp() {
        parent::setUp();
        $this->mockApplication();
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown() {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($appClass = '\yii\web\Application') {
        $config = TestConfig::$ADLDAP_CONFIG;
                
        $config['basePath'] = __DIR__;
        $config['vendorPath'] = dirname(__DIR__) . '/vendor';
        
        new $appClass($config);
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication() {
        Yii::$app = null;
        Yii::$container = new Container();
    }

    protected function sendtoStdErr($msg) {
        fwrite(STDERR, print_r($msg, TRUE));
    }
    
    public function checkAndDeleteUser() {
        // check if account exists
        $userObject = \Yii::$app->ad->search()->findBy('sAMAccountname', TestVariables::$TEST_USER_ACCOUNT_NAME);
        if($userObject != null && $userObject->exists) {
            // delete if exists
            $this->assertTrue($userObject->delete());
        }
        
        // check if group exists
        $groupObject = \Yii::$app->ad->search()->findBy('cn', TestVariables::$TEST_GROUP_NAME);
        if($groupObject != null && $groupObject->exists) {
            // delete if exists
            $this->assertTrue($groupObject->delete());
        }

        // check if nested group exists
        $nestedgroupObject = \Yii::$app->ad->search()->findBy('cn', TestVariables::$TEST_NESTED_GROUP_NAME);
        if($nestedgroupObject != null && $nestedgroupObject->exists) {
            // delete if exists
            $this->assertTrue($nestedgroupObject->delete());
        }           
    }      
}
