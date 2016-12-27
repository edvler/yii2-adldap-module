<?php
namespace Edvlerblog\model;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use Adldap\Objects\AccountControl;

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
    //Constants for a enabeld/disabled which are saved to database.
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
    
    const SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK = [
            'ON_LOGIN_CREATE_USER' => true,
            'ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS' => true,
            'ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS' => true,
            'ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS' => false,
        ];
    
    const SYNC_OPTIONS_TEMPLATE_ONLY_BACKEND_TASK = [
            'ON_LOGIN_CREATE_USER' => false,
            'ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS' => false,
            'ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS' => false,
            'ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS' => false
        ];    
    
    
    /*
     $regex = "/^(applll)(.*)/"; // start with
        //$regex = "/^(app|user)(.*)/"; // start with
        //$regex = "/(.*)(asdf|zzz)$/"; // end with
        //$regex = "/^(asdf_zzz|ttttt)$/"; // match complete string
        //$regex = "/(fa_z|zz)/"; // containes   
     */
    
    const GROUP_ASSIGNMENT_ADD_ONLY = [
            'REGEX_GROUP_MATCH_IN_LDAP' => "/^(app|user)(.*)/", // start with
            'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true,
            'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => false,
            'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => false,
        ];
    
    const GROUP_ASSIGNMENT_LDAP_MANDANTORY = [
            'REGEX_GROUP_MATCH_IN_LDAP' => "/^(app|user)(.*)/", // start with
            'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true,
            'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => true,
            'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => false,
        ];
 
    const GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX = [
            'REGEX_GROUP_MATCH_IN_LDAP' => "/^(app|user)(.*)/", // start with
            'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true,
            'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => false,
            'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => true,
        ];
    
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
            ['status', 'default', 'value' => self::STATUS_ENABLED],
            ['status', 'in', 'range' => [self::STATUS_ENABLED, self::STATUS_DISABLED]],
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
        
        return self::checkUserEnabled($userObjectDb);
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
     *        'LDAP-User-Sync-Options' => Edvlerblog\model\User::SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK,
     *        ...
     * ];
     * 
     * If the user does not exists in database and the option [[ON_LOGIN_CREATE_USER]] is true
     * a LDAP query would ne done to find the user in LDAP and sync it to database.
     * 
     * If the [[ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS]] option is true, the group assigment is
     * queryied from LDAP and stored in database on login.
     * 
     * If the [[ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS]] option is true, the account status is
     * queryied from LDAP and stored in database on login.
     *
     * @param string $username username of the user object
     * @return Edvlerblog\model\UserDbLdap A User instance if user is valid. Otherwise NULL.
     */
    public static function findByUsername($username)
    {
        $userObjectDb = static::findOne(['username' => $username]); 

        //Create user if not found in db?
        if ($userObjectDb == null && $this->getSyncOptions("ON_LOGIN_CREATE_USER") == true) {
            $userObjectDb = static::createNewUser($username);
            return $userObjectDb;
        }
        
        //Refresh group assignments of user if found in database?
        if ($userObjectDb->username != null && $userObjectDb->getSyncOptions("ON_LOGIN_REFRESH_GROUP_ASSIGNMENTS") == true) {
            $userObjectDb->updateGroupAssignment();
        }
        
        //Refresh account status of user if found in database?
        if ($userObjectDb->username != null && $userObjectDb->getSyncOptions("ON_LOGIN_REFRESH_LDAP_ACCOUNT_STATUS") == true && $userObjectDb->getSyncOptions("ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS") == false) {
            $userObjectDb->updateAccountStatus();
        }        
        
        return self::checkUserEnabled($userObjectDb);
    }
    
    /**
     * Check if a [[Edvlerblog\model\User]] is enabled.
     * 
     * If [[ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS]] option is true, the account status is
     * queryied from LDAP and stored in database on login. 
     * 
     * @param Edvlerblog\model\UserDbLdap $userObjectDb User object to validate.
     * @return Edvlerblog\model\UserDbLdap A User instance if user is valid. Otherwise NULL.
     */
    public static function checkUserEnabled($userObjectDb) {
        if ($userObjectDb == null) {
            return null;
        }
        
        if ($userObjectDb->username != null && $userObjectDb->getSyncOptions("ON_REQUEST_REFRESH_LDAP_ACCOUNT_STATUS") == true) {
            $userObjectDb->updateAccountStatus();
        }
        
        if ($userObjectDb->status == self::STATUS_ENABLED) {
            return $userObjectDb;
        } else {
            return null;
        }        
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
        $passwordValid = \Yii::$app->ldap->authenticate($this->username,$password);
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
    public function getSyncOptions($getOptionByName) {
        $syncOptionsUsed = null;
        
        if($this->individualSyncOptions != null) {
            $syncOptionsUsed = $this->individualSyncOptions;
        } else if(isset(\Yii::$app->params["LDAP-User-Sync-Options"]) && $this->individualSyncOptions == null ) {
            $syncOptionsUsed = \Yii::$app->params["LDAP-User-Sync-Options"];
        } else {
            $syncOptionsUsed = self::SYNC_OPTIONS_TEMPLATE_WITHOUT_BACKEND_TASK;
        }
        
        if (isset($syncOptionsUsed[$getOptionByName]) == true) {
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
    public function getGroupAssigmentOptions($getOptionByName) {
        $groupOptionsUsed = null;
        
        if(isset($this->individualGroupAssignmentOptions) != null) {
            $groupOptionsUsed = $this->individualGroupAssignmentOptions;
        } else if(isset(\Yii::$app->params["LDAP-Group-Assignment-Options"]) && $this->individualGroupAssignmentOptions == null ) {
            $groupOptionsUsed = \Yii::$app->params["LDAP-Group-Assignment-Options"];
        } else {
            $groupOptionsUsed = self::GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX;
        }
        
        if (isset($groupOptionsUsed[$getOptionByName]) == true) {
            return $groupOptionsUsed[$getOptionByName];
        } else {
            throw new \yii\base\Exception("Option " . $getOptionByName . " not found. See const MODE_TEMPLATES variable in Class LdapDbUser for example. Current options used: " . print_r($groupOptionsUsed,true));
        }
    }    
    
    /**
     * Create a new object in database.
     * 
     * @param string $username username of the LDAP user.
     * @return Edvlerblog\model\UserDbLdap object. Null if the username is not found in LDAP.
     */
    public static function createNewUser($username) {
        $userObjectDb = new UserDbLdap();
        //Username has to be set before a LDAP query
        $userObjectDb->username = $username;
        
        //Check if user exists in LDAP.
        if($userObjectDb->queryLdapUserObject() == null) {
            return null;
        }
        
        $userObjectDb->generateAuthKey();
        $userObjectDb->updateAccountStatus();
        $userObjectDb->updateGroupAssignment();
        $userObjectDb->save();
        
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
            $this->status = self::STATUS_DISABLED;
        } else {
            //Query account status from active directory
            $ldapAccountState = $ldapUser->getUserAccountControl();

            $disabledUser = ($ldapAccountState & AccountControl::ACCOUNTDISABLE) === AccountControl::ACCOUNTDISABLE;
            $lockedUser = ($ldapAccountState & AccountControl::LOCKOUT) === AccountControl::LOCKOUT;
            $pwExpired = ($ldapAccountState & AccountControl::PASSWORD_EXPIRED) === AccountControl::PASSWORD_EXPIRED;

            if($disabledUser == true || $lockedUser == true || $pwExpired == true) {
                $this->status = self::STATUS_DISABLED;
            } else {
                $this->status = self::STATUS_ENABLED;
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
     *        'LDAP-Group-Assignment-Options' => Edvlerblog\model\User::GROUP_ASSIGNMENT_TOUCH_ONLY_MATCHING_REGEX,
     *        ...
     * ];
     * 
     * @return Role[] all roles directly assigned to the user. The array is indexed by the role names.
     */
    public function updateGroupAssignment() {
        \Yii::beginProfile('LDAP updateGroupAssignment function');
        $ldapUser = $this->queryLdapUserObject();
        if ($ldapUser == null) {
            \Yii::endProfile('LDAP updateGroupAssignment function');
            return null;
        }
        
        $ldapGroups = $ldapUser->getMemberOf();
        $ldapGroupsConverted = [];
        $yiiAviliableRoles = \Yii::$app->authManager->getRoles();
        $rolesAssignedToUser = \Yii::$app->authManager->getRolesByUser($this->getId());
        
        //Map groups from LDAP to roles and add to user object.
        if ($this->getGroupAssigmentOptions("ADD_GROUPS_FROM_LDAP_MATCHING_REGEX") == true) {
            foreach ($ldapGroups as $ldapGroup) {
                $gn = $ldapGroup->getCommonName();
                $ldapGroupsConverted[$gn] = false; //index ldap group array for the further comparisons
                
                if(preg_match($this->getGroupAssigmentOptions("REGEX_GROUP_MATCH_IN_LDAP"),$gn) == true) {
                    $ldapGroupsConverted[$gn] = true;
                    
                    if(isset($yiiAviliableRoles[$gn]) && !isset($rolesAssignedToUser[$gn])) {
                        $auth = \Yii::$app->authManager;
                        $role = $auth->getRole($gn);
                        $auth->assign($role, $this->getId());                
                    }       
                }
            }
        }
        
        //Remove all roles from user object which are not in LDAP
        if ($this->getGroupAssigmentOptions("REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP") == true && $this->getGroupAssigmentOptions("REMOVE_ONLY_GROUPS_MATCHING_REGEX") == false) {
            foreach ($rolesAssignedToUser as $role) {
                if(isset($ldapGroupsConverted[$role->name]) == false) {
                        $auth = \Yii::$app->authManager;
                        $auth->revoke($role, $this->getId());                     
                }
            }            
        }
        
        //Remove all roles from user object which are matching the regex and are not in LDAP
        if ($this->getGroupAssigmentOptions("REMOVE_ONLY_GROUPS_MATCHING_REGEX") == true) {
            foreach ($rolesAssignedToUser as $role) {
                $roleName = $role->name;
                
                if(preg_match($this->getGroupAssigmentOptions("REGEX_GROUP_MATCH_IN_LDAP"),$roleName) == true && isset($ldapGroupsConverted[$roleName]) == false) {
                            $auth = \Yii::$app->authManager;
                            $auth->revoke($role, $this->getId());                     
                }
            }            
        }        
        
        \Yii::endProfile('LDAP updateGroupAssignment function');
        
        //Return assigned roles.
        return \Yii::$app->authManager->getRolesByUser($this->getId());
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
            
            $userObjectsFound = \Yii::$app->ldap->search()->where('samaccountname', '=', $this->username)->get();   
            
            if(count($userObjectsFound) != 1) {
                $this->ldapUserObject = null;
            } else {
                $this->ldapUserObject = $userObjectsFound[0];
            }
        }
         \Yii::endProfile('LDAP queryLdapUserObject function');
        return $this->ldapUserObject;
    }
}
