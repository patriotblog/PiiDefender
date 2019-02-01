<?php
namespace PiiDefender;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 1/14/19
 * Time: 3:35 PM
 */
class PiiDefender
{
    const  STATUS_ACTIVE = 1;
    /**
     * User Host IP Address.
     * @var string
     */
    public $ip;

    /**
     * The total number of failed logins in the test period. READ ONLY.
     * @var int
     */
    public $failedLogins;

    /**
     * The total number of times an Ip has been locked. READ ONLY.
     * @var int
     */
    public $timesLocked;

    /**
     * Allowed failed logins during the 'failedLoginsTestDuration' period.
     * @var int Failed login attempts.
     */
    public $failedLoginsAllowed = 20; // 20 failed logins in $failedLoginsTestDuration seconds

    /**
     * Max number of failed logins allowed before showing a Captcha test.
     * @var integer
     */
    public $maxLoginsBeforeCaptcha = 3;

    /**
     * Test period for login errors count, from now backwards.
     * @var int Time in seconds.
     */
    public $failedLoginsTestDuration = 300; // 5 minutes

    /**
     * Duration of the lock.
     * @var int Time in seconds.
     */
    public $lockDuration = 1800; // 30 minutes

    /**
     * Number of locks tolerated before definitive ban of the ip.
     * @var int Number of locks: 0 to disable ban.
     */
    public $locksBeforeBan = 2;

    public $isBanned;

    private $initializer;

    /**
     * The result of a query on the ip_locks table. List of lock records for the current ip.
     * @var LockedIps[]
     */
    private $locks;

    /**
     * Whether the ip is locked or banned. READ ONLY.
     * @var int
     */
    public $isLocked;

    function __construct()
    {
        $this->initializer = new PiiDefenderInitializer();
        $this->initializer->check();

        $this->ip = \Yii::app()->request->getUserHostAddress();

        // Get lock record for the current ip.
        $this->locks = $this->getLocks();


        // Stores data about the current ip.
        $this->timesLocked = count($this->locks);
        $this->isLocked = (boolean) $this->isLocked();
        $this->isBanned = (boolean) $this->isBanned();



        // Store the number of failed logins for the currenti ip.
        if (!$this->isLocked && !$this->isBanned){
            $this->failedLogins = $this->countLoginErrors();
        }

        else{
            // If locked or banned, the number of failed login is always 1000 more than allowed.
            $this->failedLogins = $this->failedLoginsAllowed + 1000;
        }
    }



    /**
     * Check the session to get login failures: returns false if the user has
     * too many failures in a small time span.
     * @param array $session User's session.
     * @param int $ip User's ip.
     * @return boolean Wheter the user has the right to continue logging in.
     */
    public function isSafeIp(){

        if ($this->isLocked || $this->isBanned){
            return false;
        }

        return true;
    }


    /**
     * Lock ip. This ip will be locked for the time defined in $this->lockDuration
     * @param string $ip
     */
    public function lockIp(){

        if (!$this->isLocked){

            \Yii::log('Now locked: ' . $this->ip, 'warning');

            $model = new LockedIps();
            $model->ip = $this->ip;
            $model->created_time = time();

            return ($model->save());

        }
        \Yii::log('Already locked: ' . $this->ip, 'warning');

    }


    /**
     * Check wether the ip is currently locked.
     * @return boolean
     */
    public function isLocked(){

        foreach ($this->locks as $lock){

            if ($lock->created_time > time() - $this->lockDuration){

                \Yii::log('Ip locked? YES', 'warning');

                \Yii::log('Time locked? ' . $lock->created_time, 'warning');

                return true;

            }

        }

        \Yii::log('Ip locked? NO', 'warning');

        return false;

    }


    /**
     * Check wether the ip is banned.
     * @return int
     */

    public function isBanned(){

        if ($this->timesLocked >= $this->locksBeforeBan){

            \Yii::log('Ip banned? YES', 'warning');

            return true;

        } else{

            \Yii::log('Ip banned? NO', 'warning');

            return false;

        }

    }


    /**
     * Get lock records for the current ip.
     * @return array Array with ip
     */
    public function getLocks(){

        $locks = LockedIps::model()->findAllByAttributes([
            'ip'=>$this->ip
        ]);

        return $locks;

    }


    /**
     * Get the number of failed logins for the current ip.
     * @return int
     */
    public function countLoginErrors(){

        $criteria = new \CDbCriteria();
        $criteria->addColumnCondition([
            'ip'=>$this->ip,
        ]);
        $criteria->addCondition('created_time>:p1');
        $criteria->params[':p1'] = time() - $this->failedLoginsTestDuration;
        $errors = FailedAttempts::model()->count($criteria);


        return $errors;

    }


    /**
     * Save ip and time of a failed login attempt in the db and delete old records.
     * @param array $session User's session.
     * @param int $ip User's ip.
     */
    public function recordFailedAttempts(){

        $model = new FailedAttempts();
        $model->ip = $this->ip;
        $model->created_time = time();

        if($model->save()){

        }else{
            var_dump($model->errors);
        }


        #var_dump($this->failedLogins);

        if ($this->failedLogins + 1 > $this->failedLoginsAllowed) {
            $this->lockIp();
        }


        \Yii::log('Rows inserted: ' . json_encode($model->errors), 'warning');

    }

    public function removeOld(){

	    $criteria = new \CDbCriteria();
	    $criteria->addCondition('created_time<:p1');
	    $criteria->params[':p1'] = (time() - $this->failedLoginsTestDuration);

	    $deleted = FailedAttempts::model()->deleteAll($criteria);

	    \Yii::log('Rows deleted: ' . $deleted, 'warning');
    }




    /**
     * Remove every failed login record about the current ip
     * @return integer number of rows deleted
     */
    public function removeFailedAttempts(){

        return FailedAttempts::model()->deleteAllByAttributes([
            'ip'=>$this->ip,
        ]);
    }

}