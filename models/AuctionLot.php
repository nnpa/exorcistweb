<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class AuctionLot extends ActiveRecord
{
    public static function tableName()
    {
        return 'auction_lots';
    }

    public function rules()
    {
        return [
            [['seller_id', 'price', 'start_time', 'end_time'], 'required'],
            [['seller_id', 'price', 'status'], 'integer'],
            [['start_time', 'end_time'], 'safe'],
            ['status', 'default', 'value' => 0],
        ];
    }

    public function getSeller()
    {
        return $this->hasOne(User::class, ['id' => 'seller_id']);
    }

    public function getItems()
    {
        return $this->hasMany(Item::class, ['id' => 'item_id'])->via('lotItems');
    }

    public function getLotItems()
    {
        return $this->hasMany(AuctionLotItem::class, ['lot_id' => 'id']);
    }

    public function getItemData()
    {
        return $this->getItems()->all();
    }

    // Формирование ответа для клиента
    public function toApiResponse()
    {
        $itemsArray = [];
        foreach ($this->getItems()->all() as $item) {
            $itemsArray[] = $item->toApiArray();
        }
        return [
            'id' => $this->id,
            'sellerName' => $this->seller ? $this->seller->login : 'Unknown',
            'price' => $this->price,
            'endTime' => strtotime($this->end_time) * 1000, // в миллисекундах для Java
            'status' => $this->status,
            'items' => $itemsArray,
        ];
    }
}