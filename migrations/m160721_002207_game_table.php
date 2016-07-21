<?php

use yii\db\Migration;

class m160721_002207_game_table extends Migration
{
    public function up()
    {
        $this->createTable('test', [
            'id' => $this->primaryKey(),
            'test' => $this->string()
        ]);

        $this->insert('test', ['test' => 'test one']);
        $this->insert('test', ['test' => 'test two']);
    }

    public function down()
    {
        echo "m160721_002207_game_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
