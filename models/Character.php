<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\models;

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Character extends ActiveRecord
{
    public static function tableName() { return 'characters'; }

    public function rules()
    {
        return [
            [['health_potions', 'mana_potions'], 'integer'],

            [['user_id', 'name'], 'required'],
            ['name', 'string', 'max' => 64],
            ['level', 'default', 'value' => 1],
            ['health', 'default', 'value' => 100],
            ['max_health', 'default', 'value' => 100],
            ['mana', 'default', 'value' => 50],
            ['max_mana', 'default', 'value' => 50],
            ['gold', 'default', 'value' => 100],
            ['difficulty', 'default', 'value' => 1],
            ['current_dungeon', 'default', 'value' => 'dungeon_1'],
            [['health', 'max_health', 'mana', 'max_mana', 'gold', 'level', 'difficulty', 'experience'], 'integer', 'min' => 0],
            [['last_dungeon_position_x', 'last_dungeon_position_y', 'last_dungeon_position_z'], 'number'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getInventoryItems()
    {
        return $this->hasMany(Inventory::class, ['character_id' => 'id']);
    }

    public function getItems()
    {
        return $this->hasMany(Item::class, ['id' => 'item_id'])->via('inventoryItems');
    }

    public function getEquippedItems()
    {
        return $this->hasMany(Inventory::class, ['character_id' => 'id'])
                    ->where(['equipped' => 1]);
    }

    public function getProgress()
    {
        return $this->hasMany(Progress::class, ['character_id' => 'id']);
    }
public function getTalents()
{
    return $this->hasMany(CharacterTalent::class, ['character_id' => 'id']);
}
    // Возвращает полные данные для клиента
    public function toApiResponse()
    {
        $inventory = [];
        foreach ($this->inventoryItems as $inv) {
            $item = $inv->item;
            $inventory[] = [
                'slot' => $inv->slot_index,
                'equipped' => (bool)$inv->equipped,
                'equipped_slot' => $inv->equipped_slot,
                'item' => $item ? $item->toApiArray() : null,
            ];
        }
        return [
            'healthPotions' => $this->health_potions,
        'manaPotions' => $this->mana_potions,
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'experience' => $this->experience,
            'health' => $this->health,
            'maxHealth' => $this->max_health,
            'mana' => $this->mana,
            'maxMana' => $this->max_mana,
            'gold' => $this->gold,
            'currentDungeon' => $this->current_dungeon,
            'difficulty' => $this->difficulty,
            'lastDungeonPosition' => [
                'x' => $this->last_dungeon_position_x,
                'y' => $this->last_dungeon_position_y,
                'z' => $this->last_dungeon_position_z,
            ],
            'inventory' => $inventory,
        ];
    }
    
}