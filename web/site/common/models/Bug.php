<?php

namespace common\models;

use Yii;
use common\models\User;
use common\models\BugAction;
use yii\db\ActiveRecord;
use common\components\MyCustomActiveRecord;

/**
 * This is the model class for table "bug".
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $bug_status
 * @property string $priority_level
 * @property int|null $developer_user_id
 * @property string|null $notes
 * @property string $delete_status
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $updated_at
 * @property int|null $updated_by
 */
class Bug extends MyCustomActiveRecord
{
    const BUG_STATUS_NEW = "new";
    const BUG_STATUS_ASSIGNED = "assigned";
    const BUG_STATUS_FIXING = "fixing";
    const BUG_STATUS_PENDING_REVIEW = "pending_review";
    const BUG_STATUS_COMPLETED = "completed";
    const BUG_STATUS_REJECTED ="rejected";
    const BUG_STATUS_REOPEN = "reopen";

    const PRIORITY_LOW = '1';
    const PRIORITY_MED = '2';
    const PRIORITY_HIGH = '3';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bug';
    }
    public function init(){
        $this->on(ActiveRecord::EVENT_AFTER_INSERT, [$this, 'saveAction']);
        $this->on(ActiveRecord::EVENT_AFTER_UPDATE, [$this, 'saveAction']);
        parent::init();
    }

    public function saveAction($event){
        $latestAction = BugAction::find()->andWhere(['bug_id'=>$this->id])->active()->orderBy(['created_at'=>SORT_DESC])->one();
        if(is_null($latestAction) || ($latestAction && $latestAction->action_type != $this->bug_status)){
          $m = BugAction::makeModel($this->id, $this->bug_status);
          return $m->save(false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'description', 'bug_status', 'priority_level'], 'required'],
            [['description', 'bug_status', 'priority_level', 'delete_status'], 'string'],
            [['developer_user_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            ['title', 'string', 'max' => 128],
            ['bug_status', 'in', 'range' => [ 'new', 'assigned', 'fixing', 'pending_review',
                                              'completed', 'rejected', 'reopen' ]],
            ['priority_level', 'in', 'range' => [ '1', '2', '3' ]],
            ['developer_user_id', 'validateDevUID'],
            ['notes', 'string', 'max' => 1028],
            ['delete_status', 'in', 'range' => [ 'enabled', 'disabled' ]],
            ['created_by', 'exist', 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            ['updated_by', 'exist', 'targetClass' => User::class, 'targetAttribute' => ['updated_by' => 'id']],
        ];
    }

    public function validateDevUID($attribute, $params, $validator)
    {
        $query = new \yii\db\Query();
        $query->select('user_id')
              ->from('rbac_auth_assignment')
              ->where([
                  'user_id' => $this->$attribute,
                  'item_name' => User::ROLE_DEVELOPER,
              ]);

        if (!$query->exists()) {
            $this->addError($attribute, 'dev uid must refer to existing developer');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
            'bug_status' => 'Bug Status',
            'priority_level' => 'Priority Level',
            'developer_user_id' => 'Developer',
            'notes' => 'Notes',
            'delete_status' => 'Delete Status',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    public function getBugStatusBadgeColor(){
      $badge_type = "badge-light";
      switch ($this->bug_status) {
        case Bug::BUG_STATUS_FIXING:
          $badge_type = "badge-warning";
          break;
        case Bug::BUG_STATUS_COMPLETED:
          $badge_type = "badge-success";
          break;
        case Bug::BUG_STATUS_ASSIGNED:
          $badge_type = "badge-info";
          break;
        default:
          $badge_type = "badge-light";
          break;
      }
      return $badge_type;
    }

    public function getPriorityLevelBadgeColor(){
      $badge_type = "badge-light";
      switch ($this->priority_level) {
        case SELF::PRIORITY_LOW:
          $badge_type = "badge-info";
          break;
        case SELF::PRIORITY_MED:
          $badge_type = "badge-warning";
          break;
        case SELF::PRIORITY_HIGH:
          $badge_type = "badge-danger";
          break;
        default:
          $badge_type = "badge-light";
          break;
      }
      return $badge_type;
    }

    public function toObject() {
        $m = $this;

        $o = (object) [];
        $o->id = $m->id;
        $o->title = $m->title;
        $o->description = $m->description;
        $o->bug_status = $m->bug_status;
        $o->priority_level = $m->priority_level;
        $o->developer = empty($m->developer_user_id) ? "" : User::findIdentity($m->developer_user_id)->publicIdentity;
        $o->notes = $m->notes;
        $o->delete_status = $m->delete_status;
        $o->created_at = Yii::$app->formatter->asDateTime($m->created_at);
        $o->created_by = empty($m->updated_by) ? null : User::findIdentity($m->created_by)->publicIdentity;
        $o->updated_at = Yii::$app->formatter->asDateTime($m->updated_at);
        $o->updated_by = empty($m->updated_by) ? null : User::findIdentity($m->updated_by)->publicIdentity;
        $o->bug_status_badge = $m->bugStatusBadgeColor;
        $o->priority_level_badge = $m->priorityLevelBadgeColor;
        return $o;
    }

    public function getDocuments()
    {
        return $this->hasMany(BugDocument::className(), [ 'bug_id' => 'id' ]);
    }

    public function getTags()
    {
        return $this->hasMany(BugTag::className(), [ 'bug_id' => 'id'])
                    ->andWhere(['delete_status'=>MyCustomActiveRecord::DELETE_STATUS_ENABLED ]);
    }

    public function getDeveloperUser()
    {
        return $this->hasOne(User::className(), [ 'id' => 'developer_user_id' ]);
    }

    public function getDocumentPreviews()
    {
        return [
            'data' => $this->getDocumentPreviewData(),
            'config' => $this->getDocumentPreviewConfig(),
        ];
    }

    private function getDocumentPreviewData()
    {
        return array_map(function($doc) {
            return $doc->getPreviewData();
        }, $this->documents);
    }

    private function getDocumentPreviewConfig()
    {
        return array_map(function($doc) {
            return $doc->getPreviewConfig();
        }, $this->documents);
    }

    /**
     * {@inheritdoc}
     * @return \common\models\query\BugQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \common\models\query\BugQuery(get_called_class());
    }

    public static function makeModel($title, $description)
    {
        $bug = new Bug();
        $bug->title = $title;
        $bug->description = $description;
        $bug->bug_status = SELF::BUG_STATUS_NEW;
        $bug->priority_level = SELF::PRIORITY_LOW;
        return $bug;
    }

    public static function getAllPriorityLevel() {
      return [
          SELF::PRIORITY_LOW => "Low",
          SELF::PRIORITY_MED => "Med",
          SELF::PRIORITY_HIGH => "High",
      ];
    }

    public static function getAllBugStatus() {
      return [
          SELF::BUG_STATUS_NEW => "New",
          SELF::BUG_STATUS_ASSIGNED => "Assigned",
          SELF::BUG_STATUS_FIXING => "Fixing",
          SELF::BUG_STATUS_PENDING_REVIEW => "Pending Review",
          SELF::BUG_STATUS_COMPLETED => "Completed",
          SELF::BUG_STATUS_REJECTED => "Rejected",
          SELF::BUG_STATUS_REOPEN => "Re-Open",
      ];
    }

    public static function getActiveBugsData() {
        return SELF::find()
          ->where(['not in', 'bug_status', [SELF::BUG_STATUS_PENDING_REVIEW, SELF::BUG_STATUS_REJECTED, SELF::BUG_STATUS_COMPLETED]])
          ->all();
    }

    public static function getResolvedBugsData() {
        return SELF::find()
          ->where(['bug_status'=>SELF::BUG_STATUS_COMPLETED])
          ->all();
    }

    public static function getPendingBugsData() {
        return SELF::find()
          ->where(['bug_status'=>SELF::BUG_STATUS_PENDING_REVIEW])
          ->all();
    }

    public static function getTopDeveloperData() {
        $date = new \DateTime('now');
        $st = $date->modify('first day of this month')->format('m-Y');

        return BugAction::find()
            ->select(['COUNT(*) AS counter', 'developer_user_id'])
            ->leftJoin('bug', '`bug`.`id` = `bug_action`.`bug_id`')
            ->where(['action_type'=>SELF::BUG_STATUS_COMPLETED])
            ->andWhere(['FROM_UNIXTIME(`bug_action`.`created_at`, "%m-%Y")' => $st])
            ->groupBy('developer_user_id')
            ->orderBy(['counter' => SORT_DESC, 'developer_user_id' => SORT_ASC])
            ->asArray()
            ->limit(3)
            ->all();
    }

    public static function getTopReporterData($st, $et){
        return SELF::find()
          ->select(['COUNT(*) AS counter', 'created_by'])
          ->where(['!=', 'bug_status', SELF::BUG_STATUS_REJECTED])
          ->andWhere(['>=', 'created_at', $st])
          ->andWhere(['<=', 'created_at', $et])
          ->groupBy('created_by')
          ->orderBy(['counter'=>SORT_DESC])
          ->asArray()
          ->limit(3)
          ->all();
    }

    public static function getBugStatusData(){
        return SELF::find()
          ->select(['COUNT(*) AS counter', 'bug_status'])
          ->groupBy('bug_status')
          ->asArray()
          ->all();
    }

    public static function getCurBugStatusData(){
        return SELF::find()
          ->select(['bug_status', 'created_at', 'COUNT(id) AS counter'])
          ->where(['FROM_UNIXTIME(created_at, "%m-%Y")' => date('m-Y')])
          ->groupBy('bug_status')
          ->asArray()
          ->all();
    }

    public static function getPriorityLevelData(){
        return SELF::find()
          ->select(['COUNT(*) AS counter', 'priority_level'])
          ->where(['not in', 'bug_status', [SELF::BUG_STATUS_REJECTED, SELF::BUG_STATUS_COMPLETED]])
          ->groupBy('priority_level')
          ->asArray()
          ->all();
    }

    public static function getReportedBugsByMonth(){
        return SELF::find()
          ->select(['FROM_UNIXTIME(created_at, "%m-%Y") AS m_date', 'COUNT(id) AS counter'])
          ->where(['not in', 'bug_status', [SELF::BUG_STATUS_REJECTED, SELF::BUG_STATUS_COMPLETED]])
          ->groupBy('m_date')
          ->asArray()
          ->all();
    }

    public static function getResolvedBugsByMonth(){
        return SELF::find()
          ->select(['FROM_UNIXTIME(created_at, "%m-%Y") AS m_date', 'COUNT(id) AS counter'])
          ->where(['bug_status'=>SELF::BUG_STATUS_COMPLETED])
          ->groupBy('m_date')
          ->asArray()
          ->all();
    }

    public static function getReportedBugs($start_at, $end_at){
        return SELF::find()
          ->select(['created_at', 'COUNT(id) AS counter'])
          ->where(['>=', 'created_at', $start_at])
          ->andWhere(['<=', 'created_at', $end_at])
          ->count();
    }

    public static function getResolvedBugs($start_at, $end_at){
        return SELF::find()
          ->select(['updated_at', 'COUNT(id) AS counter'])
          ->where(['>=', 'updated_at', $start_at])
          ->andWhere(['<=', 'updated_at', $end_at])
          ->andWhere(['bug_status'=>SELF::BUG_STATUS_COMPLETED])
          ->count();
    }
}
