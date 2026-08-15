<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Auction extends ActiveRecord
{
    public static function tableName() { return 'auction'; }

    public function rules()
    {
        return [
            [['seller_id', 'item_id', 'price'], 'required'],
            ['price', 'integer', 'min' => 0],
            ['sold', 'boolean'],
            ['buyer_id', 'integer'],
        ];
    }

    public function getSeller()
    {
        return $this->hasOne(Character::class, ['id' => 'seller_id']);
    }

    public function getBuyer()
    {
        return $this->hasOne(Character::class, ['id' => 'buyer_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    // Для списка аукциона
    public function toApiArray()
    {
        $item = $this->item;
        return [
            'id' => $this->id,
            'price' => (int)$this->price,
            'sellerName' => $this->seller ? $this->seller->name : 'unknown',
            'item' => $item ? $item->toApiArray() : null,
            'endTime' => $this->end_time,
        ];
    }
}