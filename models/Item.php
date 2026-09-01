<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Item extends ActiveRecord
{
    public static function tableName() { return 'items'; }

    public function rules()
    {
        return [
            [['difficulty'], 'integer', 'min' => 1],

            [['id', 'name', 'type', 'level', 'rarity'], 'required'],
            ['id', 'string', 'max' => 36],
            ['name', 'string', 'max' => 128],
            ['type', 'in', 'range' => ['Weapon','Helmet','Chest','Shield','Legs','Boots','Gloves',"Gem"]],
            ['rarity', 'in', 'range' => ['COMMON','UNCOMMON','RARE','EPIC','LEGENDARY']],
            [['damage','defense','health_bonus','mana_bonus','socket_count'], 'integer', 'min' => 0],
            [['icon_path', 'description'], 'string'],
        ];
    }

    public function toApiArray()
    {
        return [
            'difficulty' => $this->difficulty,

            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'level' => $this->level,
            'rarity' => $this->rarity,
            'description' => $this->description,
            'damage' => (int)$this->damage,
            'defense' => (int)$this->defense,
            'healthBonus' => (int)$this->health_bonus,
            'manaBonus' => (int)$this->mana_bonus,
            'iconPath' => $this->icon_path,
            'socketCount' => (int)$this->socket_count,
        ];
    }
}