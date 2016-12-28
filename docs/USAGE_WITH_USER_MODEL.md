# yii2-adldap-module User model

The User model adds the possibility to authenticate users against Active Directory in yii2 fashion.
Depending on your needs the UserDbLdap fits perfect for you, needs to be extented or, if you are using another user model in your application it cannot be used.

For a better overview I will give a short overview what are needed for a successfull installation.


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

