# yii2-adldap-module User model

The User model adds the possibility to authenticate users against Active Directory in yii2 fashion.
It's also possible to match assigned groups to a user in Active Directory to a role in yii2.

You can manage your users **completly** over Active Directory without doing anything in yii2!!
The only thing you have to is assign groups in Active Directory starting with yii2 to the user.
The rest would be **magic**!

But more details later.


## Task 1 - Basic installation
### 1. Configure yii2-adldap-module as described in the main README.md. This means your LDAP servers, base_dn and so on are set in the web.conf (basic template) / main.conf (advanced template).

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

### 7. Test the Login of your basic app.  
If everythings okay you should see the username in upper right corner. At this point you can use any user in your Active Directory to login into the web application.

  You can do select * from users to check if a user was inserted on login.


## Task 2 - Configuration
Maybe you think: Configuration, what?? But there are severel possible ways to connect your Active Directory as you will see.

**Before you start over there are some terms you have to understand:**  
**role** = This term is used for a role in yii2. If you don't know what a role is look at http://www.yiiframework.com/doc-2.0/guide-security-authorization.html#rbac  
**group** = This term is used for a group in Active Directory.  
**user** = Means a user which exists in Active Directory.  
**username or login** = sAMAccountName attribute in Active Directory (the username you type at the windows login)  
**assigned group** = Means that a user is member of a group in Active Directory  


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

  
  
