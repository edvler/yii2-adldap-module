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

use yii\db\Migration;
class m161220_101944_create_user_table extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull()->unique(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);
    }
    public function down()
    {
        $this->dropTable('{{%user}}');
    }
}
