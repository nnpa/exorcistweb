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
            'killCounter' => $this->kill_counter,

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
    
    
  private static $setPrefixWords = [
    'damage'  => ['sharp', 'остро'],
    'health'  => ['heavy', 'тяжело'],
    'defense' => ['sturdy', 'крепко'],
    'mana'    => ['magic', 'магично'],
];

/**
 * Определяет "сет" предмета по первому слову его названия.
 * Название генерируется как "Префикс Суффикс", поэтому
 * достаточно проверить начало строки.
 */
private function detectItemSet($itemName)
{
    if (!$itemName) {
        return null;
    }

    $lower = mb_strtolower(trim($itemName));

    foreach (self::$setPrefixWords as $setKey => $words) {
        foreach ($words as $word) {
            $word = mb_strtolower($word);
            if (mb_strpos($lower, $word) === 0) {
                return $setKey;
            }
        }
    }

    return null;
}

public function recalcStats()
{
    $baseHealth = 100 + ($this->level - 1) * 10;
    $baseMana   = 50 + ($this->level - 1) * 5;
    $baseDamage = 5 + ($this->level - 1) * 2;
    $baseDefense = 0;

    $healthBonus = 0;
    $manaBonus   = 0;
    $damageBonus = 0;
    $defenseBonus = 0;

    // Счётчики предметов по сетам (по префиксу названия)
    $setCounts = [
        'damage'  => 0,
        'health'  => 0,
        'defense' => 0,
        'mana'    => 0,
    ];

    // Используем правильное имя отношения: inventoryItems
    foreach ($this->inventoryItems as $inv) {
        if ($inv->equipped && $inv->item) {
            $item = $inv->item;
            $healthBonus += $item->health_bonus;
            $manaBonus   += $item->mana_bonus;
            $damageBonus += $item->damage;
            $defenseBonus += $item->defense;

            // ================================================
            // БОНУСЫ ОТ ВСТАВЛЕННЫХ КАМНЕЙ (сокеты)
            // ================================================
            $sockets = \app\models\ItemSocket::find()
                ->where(['item_id' => $item->id])
                ->all();

            foreach ($sockets as $socket) {

                if (!$socket->gem_item_id) {
                    continue;
                }

                $gem = $socket->gem;

                if (!$gem) {
                    continue;
                }

                switch ($gem->name) {

                    case 'Ruby':
                        $damageBonus += 10;
                        break;

                    case 'Emerald':
                        $defenseBonus += 10;
                        break;

                    case 'Diamond':
                        $healthBonus += 100;
                        break;
                }
            }

            // ================================================
            // ОПРЕДЕЛЯЕМ СЕТ ПО ПРЕФИКСУ НАЗВАНИЯ
            // ================================================
            $setKey = $this->detectItemSet($item->name);

            if ($setKey !== null && isset($setCounts[$setKey])) {
                $setCounts[$setKey]++;
            }
        }
    }

    $this->max_health = $baseHealth + $healthBonus;
    $this->max_mana   = $baseMana   + $manaBonus;
    $this->damage     = $baseDamage + $damageBonus;
    $this->defense    = $baseDefense + $defenseBonus;

    // ================================================
    // ПРОЦЕНТНЫЕ БОНУСЫ ЗА СЕТЫ (от 2 предметов, макс. 7%)
    // ================================================
    foreach ($setCounts as $setKey => $count) {

        if ($count < 2) {
            continue;
        }

        $percent = min($count, 7);

        switch ($setKey) {

            case 'health':
                $this->max_health = (int) round($this->max_health * (1 + $percent / 100));
                break;

            case 'mana':
                $this->max_mana = (int) round($this->max_mana * (1 + $percent / 100));
                break;

            case 'damage':
                $this->damage = (int) round($this->damage * (1 + $percent / 100));
                break;

            case 'defense':
                $this->defense = (int) round($this->defense * (1 + $percent / 100));
                break;
        }
    }

    $this->health = min($this->health, $this->max_health);
    $this->mana   = min($this->mana,   $this->max_mana);

    return $this->save();
}
}