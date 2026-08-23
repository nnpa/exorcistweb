<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Inventory extends ActiveRecord
{
    public static function tableName() { return 'inventory'; }

public function rules()
{
    return [
        [['character_id', 'item_id'], 'required'],          // slot_index убираем из required
        ['slot_index', 'integer'],                          // разрешаем null (пропускается при skipOnEmpty)
        ['equipped', 'boolean'],
        ['equipped_slot', 'in', 'range' => ['helmet','chest','weapon','shield','legs','boots','gloves']],
    ];
}

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getCharacter()
    {
        return $this->hasOne(Character::class, ['id' => 'character_id']);
    }
}