<?php
/**
 * See LICENSE.md distributed with the software package for license informations.
 * 
 * THANKS TO:
 * - Fedek. He wrote a similar plugin for yii 1.
 * - stevebauman. He is maintaining the Adldap2 repository on github.com
 *
 * @category yii2-extension
 * @package yii2-adldap-module
 * @author Matthias Maderer
 * @copyright (c) 2017 Matthias Maderer
 * @link https://github.com/edvler/yii2-adldap-module
 */

namespace Edvlerblog\Adldap2\commands;

use Yii;
use yii\console\Controller;
use Adldap\Utilities;

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
    
    /**
     * Import all users from LDAP, assign roles and account status. Run this command with cron or another scheduler every X minuten/hours/days.
     */
    public function actionImportAllUsers()
    {
        \Yii::warning("-- Starting import from Active Directory --");
        $results = \Yii::$app->ad->getDefaultProvider()->
                search()->
                select("samaccountname")->
                where('objectClass','=','user')->
                where('objectCategory','=','person')->
                paginate(999);
        
        $userNamesToAdd = [];
        foreach($results->getResults() as $ldapUser) {
            $accountName = $ldapUser->getAttribute("samaccountname",0);
            if ($accountName != null) {
                array_push($userNamesToAdd, $accountName);
            }
        }

        
        $c=0;
        \Yii::warning("-- Found " . count ($userNamesToAdd) . " users --");
        foreach ($userNamesToAdd as $userName) {
            $c++;
            
            \Yii::warning("-- Working on user " . $userName . " --");
            $userObject = \Edvlerblog\Adldap2\model\UserDbLdap::createOrRefreshUser($userName);
            
            if ($userObject != null) {
                \Yii::warning("User " . $userName . " created");
            } else {
                \Yii::warning("User " . $userName . " NOT created");
            }
            
        }
        \Yii::warning("-- End import from Active Directory --");
        
     }    
    
}
