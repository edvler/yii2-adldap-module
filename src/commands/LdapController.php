<?php
namespace Edvlerblog\commands;

use Yii;
use yii\console\Controller;
use app\klipa\ldap\LdapDbUser;
use Adldap\Objects\AccountControl;

class LdapController extends Controller
{
    /**
     * Create a role with the name yii2_example_group and assign the permissions permissionDisplayDetailedAbout and permissionToUseContanctPage
     */
    public function actionCreateExampleRole()
    {
        $auth = Yii::$app->authManager;

        // add "permissionDisplayDetailedAbout" permission
        $displayDetailedAbout = $auth->createPermission('permissionDisplayDetailedAbout');
        $displayDetailedAbout->description = 'Permission to display detailed about informations';
        $auth->add($displayDetailedAbout);

        // add "permissionToUseContanctPage" permission
        $useContactPage = $auth->createPermission('permissionToUseContanctPage');
        $useContactPage->description = 'Permission to use the contanct page';
        $auth->add($useContactPage);

        // add "yii2_example_group" role and give this role the "permissionDisplayDetailedAbout" and "permissionToUseContanctPage" permission
        $yii2ExampleGroup = $auth->createRole('yii2_example_group');
        $auth->add($yii2ExampleGroup);
        $auth->addChild($yii2ExampleGroup, $displayDetailedAbout);
        $auth->addChild($yii2ExampleGroup, $useContactPage);
        
        
        
        // add "permissionToUseContanctPage" permission
        $useHomePage = $auth->createPermission('permissionToSeeHome');
        $useHomePage->description = 'Permission to use the home page';
        $auth->add($useHomePage);

        // add "yii2_see_home_group" role and give this role the "permissionToSeeHome" permission
        $yii2HomeGroup = $auth->createRole('yii2_see_home_group');
        $auth->add($yii2HomeGroup);
        $auth->addChild($yii2HomeGroup, $useHomePage);    
        
        echo "\n\n!!!! TODO !!!!\nTow roles with the name yii2_example_group and yii2_see_home_group were created in yii2.\nPlease create the groups with the same name in Active Directory.\nAssign the user you are using for the login to this groups in Active Directory.\n";
    }    
    
}
