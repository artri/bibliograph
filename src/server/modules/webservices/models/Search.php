<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\modules\webservices\models;

use app\models\User;
use lib\models\BaseModel;

/**
 * Class Search
 * @package app\modules\webservices\models
 * @property int $id
 * @property string $created
 * @property string $modified
 * @property int $hits
 * @property string $query
 * @property string $datasource
 * @property int $UserId
 */
class Search extends BaseModel
{

  static function tableName()
  {
    return '{{%data_Search}}';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['hits', 'UserId'], 'integer'],
      [['query'], 'string', 'max' => 500],
      [['datasource'], 'string', 'max' => 50],
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * Public to avoid magic property access
   * @return \yii\db\ActiveQuery
   */
  public function getUser()
  {
    return $this->hasOne(User::class, [ 'id' => 'UserId' ] );
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  public function getRecords()
  {
    return $this->hasMany(Record::class, ['SearchId' => 'id'] );
  }

  //-------------------------------------------------------------
  // Overrridden methods
  //-------------------------------------------------------------

  /**
   * @return bool
   */
  public function beforeDelete()
  {
    if( parent::beforeDelete() ){
      Record::setDatasource(static::getDatasource());
      Record::deleteAll(['SearchId'=> $this->id]);
      return true;
    }
    return false;
  }
}
