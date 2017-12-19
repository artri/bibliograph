<?php

use yii\db\Migration;

class m171219_230853_create_table_data_DatasourceSchema extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%data_DatasourceSchema}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'namedId' => $this->string(50),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'class' => $this->string(100),
            'description' => $this->string(255),
            'active' => $this->smallInteger(1)->notNull()->defaultValue('1'),
        ], $tableOptions);

        $this->createIndex('unique_namedId', '{{%data_DatasourceSchema}}', 'namedId', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%data_DatasourceSchema}}');
    }
}