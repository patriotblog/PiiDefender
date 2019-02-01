<?php
namespace PiiDefender;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 1/14/19
 * Time: 3:37 PM
 */
class PiiDefenderInitializer extends CDbMigration
{
    public function check(){
        if(!$this->dbExist()){
            $this->makeDB();
        }
    }
    private function dbExist(){
        $check = Yii::app()->db->schema->getTable('failed_attempts');
        if($check){
            return true;
        }else{
            return false;
        }
    }
    private function makeDB(){

        $this->createTable('failed_attempts',[
            'id'=>'pk',
            'ip'=>'varchar(45) not null',
            'created_time' => 'int(11) NOT NULL',
        ]);
        $this->createTable('locked_ips', [
            'id'=>'pk',
            'ip'=>'varchar(45) not null',
            'created_time' => 'int(11) NOT NULL',
            'status'=>'int(11) default '.PiiDefender::STATUS_ACTIVE
        ]);
    }
}