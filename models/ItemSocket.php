<?php
namespace app\models;

use yii\db\ActiveRecord;

class ItemSocket extends ActiveRecord
{
    public static function tableName()
    {
        return 'item_sockets';
    }

    public function rules()
    {
        return [
            [['item_id', 'socket_index'], 'required'],
            [['socket_index'], 'integer'],
            [['item_id', 'gem_item_id'], 'string', 'max' => 36],
            [['created_at'], 'safe'],
        ];
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getGem()
    {
        return $this->hasOne(Item::class, ['id' => 'gem_item_id']);
    }
}