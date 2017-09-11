<?php
use yii\di\Container;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
    }
    
    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }
    
    
    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($appClass = '\yii\web\Application')
    {
        new $appClass([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ .'/index.php',
                    'scriptUrl' => '/index.php',
                ],                
                'authManager' => [
                    'class' => 'yii\rbac\DbManager',
                ],
                'user' => [
                    'identityClass' => 'Edvlerblog\Adldap2\model\UserDbLdap',
                    'enableAutoLogin' => true,
                ],                
                'ad' => [
                    'class' => 'Edvlerblog\Adldap2\Adldap2Wrapper',
                    'providers' => [
                        'default' => [
                            'autoconnect' => true,
                            'config' => [
                                'account_suffix' => '@test.lan',
                                'domain_controllers' => ['srv1.test.lan', 'srv2.test.lan'],
                                'base_dn' => 'dc=test,dc=lan',
                                'admin_username' => 'USERNAME',
                                'admin_password' => 'PASSWORD',
                            ]
                        ],
                    ],
                ],
                'db' => [    
                    'class' => 'yii\db\Connection',
                    'dsn' => 'mysql:host=localhost;dbname=adldap-test',
                    'username' => 'root',
                    'password' => 'D4tenT0pf',
                    'charset' => 'utf8'
                ],               
            ]            
        ]);
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
        Yii::$container = new Container();
    }
    
    protected function sendtoStdErr($msg) {
        fwrite(STDERR, print_r($msg, TRUE));
    }    
}
