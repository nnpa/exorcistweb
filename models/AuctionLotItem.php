<?php
namespace app\models;

use yii\db\ActiveRecord;

class AuctionLotItem extends ActiveRecord
{
    public static function tableName()
    {
        return 'auction_lot_items';
    }

    public function rules()
    {
        return [
            [['lot_id', 'item_id'], 'required'],
            [['lot_id'], 'integer'],
            [['item_id'], 'string', 'max' => 36],
        ];
    }
}