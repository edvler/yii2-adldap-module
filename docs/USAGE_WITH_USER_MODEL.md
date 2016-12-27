# yii2-adldap-module User model

The User model adds the possibility to authenticate users against Active Directory in yii2 fashion.
Depending on your needs the UserDbLdap fits perfect for you, needs to be extented or, if you are using another user model in your application it cannot be used.

For a better overview I will give a short overview of how things work togehter and what are needed for a successfull installation.
This is only a short overwiew.

0. I assume you configured yii2-adldap-module as described in the main README.md. This means your servers are set and so on.
1. Apply yii2 RBAC Migrations
2. Apply UserDbLdap Migrations
3. Add configuration for the behavior of the UserDbLdap class to the params.php
