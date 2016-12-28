# yii2-adldap-module User model

The User model adds the possibility to authenticate users against Active Directory in yii2 fashion.
It's also possible to match assigned groups to a user in Active Directory to a role in yii2.

You can manage your users **completly** over Active Directory without doing anything in yii2!!
The only thing you have to is assign groups in Active Directory starting with yii2 to the user.
The rest would be **magic**!

But more details later.


## Task 1 - Basic installation
### 1. Configure yii2-adldap-module as described in the main README.md.  
This means your LDAP servers, base_dn and so on are set in the web.conf (basic template) / main.conf (advanced template).

### 2. Configure Database.  
See http://www.yiiframework.com/doc-2.0/guide-start-databases.html#configuring-db-connection

### 3. Configure yii2 RBAC. Please use the yii\rbac\DbManager and don't forgett to apply the migrations mentioned in the docs.  
http://www.yiiframework.com/doc-2.0/guide-security-authorization.html#rbac

  This has to be done: (if you don't know why and what you are doing read the link above!!)  
  
  Execute the rbac migrations in a shell or cmd

        yii migrate --migrationPath=@yii/rbac/migrations

  Add the authManager class to your components.

        'components' => [
            //...
              'authManager' => [
                  'class' => 'yii\rbac\DbManager',
              ],
            //...

### 4. Apply UserDbLdap Migrations. Execute the following command on your shell or cmd

        yii migrate --migrationPath=@Edvlerblog/migrations

### 5. Change the identity class in your web.conf (basic template) / main.conf (advanced template).

        'components' => [
            //...
            'user' => [
                'identityClass' => 'Edvlerblog\model\UserDbLdap',
                //...
            ],
            //...
        
### 6. In the basic template change the models/LoginForm.php to use the new identity class.

        //...
        public function getUser()
        {
            if ($this->_user === false) {
                $this->_user = \Edvlerblog\model\UserDbLdap::findByUsername($this->username);
            }

            return $this->_user;
        }
        //...

### 7. Add the LdapController to the controllerMap in the config/console.php (basic template)

        'controllerMap' => [
            //...
            'ldapcmd' => [
                'class' => 'Edvlerblog\commands\LdapController',
            ],
            //...
        ],
        
Open a shell or a cmd and change to the base directory of your yii2 installation (where the composer.json is located).  
Type in your shell:  
**yii**
You should see a ldapcmd entry with the commands ldapcmd/create-example-role and others.  

### 8. Test the Login of your basic app.  
Now you can go to the login in page of your yii installation (see upper right corner of the website). You can use any Active Directory user which is able to login on the windows login of your PC.  
If everythings okay you should see the username in upper right corner.

    You can do  
    select * from users  
    to check if a user was inserted on login.
    
    With 
    SELECT * FROM auth_assignment;
    you can check if any roles where assigned to your user on login (it is normal that no roles assigned at this point!)


## Task 2 - Configuration
### General description
Maybe you think: Configuration, what?? But there are severel possible ways to connect your Active Directory as you will see.

**Before you start over there are some terms you have to understand:**  
**role** = This term is used for a role in yii2. If you don't know what a role is look at http://www.yiiframework.com/doc-2.0/guide-security-authorization.html#rbac  
**group** = This term is used for a group in Active Directory.  
**user** = Means a user which exists in Active Directory.  
**username or login** = sAMAccountName attribute in Active Directory (the username you type at the windows login)  
**assigned group** = Means that a user is member of a group in Active Directory  
**assigned role** = Means that a user is member of a role in yii2  


**How it works in short words with the default settings**  
If you successfully finished task 1 imagine the login form which you reach over the Login Button in the right upper corner.


If you leave the default configuration, the following is happening on login (and I think it most suites):  
- On Login a LDAP query is issued to get the user from Active Directory, if it not exists in database the user is created.  
- On Login a LDAP query is issued to get the account status of the user, if the account status active the login is possible.  
- On Login the group to role assignment is refreshed with the following settings  
  - No role has to be assingned to the user for a successfull login  
  - For Active Directory groups starting with **yii2** and matching a existing role name in yii2, the role is assigned to the user automatically  
  - Only roles which are starting with **yii2** are added or removed from the user, other roles would not be touched  

For a working group to role assignment you have to create the roles in yii2! The roles would NOT be automatically created.

### Example for group configuration
In Step 7 of Task 1 you are have already done a successfully hopefully. But the problem is that every user in Active Directory with a valid password a active Account now can login in yii2. Thats not a good solution.

Before you continue read the the commets in source code starting at line 127.  
https://github.com/edvler/yii2-adldap-module/blob/master/src/model/UserDbLdap.php#L127

#### Login only possible when a role is assigned to the user
Now add the following to your config/params.php

    return [
        //...
        'LDAP-Group-Assignment-Options' => [
                'LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX' => "/^(yii2|app)(.*)/", // a role has to be assign, which is starting with yii2 or with app
                'REGEX_GROUP_MATCH_IN_LDAP' => "/^(yii2|app)(.*)/", // Active Directory groups beginning with yii2 or app ar filtered and if a yii2 role with the same name exists the role would be added to the user
                'ADD_GROUPS_FROM_LDAP_MATCHING_REGEX' => true, //add matches between groups and roles to the user
                'REMOVE_ALL_GROUPS_NOT_FOUND_IN_LDAP' => false,
                'REMOVE_ONLY_GROUPS_MATCHING_REGEX' => true, //Only remove groups matching regex REGEX_GROUP_MATCH_IN_LDAP
            ],
        //...
    ];
    
The configuration does the same as the default configuration with **one exception!**
 - The LOGIN_POSSIBLE_WITH_ROLE_ASSIGNED_MATCHING_REGEX is not null.
 Now only users with roles assigned beginning with **yii2 OR app** can login!
 
If you try to login again (please logout before) it will not work!  
Why?  
The answer is simple for two reasons:
- You don't have a group in Active Directory which name is starting with yii2 and thus the user is not a member of such a group 
- yii2 has no corresponding role

#### Create example role
Look into the source code of the function actionCreateExampleRole (see file @vendor/edvlerblog/yii2-adldap-module/src/commands/LdapController.php).

As you can see two permissions are created **(permissionDisplayDetailedAbout, permissionToUseContanctPage)** and assigend to the role 
**yii2_example_group**.

Open a shell or a cmd and change to the base directory of your yii2 installation (where the composer.json is located).  
Type in your shell:  
    
    cd C:\xampp\htdocs\basic
    yii ldapcmd/create-example-role


    !!!! TODO !!!!
    A role with the name yii2_example_group was created in yii2.
    Please create a group with the same name in Active Directory.
    Assign the user you are using for the login to this group in Active Directory.
    
#### Create Active Directory Group 
Now go to your Active Directory Management Console and create a group with the same name as the role (**yii2_example_group**).
Make the user you are using for the login to a member of that group.


#### Test the Login
Now you can go to the login in page of your yii installation (see upper right corner of the website). Use the user which you are made to a member of the group **yii2_example_group** in Active Directory.
If everythings okay you should see the username in upper right corner.
