<?php

class EldapTest extends TestCase
{

    /**
     * @return Mailer test email component instance.
     */
    protected function createTestLdapComponent()
    {
        $component = new Edvlerblog\Ldap([
        ]);
        return $component;
    }

    protected function setUp()
    {
        $this->mockApplication([
            'components' => [
                'ldap' => [
                    'class' => 'Edvlerblog\Ldap',
                    'options' => [
                        'account_suffix' => '@gatech.edu',
                        'domain_controllers' => ['whitepages.gatech.edu'],
                        'base_dn' => 'dc=whitepages,dc=gatech,dc=edu',
                        'admin_username' => '',
                        'admin_password' => '',
                    ]
                ]
            ]
        ]);
    }

    protected function tearDown()
    {
        $this->destroyApplication();
    }

    // tests
    public function testConfigure()
    {
        $this->assertFalse(
		\Yii::$app->ldap->authenticate("username", "password")
	);

    }
}
