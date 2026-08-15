<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Progress extends ActiveRecord
{
    public static function tableName() { return 'progress'; }

    public function rules()
    {
        return [
            [['character_id', 'dungeon_id'], 'required'],
            ['completed', 'boolean'],
            ['best_difficulty', 'integer', 'min' => 0],
        ];
    }

    public function getCharacter()
    {
        return $this->hasOne(Character::class, ['id' => 'character_id']);
    }
}