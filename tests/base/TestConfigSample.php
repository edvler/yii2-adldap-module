<?php
class TestConfig {
    public static $ADLDAP_CONFIG = [
            'id' => 'testapp',
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
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
                                'account_suffix' => 'test.lan',
                                'hosts' => ['srv1.test.lan'],
                                'base_dn' => 'dc=test,dc=lan',
                                'username' => 'yii2binduser@test.lan',
                                'password' => 'PWD_GOES_HERE',
                                // See docs/SSL_TLS_AD.md
                                // SSL and TLS needed to create a user with password
                                'port' => 636,
                                'use_ssl' => true,
                                'use_tls' => true,
                            ]
                        ],
                    ],
                ],
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'mysql:host=localhost;dbname=adldap-test',
                    'username' => 'root',
                    'password' => 'PWD_GOES_HERE',
                    'charset' => 'utf8'
                ],
            ]
        ];

    // variables are used by tests suites
    public static $TEST_USER_PASSWORD = 'PWD_GOES_HERE';
    public static $TEST_USER_PRINCIPAL_NAME = 'snoopstein@test.lan';
}
