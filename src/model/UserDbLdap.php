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

namespace Edvlerblog\Adldap2\model;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use Adldap\Objects\AccountControl;
use Adldap\Utilities;

/**
 * User model with database integration and LDAP synchronistation.
 *
 * @property integer $id
 * @property string $username
 * @property integer $status
 * @property string $auth_key
 * @property integer $created_at
 * @property integer $updated_at
 */
class UserDbLdap extends ActiveRecord implements IdentityInterface
{        
    /**
     * Constants starting with SYNC_OPTIONS_****
     * This constant defines all options needed, that are belogn to the login behavior
     * of the UserDbLdap.
     * 
     * The main purpose of this constant is to define when a LDAP query is done and when not.
     * For example you can decide, if a user is not found in the yii2 database, if a LDAP query should search
     * for the user and create it, or if the login simply fails.
     * Another example is, if a LDAP query should be issued on login to refresh the group assignments.
     * 
     * As you can imagine updating group assignments and updating the account status could be a 
     * time consuming task. This is why you can define what should be done on every site request
     * and on login.
     * 
     * If you run a backend task, which querys the informations 
     * from active directory every X minutes with cron or a another scheduler,
     * you can completly deactivate all refreshs on login. See constant SYNC_OPTIONS_TEMPLATE_ONLY_BACKEND_TASK
     * for example.
     * 
     * You can configure your own settings in the config/params.php too.
     * 
     *   With predefined constant
     *   
     *   return [
     *       //...
     *       'LDAP-User-Sync-Options' => Edvlerblog\Adldap2\model\UserDbLdapDbLdap::SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK,
     *       'LDAP-Group-Assignment-Options' => Edvlerblog\Adldap2\model\UserDbLdapDbLdap::GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX,
     *       //...
     *   ];
     * 
     *   With complete own settings
     * 
     *   return [
     *       //...
     *       'LDAP-User-Sync-Options' => [
     *                                       'ON_LOGIN_CREATE_USER' => false,
     *                                       'ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS' => false,
     *                                       'ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS' => true,
     *                                       'ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS' => false,
     *                                   ],
     *       'LDAP-Group-Assignment-Options' => Edvlerblog\Adldap2\model\UserDbLdapDbLdap::GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX,
     *       //...
     *   ]; 
     * 
     */
    
    
    /**
     * Constant SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK
     * 
     * This constant is DEFAULT if you doesn't configure anything.
     * - On login a user is automatically created, if it not exists in database
     * - On login the group assignments are refreshed
     * - On login the account status is refreshed
     * 
     * In simple words: 
     * If you login with a Active Directory user, which is active, and the password matches you can login!
     * On every login the above mentioned points are checked. 
     * For example: If a user is deactived, the next login would fail but the current session would be valid until logout.
     * 
     * One word of caution! There are further options which beginning with GROUP_ASSIGNMENT_***. This are also influence the login behavior.
     */
    const SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK = [
            /* ON_LOGIN_CREATE_USER
             * If this is true and the user is not found in database (maybe first login or other reasons),
             * a LDAP query would search for in Active Directory.
             * 
             * If the user is found a new UserDbLdap object would be created, the status is refreshed,
             * and the groups are assigned according to the rules defined in GROUP_ASSIGNMENT_**** settings.
             * 
             * If the password matches a login is possible. For the user it seems like a normal login.
             */
            'ON_LOGIN_CREATE_USER' => true,
        
            /*
             * ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS
             * If this is set to true. Evertime a user login is done,
             * the group assignments are refreshed according to the rules defined in GROUP_ASSIGNMENT_**** settings.
             */
            'ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS' => true,
        
            /*
             * ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS
             * If this is set to true. Evertime a user login is done,
             * the status of the user account in Active Directory is queried and stored in database.
             */
            'ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS' => true,
        
             /*
             * ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS
             * If this is set to true. Evertime a PAGE REFRESH is done,
             * the status of the user account in Active Directory is queried and stored in database.
             */       
            'ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS' => false,
        ];
    
    
    
    /**
     * Constant SYNC_OPTIONS_TEMPLATE_ONLY_BACKEND_TASK
     * 
     * On login nothing would be done. If the user doesn't exists or the roles assigned to 
     * the user are not allowed to login.
     * 
     * You has to configure the backend task, which imports all users from active directory.
     * Please configure the basic's as described here in the folder docs/USAGE_WITH_USER_MODEL.md.
     * 
     * Follow this steps to run the backend task:
     * cmd> cd c:\xampp\htdocs\basic
     * cmd> yii ldapcmd/import-all-users
     * 
     * This command should import all users, which are matching the configured group assignment options.
     * See GROUP_ASSIGNMENT_** options.
     * 
     */    
    const SYNC_OPTIONS_TEMPLATE_ONLY_BACKEND_TASK = [
            'ON_LOGIN_CREATE_USER' => false,
            'ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS' => false,
            'ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS' => false,
            'ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS' => false
        ];    

    
     /**
     * Constants starting with GROUP_ASSIGNMENT_****
     * This constant defines all options needed, that are influence the Active Directory group to yii2 role matching
     * of the UserDbLdap class.
     * 
     * The first main purpose of this constant is to define, if a login without a assigned role is possible.
     * 
     * The second purpose is to define which groups are matched to roles. This enables you to match only certain
     * groups to roles. DON'T forget to create a role with the same name as the group in Active Directory.
     * 
     * The third purpose is to define how to deal with roles not matching a LDAP group name (remove them or don't touch them).
     *
      * 
      * 
     * You can configure your own settings in the config/params.php
     * 
     *   With predefined constant
     *   
     *   return [
     *       //...
     *       'LDAP-Group-Assignment-Options' => Edvlerblog\Adldap2\model\UserDbLdapDbLdap::GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX_WITH_ROLE,
     *       //...
     *   ];
     * 
     *   With complete own settings
     * 
     *   return [
     *       //...
     *       'LDAP-Group-Assignment-Options' => [
     *                                           'LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX' => "/(.*)/",
     *                                           'REGEX_GROUP_MATCH_IN_LDAP' => "/^(yii2|app)(.*)/", // start with
     *                                           'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true,
     *                                           'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => true,
     *                                           'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => false,
     *                                           'SEARCH_NESTED_GROUPS' => false
     *                                       ],
     *       //...
     *   ]; 
     *
     * 
     * Some options use regex. Here are some exmaples for common use cases:
     * $regex = "/^(yii2)(.*)/"; // Evaluates to true if the beginning of the groupname is yii2. Example yii2_create_post gives true
     * $regex = "/^(yii2|app)(.*)/"; // Evaluates to true if the beginning of the groupname is yii2 OR app. Example yii2_create_post gives true, app_create_post gives true
     * $regex = "/(.*)(yii2)$/"; //  Evaluates to true if the end of the groupname is yii2. Example create_post_yii2 gives true
     * $regex = "/(.*)(yii2|app)$/"; // Evaluates to true if the end of the groupname is yii2 OR app. Example create_post_yii2 gives true, create_post_app gives true
     * $regex = "/^(yii2_complete_group_name)$/"; // Evaluates to true if the complete groupname matches yii2_complete_group_name
     * $regex = "/^(yii2_complete_group_name|another_complete_group_name)$/"; // Evaluates to true if the complete groupname matches yii2_complete_group_name OR another_complete_group_name
     * $regex = "/(yii2)/"; // Evaluates to true if the groupname contains yii2. Example group_yii2_post gives true
     * $regex = "/(yii2|app)/"; // Evaluates to true if the groupname contains yii2. Example group_yii2_post gives true, Example group_app_post gives true,
     * $regex = "/(.*)/"; // Evaluates to true on every groupname
     */   
    
    
    /**
     * GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX constant
     * If you don't define your own settings the constant GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX is used as default.
     * 
     * In short words the settings does the following:
     *  - Login is possible without any role assinged
     *  - Active Directory groups starting with yii2 and matching (name has to be euqal!) a yii2 role are added to the user
     *  - Groups removed in Active Directory are removed in yii2 to. Groups not matching the REGEX_GROUP_MATCH_IN_LDAP are not touched.
     */
    const GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX = [
            /*
             * LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX
             * If the value of this key is null, a user can login without a role assinged!
             * 
             * If a regex is given, a role has to be assinged that matches the regex given for this key to successfully login.
             * 
             * For example with the regex
             * 'LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX' => "/(.*)/";
             * a user can only login if a role is assigned. But the name can be anything.
             * 
             * With this regex
             * 'LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX' => "/^(yii2)(.*)/";
             * a user can only login if a role is assigned which name is starting with yii2.
             */
            'LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX' => null, // no role necceassarry for login
        
            /*
             * REGEX_GROUP_MATCH_IN_LDAP
             * If a role exists in yii2 which matches a LDAP group (names has to be the same!), it is assigned to the user.
             * The LDAP groups can be filtered with a regex to match only certain groups from LDAP.
             * 
             * In this example only groups starting with yii2 and app would be assigned to the user
             * if a corresponding role (again names has to be the same!) exists in yii2.
             */
            'REGEX_GROUP_MATCH_IN_LDAP' => "/^(yii2|app)(.*)/", // groupname start with yii2 or app
        
            /*
             * ADD_GROUPS_FROM_LDAP_MATCHING_REGEX
             * Groups from Active Directory would be matched with roles in yii2 and added as described in REGEX_GROUP_MATCH_IN_LDAP.
             * 
             * Simple words:
             * If you add a group in Active Directory it would be added in yii too.
             */
            'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true,
        
            /*
             * REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP
             * If this option is true, all roles NOT matching a LDAP group would be remove from the
             * user.
             * 
             * If a role does NOT exists in Active Directory it would be removed.
             * As as result the user only have roles assigned, which are exists as groups in Active Directory, the user is member of and which
             * matches a role in yii2.
             */
            'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => false,
        
            /*
             * REMOVE_ONLY_GROUPS_MATCHING_REGEX
             * If this option is true, only roles would be removed from the user which matches the regex given 
             * under REGEX_GROUP_MATCH_IN_LDAP.
             * 
             * This means the user always have the roles which are assingned as groups in Active Directory.
             * If you remove one, it would be removed in yii2 too.
             * But other roles is yii2 would not be touched.
             */        
            'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => true,
        
            /*
             * Check for groups, which are not directly assigned to the user, 
             * but assigned to another group in which the user is member of.
             * (keywords: nested groups, group tree, group as member of group).
             */
            'SEARCH_NESTED_GROUPS' => false        
        ];    

    
    /**
     * GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX_WITH_ROLE constant
     * Same as GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX.
     * 
     * The only differnce is, that a login is only allowed with a role assigned.
     */    
    const GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX_WITH_ROLE = [
            'LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX' => "/(.*)/", // a role has to be assign, the name could be everything
            'REGEX_GROUP_MATCH_IN_LDAP' => "/^(yii2|app)(.*)/", // start with
            'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true,
            'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => true,
            'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => false,
            'SEARCH_NESTED_GROUPS' => false  
        ];    

    /**
     * GROUP_ASSIGNMENT_LDAP_MANDANTORY constant
     * All roles that are not found in active directory as group will be removed from the user object.
     * 
     * The roles assigned to the user object are always the same as the member of attribute of the active directory user.
     */      
    const GROUP_ASSIGNMENT_LDAP_MANDANTORY = [
            'LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX' => "/(.*)/",
            'REGEX_GROUP_MATCH_IN_LDAP' => "/^(yii2|app)(.*)/", // start with
            'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true,
            'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => true,
            'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => false,
            'SEARCH_NESTED_GROUPS' => false  
        ];
    
    /**
     * Constants for a enabeld/disabled which are saved to database.
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;    
    
    private $ldapUserObject = null;
    private $individualSyncOptions = null;
    private $individualGroupAssignmentOptions = null;  
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => static::STATUS_ENABLED],
            ['status', 'in', 'range' => [static::STATUS_ENABLED, static::STATUS_DISABLED]],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }    
    
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        //Database check. If no dataset is found then the only possible return value is null.
        $userObjectDb = static::findOne(['id' => $id]);
        
        return static::checkAllowedToLogin($userObjectDb);
    }

    /**
     * Finds user by username
     * 
     * Depending on the synchronisation options additional LDAP querys are done.
     * 
     * For a description of the options see the top of this class, where templates (e.g. SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK) are defined.
     * This Templates can be used directly in params or you can define each param by yourself.         
     * 
     * Example config/params.php
     * return [
     *        ...
     *        'LDAP-User-Sync-Options' => Edvlerblog\Adldap2\model\UserDbLdap::SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK,
     *        ...
     * ];
     * 
     * If the user does not exists in database and the option [[ON_LOGIN_CREATE_USER]] is true
     * a LDAP query would be done to find the user in LDAP and sync it to database.
     * 
     * If the [[ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS]] option is true, the group assigment is
     * queryied from LDAP and stored in database on login.
     * 
     * If the [[ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS]] option is true, the account status is
     * queryied from LDAP and stored in database on login.
     *
     * @param string $username username of the user object
     * @return Edvlerblog\Adldap2\model\UserDbLdapDbLdap A User instance if user is valid. Otherwise NULL.
     */
    public static function findByUsername($username)
    {
        $userObjectDb = static::findOne(['username' => $username]); 

        //Create user if not found in db?
        if ($userObjectDb == null) {
            //Just create to get synchronisation options
            $userObjectDb = new static();
            
            if(static::getSyncOptions("ON_LOGIN_CREATE_USER", $userObjectDb->individualSyncOptions) == true) {
                $userObjectDb = static::createNewUser($username);
            } else {
                $userObjectDb = null;
            }
            return $userObjectDb;
        }
        
        //Refresh group assignments of user if found in database?
        if ($userObjectDb->username != null && static::getSyncOptions("ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS", $userObjectDb->individualSyncOptions) == true) {
            $userObjectDb->updateGroupAssignment();
        }
        
        //Refresh account status of user if found in database?
        if ($userObjectDb->username != null && static::getSyncOptions("ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS", $userObjectDb->individualSyncOptions) == true && static::getSyncOptions("ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS", $userObjectDb->individualSyncOptions) == false) {
            $userObjectDb->updateAccountStatus();
        }        
        
        return static::checkAllowedToLogin($userObjectDb);
    }
    
    /**
     * Check if a [[Edvlerblog\Adldap2\model\UserDbLdap]] is allowed to login.
     * Two checks are done before a user object is returned.
     * 
     * 1. Check if user is enabled
     * If [[ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS]] option is true, the account status is
     * queryied ON EVERY REQUEST from LDAP and stored in database on login.
     * 
     * 2. Check if the user has a role assigned which is allowed to login
     * See Parameter LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX 
     * 
     * @param Edvlerblog\Adldap2\model\UserDbLdapDbLdap $userObjectDb User object to validate.
     * @return Edvlerblog\Adldap2\model\UserDbLdapDbLdap A User instance if user is valid. Otherwise NULL.
     */
    public static function checkAllowedToLogin($userObjectDb) {
        if ($userObjectDb == null) {
            return null;
        }
        
        //Refresh account status on every request?
        if ($userObjectDb->username != null && static::getSyncOptions("ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS", $userObjectDb->individualSyncOptions) == true) {
            $userObjectDb->updateAccountStatus();
        }
        
        //Login only possible if a role is assigned which matches the LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX regex
        if ($userObjectDb->status == static::STATUS_ENABLED && static::getGroupAssigmentOptions("LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX",$userObjectDb->individualGroupAssignmentOptions) != null) {
            $rolesAssignedToUser = \Yii::$app->authManager->getRolesByUser($userObjectDb->getId());
            
            foreach ($rolesAssignedToUser as $role) {
                if(preg_match(static::getGroupAssigmentOptions("LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX",$userObjectDb->individualGroupAssignmentOptions),$role->name) == true) {
                    return $userObjectDb;
                }
            }
        }
        
        //Login possible if no role is assigned
        if ($userObjectDb->status == static::STATUS_ENABLED && static::getGroupAssigmentOptions("LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX",$userObjectDb->individualGroupAssignmentOptions) == null) {
            return $userObjectDb;
        }
        
        return null;
    }
    
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }   
    
    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        if ($this->auth_key == null) {
            $this->generateAuthKey();
            $this->save();
        }
        return $this->auth_key;
    }
    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
    
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        \Yii::beginProfile('LDAP validatePassword function');
        $passwordValid = $this->getAdldap2Provider()->auth()->attempt($this->username,$password);
        \Yii::endProfile('LDAP validatePassword function');
        return $passwordValid;
    }
    
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }  
    
    /**
     * Set a individual LDAP synchronisation configuration object for this object.
     * 
     * @param type $individualSyncOptions See SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK in the code for possible options.
     */
    public function setIndividualSyncOptions($individualSyncOptions) {
        $this->individualSyncOptions = $individualSyncOptions;
    }     
    
    /**
     * Get a value of the synchronisation options by option key.
     * 
     * @param string $getOptionByName The option key of the value to retrive.
     * @return mixed The value of the option key
     * @throws \yii\base\Exception if option key is not found in the given option set.
     */
    public static function getSyncOptions($getOptionByName, $individualSyncOptions = null) {
        $syncOptionsUsed = null;
        
        if($individualSyncOptions != null) {
            $syncOptionsUsed = $individualSyncOptions;
        } else if(isset(\Yii::$app->params["LDAP-User-Sync-Options"]) && $individualSyncOptions == null ) {
            $syncOptionsUsed = \Yii::$app->params["LDAP-User-Sync-Options"];
        } else {
            $syncOptionsUsed = static::SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK;
        }
        
        if (array_key_exists($getOptionByName,$syncOptionsUsed) == true) {
            return $syncOptionsUsed[$getOptionByName];
        } else {
            throw new \yii\base\Exception("Option " . $getOptionByName . " not found. See const MODE_TEMPLATES variable in Class LdapDbUser for example. Current options used: " . print_r($syncOptionsUsed,true));
        }
    }

    /**
     * Set a individual LDAP group assignment configuration object for this object.
     * 
     * @param type $individualGroupAssignmentOptions See GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX in the code for possible options.
     */
    public function setIndividualGroupAssignmentOptions($individualGroupAssignmentOptions) {
        $this->individualGroupAssignmentOptions = $individualGroupAssignmentOptions;
    }     
    
    /**
     * Get a value of the group assignment options by option key.
     * 
     * See function updateGroupAssignment for further explanation.
     * 
     * @param string $getOptionByName The option key of the value to retrive.
     * @return mixed The value of the option key
     * @throws \yii\base\Exception if option key is not found in the given option set.
     */
    public static function getGroupAssigmentOptions($getOptionByName, $individualGroupAssignmentOptions = null) {
        $groupOptionsUsed = null;
        
        if(isset($individualGroupAssignmentOptions) != null) {
            $groupOptionsUsed = $userDbLdapObject->individualGroupAssignmentOptions;
        } else if(isset(\Yii::$app->params["LDAP-Group-Assignment-Options"]) && $individualGroupAssignmentOptions == null ) {
            $groupOptionsUsed = \Yii::$app->params["LDAP-Group-Assignment-Options"];
        } else {
            $groupOptionsUsed = static::GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX;
        }
        
        if (array_key_exists($getOptionByName,$groupOptionsUsed) == true) {
            return $groupOptionsUsed[$getOptionByName];
        } else {
            throw new \yii\base\Exception("Option " . $getOptionByName . " not found. See const MODE_TEMPLATES variable in Class LdapDbUser for example. Current options used: " . print_r($groupOptionsUsed,true));
        }
    }    
    
    /**
     * Create a new object in database.
     * 
     * @param string $username username of the LDAP user.
     * @return Edvlerblog\Adldap2\model\UserDbLdapDbLdap object. Null if the username is not found in LDAP.
     */
    public static function createNewUser($username,$individualGroupAssignmentOptions = null) {
        $userObjectDb = new static();

        //Username has to be set before a LDAP query
        $userObjectDb->username = $username;
        
        $userObjectDb->setIndividualGroupAssignmentOptions($individualGroupAssignmentOptions);
        
        //Check if user exists in LDAP.
        if($userObjectDb->queryLdapUserObject() == null) {
            return null;
        }
        
        $roles = $userObjectDb->updateGroupAssignment();

        if (count($roles) > 0 || static::getGroupAssigmentOptions("LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX",$userObjectDb->individualGroupAssignmentOptions) == null) {
            $userObjectDb->generateAuthKey();
            $userObjectDb->updateAccountStatus();
            $userObjectDb->save();
        } else {
            $userObjectDb = null;
        }
        
        return $userObjectDb;
    }
    
    /**
     * Check if a user exists. If the user exists the account status and group assigments are refreshed.
     * Otherwise a new user is created.
     * 
     * @param string $username
     * @param array $individualGroupAssignmentOptions
     * @return Edvlerblog\Adldap2\model\UserDbLdapDbLdap object. Null if the username is not found in LDAP.
     */
    public static function createOrRefreshUser($username,$individualGroupAssignmentOptions = null) {
        $userObjectDb = static::findOne(['username' => $username]); 

        //Create user if not found in db
        if ($userObjectDb == null) {        
            return static::createNewUser($username, $individualGroupAssignmentOptions);
        }
        
        $userObjectDb->setIndividualGroupAssignmentOptions($individualGroupAssignmentOptions);
        $userObjectDb->updateAccountStatus();
        $userObjectDb->updateGroupAssignment();
        
        return $userObjectDb;
    }
    
    /**
     * Query LDAP for the current user status and save the information to database.
     * 
     * @return int Status after update
     */
    public function updateAccountStatus() {
        \Yii::beginProfile('LDAP updateAccountStatus function');
        $ldapUser = $this->queryLdapUserObject();
        
        if ($ldapUser == null) {
            //If no user is found in LDAP, disable in database.
            $this->status = static::STATUS_DISABLED;
        } else {
            //Query account status from active directory
            $ldapAccountState = $ldapUser->getUserAccountControl();

            $disabledUser = ($ldapAccountState & AccountControl::ACCOUNTDISABLE) === AccountControl::ACCOUNTDISABLE;
            $lockedUser = ($ldapAccountState & AccountControl::LOCKOUT) === AccountControl::LOCKOUT;
            $pwExpired = ($ldapAccountState & AccountControl::PASSWORD_EXPIRED) === AccountControl::PASSWORD_EXPIRED;

            if($disabledUser == true || $lockedUser == true || $pwExpired == true) {
                $this->status = static::STATUS_DISABLED;
            } else {
                $this->status = static::STATUS_ENABLED;
            }
        }
        
        $this->save();
        
        \Yii::endProfile('LDAP updateAccountStatus function');
        return $this->status;
    }
    
    
    /**
     * Update the group assignment of the user object
     * The \Yii::$app->params["LDAP-Group-Assignment-Options"] has several options how to update the group assignment.
     * 
     * Basicly a query to LDAP is done which returns the groups assigned to the user in the LDAP directory.
     * Depending on the settings in the params groups are added or removed from the user object.
     * 
     * For a description of the options see the top of this class, where templates (e.g. GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX) are defined.
     * This Templates can be used directly in params or you can define each param by yourself.         
     * 
     * Example config/params.php
     * return [
     *        ...
     *        'LDAP-Group-Assignment-Options' => Edvlerblog\Adldap2\model\UserDbLdap::GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX,
     *        ...
     * ];
     * 
     * @return Role[] all roles directly assigned to the user. The array is indexed by the role names.
     */
    public function updateGroupAssignment() {
        \Yii::beginProfile('LDAP updateGroupAssignment function');
        
        $ldapGroupsAssignedToUser = $this->getGroupsAssignedInLdap(); //Query LDAP groups assigned to user
        $yiiRolesAssignedToUser = \Yii::$app->authManager->getRolesByUser($this->getId()); //Get all roles assigned to user
        $yiiAvailableRoles = \Yii::$app->authManager->getRoles(); //Get all avaliable roles in yii2
        
        //Map groups from LDAP to roles and add to user object.
        if (static::getGroupAssigmentOptions('ADD_GROUPS_FROM_LDAP_MATCHING_REGEX',$this->individualGroupAssignmentOptions) == true) {
            foreach ($ldapGroupsAssignedToUser as $gn) {
                if(preg_match(static::getGroupAssigmentOptions('REGEX_GROUP_MATCH_IN_LDAP',$this->individualGroupAssignmentOptions),$gn) == true) {                    
                    if(array_key_exists($gn,$yiiAvailableRoles) && !array_key_exists($gn,$yiiRolesAssignedToUser)) {
                        if ($this->isNewRecord) {
                            $this->generateAuthKey();
                            $this->updateAccountStatus();                        
                            $this->save(); //Save to db to get id from database
                        }
                        $auth = \Yii::$app->authManager;
                        $role = $auth->getRole($gn);
                        $auth->assign($role, $this->getId());                
                    }       
                }
            }
        }
        
        //Remove all roles from user object which are not in LDAP
        if (static::getGroupAssigmentOptions('REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP',$this->individualGroupAssignmentOptions) == true && 
            static::getGroupAssigmentOptions('REMOVE_ONLY_GROUPS_MATCHING_REGEX',$this->individualGroupAssignmentOptions) == false) {
            foreach ($yiiRolesAssignedToUser as $role) {
                if(in_array($role->name,$ldapGroupsAssignedToUser) == false) {
                        $auth = \Yii::$app->authManager;
                        $auth->revoke($role, $this->getId());                     
                }
            }            
        }
        
        //Remove all roles from user object which are matching the regex and are not in LDAP
        if (static::getGroupAssigmentOptions('REMOVE_ONLY_GROUPS_MATCHING_REGEX',$this->individualGroupAssignmentOptions) == true) {
            foreach ($yiiRolesAssignedToUser as $role) {
                $roleName = $role->name;
                
                if(preg_match(static::getGroupAssigmentOptions('REGEX_GROUP_MATCH_IN_LDAP',$this->individualGroupAssignmentOptions),$roleName) == true && 
                   in_array($roleName,$ldapGroupsAssignedToUser) == false) {
                            $auth = \Yii::$app->authManager;
                            $auth->revoke($role, $this->getId());
                }
            }
        }
        
        $rolesAfterUpdate = \Yii::$app->authManager->getRolesByUser($this->getId());
        \Yii::endProfile('LDAP updateGroupAssignment function');
        
        //Return assigned roles.
        return $rolesAfterUpdate;
    }
    
    /**
     * Query all groups assigned to user from Active Directory.
     * If the parameter SEARCH_NESTED_GROUPS = true then all nested groups are
     * respected too.
     * Keep in mind, that a query for nested groups is much slower as a normal query.
     * 
     * @return array with names of groups assigned to user. Empty if no groups found.
     */
    public function getGroupsAssignedInLdap() {
        \Yii::beginProfile('LDAP getGroupsAssignedInLdap function');
        
        $ldapUser = $this->queryLdapUserObject();
        
        if ($ldapUser == null) {
            \Yii::endProfile('LDAP getGroupsAssignedInLdap function');
            return []; //return empty array
        }
        
        $ldapGroupsConverted = []; //start with empty array of groups
        
        //check for nested groups?
        if (static::getGroupAssigmentOptions('SEARCH_NESTED_GROUPS',$this->individualGroupAssignmentOptions) == true) {
            //$ldapGroups=$ldapUser->getGroups(['cn'], $recursive=true); //alternate Query, but slower
            //1.2.840.113556.1.4.1941 = Specical OID to resolve chains
            $ldapGroups = $this->getAdldap2Provider()->search()->rawFilter('(member:1.2.840.113556.1.4.1941:=' . $ldapUser->getDn() . ')')->select('cn')->raw()->get();
            if ($ldapGroups == null) {
                $ldapGroups = [];
            }
            
            //get cn of each group
            foreach ($ldapGroups as $groupDn) {
                if (is_array($groupDn) && array_key_exists('cn', $groupDn)) {
                    array_push($ldapGroupsConverted, $groupDn['cn'][0]);
                }
            }
        } else {
            //get attribute memberof
            $ldapGroups = $ldapUser->getAttribute('memberof');
            if ($ldapGroups == null) {
                $ldapGroups = [];
            }

            //get first part of dn
            foreach ($ldapGroups as $groupDn) {
                $n = Utilities::explodeDn($groupDn)[0];
                array_push($ldapGroupsConverted, $n);
            }
        }

        \Yii::endProfile('LDAP getGroupsAssignedInLdap function');
        return $ldapGroupsConverted;      
    }    
    
    /**
     * Querys the complete user object from LDAP.
     * The username of the object has to be set before a query!
     * 
     * @return \Adldap\models\User
     * @throws \yii\base\Exception if the username is not set and thus no LDAP query based on username can be done.
     */
    public function queryLdapUserObject() {
        \Yii::beginProfile('LDAP queryLdapUserObject function');
        
        if ($this->ldapUserObject == null) {
            if ($this->username == null) {
                throw new \yii\base\Exception("Please set username attribute before calling queryLdapUserObject() function.");
            }

            $userObjectsFound = $this->getAdldap2Provider()->search()->findBy('sAMAccountname', $this->username);
			
            if(count($userObjectsFound) != 1) {
                $this->ldapUserObject = null;
            } else {
                $this->ldapUserObject = $userObjectsFound;
            }
        }
        
        \Yii::endProfile('LDAP queryLdapUserObject function');
        
        return $this->ldapUserObject;
    }
    
    /**
     * Get the Adldap2 provider name
     */
    private function getAdldap2Provider() {
        if(isset(\Yii::$app->params["yii2-adldap-providername"])) {
            $provider =\Yii::$app->ad->getProvider(\Yii::$app->params["yii2-adldap-providername"]);
        } else {
            $provider =\Yii::$app->ad->getDefaultProvider();
        }

        return $provider;
    }
	
    /**
     * Get the password expirytime
     * See: https://msdn.microsoft.com/en-us/library/cc223410.aspx
     */
    public function getPasswordExpiryDate() {
        if ($this->username == null) {
			throw new \yii\base\Exception("Please set username attribute before calling getPasswordExpiryDate() function.");
        }	    
	    
        $provider = $this->getAdldap2Provider();
        $search = $provider->search();  //start a search
        $search = $search->select(['msDS-UserPasswordExpiryTimeComputed']); //Only query this attributes
        $search = $search->where('samaccountname', '=', $this->username);
        $result = $search->get();
        return $result[0]->getFirstAttribute("msds-userpasswordexpirytimecomputed");        
    }	
}
