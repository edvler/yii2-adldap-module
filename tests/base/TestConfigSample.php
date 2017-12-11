<?php
class TestConfig {    
    public static $ADLDAP_CONFIG = [
        'class' => 'Edvlerblog\Adldap2\Adldap2Wrapper',
        'providers' => [
            'default' => [
                'autoconnect' => true,
                'config' => [
                                'account_suffix' => '@test.lan',
                                'domain_controllers' => ['srv1.test.lan', 'srv2.test.lan'],
                                'base_dn' => 'dc=test,dc=lan',
                                'admin_username' => 'AD_USER_WITH_RIGHTS_TO_MODIFY',
                                'admin_password' => 'PW_OF_BIND_USER',
                ]
            ],
        ],
    ];

    public static $TEST_USER_PASSWORD = 'PW_OF_TEST_USER';    
}
