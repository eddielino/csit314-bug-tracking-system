<?php

namespace common\models\query;

/**
 * This is the ActiveQuery class for [[\common\models\BugAction]].
 *
 * @see \common\models\BugAction
 */
class BugActionQuery extends \common\components\MyCustomActiveRecordQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \common\models\BugAction[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \common\models\BugAction|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
