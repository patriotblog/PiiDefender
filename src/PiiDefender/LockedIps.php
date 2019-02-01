<?php
namespace PiiDefender;
/**
 * This is the model class for table "locked_ips".
 *
 * The followings are the available columns in table 'locked_ips':
 * @property integer $id
 * @property string $ip
 * @property integer $created_time
 * @property integer $status
 */
class LockedIps extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'locked_ips';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('ip, created_time', 'required'),
			array('created_time, status', 'numerical', 'integerOnly'=>true),
			array('ip', 'length', 'max'=>45),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, ip, created_time, status', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'ip' => 'Ip',
			'created_time' => 'Created Time',
			'status' => 'Status',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return \CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new \CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('ip',$this->ip,true);
		$criteria->compare('created_time',$this->created_time);
		$criteria->compare('status',$this->status);

		return new \CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return LockedIps the static model class
	 */
	public static function model($className=__CLASS__)
	{
	    /** @var LockedIps $model */
		$model =  parent::model($className);
		return $model;
	}


    public function beforeSave()
    {
        if(parent::beforeSave()){

            if(empty($this->created_time)){
                $this->created_time = time();
            }

            if(empty($this->status)){
                $this->status = PiiDefender::STATUS_ACTIVE;
            }

            return true;
        }
        return false;
    }

}
