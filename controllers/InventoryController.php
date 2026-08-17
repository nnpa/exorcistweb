<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use app\models\Character;
use app\models\Inventory;
use app\models\Item;

class InventoryController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'pickup' => ['post'],
                'equip' => ['post'],
                'unequip' => ['post'],
                'drop' => ['post'],
            ],
        ];
        return $behaviors;
    }

    private function getCharacter()
    {
        $user = Yii::$app->user->identity;
        $char = Character::find()->where(['user_id' => $user->id])->one();
        if (!$char) throw new BadRequestHttpException('Character not found');
        return $char;
    }

    // ===== ПОДНЯТИЕ ПРЕДМЕТА (с созданием записи в БД) =====
    public function actionPickup()
    {
        $request = Yii::$app->request;
        $itemData = $request->post('itemData'); // ожидаем JSON с данными предмета
        if (!$itemData) {
            throw new BadRequestHttpException('Missing itemData');
        }

        // Если передан только itemId (старая логика) – попробуем найти предмет
        if (is_string($itemData)) {
            $item = Item::findOne($itemData);
            if (!$item) {
                throw new BadRequestHttpException('Item not found');
            }
        } else {
            // Создаём предмет из данных
            $item = new Item();
            $item->id = $itemData['id'] ?? null;
            $item->name = $itemData['name'] ?? 'Unknown';
            $item->type = $itemData['type'] ?? 'Weapon';
            $item->level = $itemData['level'] ?? 1;
            $item->rarity = $itemData['rarity'] ?? 'COMMON';
            $item->description = $itemData['description'] ?? '';
            $item->damage = $itemData['damage'] ?? 0;
            $item->defense = $itemData['defense'] ?? 0;
            $item->health_bonus = $itemData['healthBonus'] ?? 0;
            $item->mana_bonus = $itemData['manaBonus'] ?? 0;
            $item->icon_path = $itemData['iconPath'] ?? '';
            $item->socket_count = $itemData['socketCount'] ?? 0;
$item->difficulty = $itemData['difficulty'] ?? 1;

            if (!$item->id) {
                $item->id = \Yii::$app->security->generateRandomString(36);
            }

            if (!$item->save()) {
                Yii::error('Failed to save item: ' . print_r($item->errors, true));
                throw new BadRequestHttpException('Failed to save item data');
            }
        }

        $char = $this->getCharacter();

        // Находим свободный слот
        $slots = Inventory::find()->where(['character_id' => $char->id])->select('slot_index')->column();
        $freeSlot = null;
        for ($i = 0; $i < 20; $i++) {
            if (!in_array($i, $slots)) {
                $freeSlot = $i;
                break;
            }
        }
        if ($freeSlot === null) {
            throw new BadRequestHttpException('Inventory full');
        }

        $inv = new Inventory();
        $inv->character_id = $char->id;
        $inv->item_id = $item->id;
        $inv->slot_index = $freeSlot;
        $inv->equipped = 0;
        $inv->equipped_slot = null;
        if (!$inv->save()) {
            Yii::error('Inventory save error: ' . print_r($inv->errors, true));
            throw new BadRequestHttpException('Failed to add item to inventory');
        }

        $char->refresh();
        return ['success' => true, 'slot' => $freeSlot, 'character' => $char->toApiResponse()];
    }

    public function actionEquip()
    {
        $slot = (int) Yii::$app->request->post('slot');
        if ($slot === null) throw new BadRequestHttpException('Missing slot');

        $char = $this->getCharacter();
        $inv = Inventory::find()->where(['character_id' => $char->id, 'slot_index' => $slot])->one();
        if (!$inv) throw new BadRequestHttpException('Item not in inventory');

        $item = $inv->item;
        if (!$item) throw new BadRequestHttpException('Item not found');

        $equipSlotMap = [
            'Helmet' => 'helmet',
            'Chest' => 'chest',
            'Weapon' => 'weapon',
            'Shield' => 'shield',
            'Legs' => 'legs',
            'Boots' => 'boots',
            'Gloves' => 'gloves',
        ];
        $equipSlot = $equipSlotMap[$item->type] ?? null;
        if (!$equipSlot) throw new BadRequestHttpException('Cannot equip this item');

        // Снимаем старый
        $old = Inventory::find()->where([
            'character_id' => $char->id,
            'equipped_slot' => $equipSlot,
            'equipped' => 1
        ])->one();
        if ($old) {
            $old->equipped = 0;
            $old->equipped_slot = null;
            if (!$old->save()) {
                Yii::error('Failed to unequip old item');
                throw new BadRequestHttpException('Failed to unequip old item');
            }
        }

        $inv->equipped = 1;
        $inv->equipped_slot = $equipSlot;
        if (!$inv->save()) {
            Yii::error('Failed to equip new item: ' . print_r($inv->errors, true));
            throw new BadRequestHttpException('Failed to equip item');
        }

        $char->refresh();
        return ['success' => true, 'character' => $char->toApiResponse()];
    }

public function actionUnequip()
{
    $equippedSlot = Yii::$app->request->post('equipped_slot');
    if (!$equippedSlot) {
        throw new BadRequestHttpException('Missing equipped_slot');
    }

    $char = $this->getCharacter();
    $inv = Inventory::find()->where([
        'character_id' => $char->id,
        'equipped_slot' => $equippedSlot,
        'equipped' => 1
    ])->one();

    if (!$inv) {
        throw new BadRequestHttpException('Item not equipped');
    }

    $inv->equipped = 0;
    $inv->equipped_slot = null;
    if (!$inv->save()) {
        Yii::error('Failed to unequip item: ' . print_r($inv->errors, true));
        throw new BadRequestHttpException('Failed to unequip item');
    }

    $char->refresh();
    return ['success' => true, 'character' => $char->toApiResponse()];
}

public function actionDrop()
{
    $slot = (int) Yii::$app->request->post('slot');
    if ($slot === null) throw new BadRequestHttpException('Missing slot');

    $char = $this->getCharacter();
    $inv = Inventory::find()->where(['character_id' => $char->id, 'slot_index' => $slot])->one();
    if (!$inv) throw new BadRequestHttpException('Item not found');

    $item = $inv->item;
    $itemId = $inv->item_id;

    // ===== НАЧИСЛЯЕМ ЗОЛОТО =====
    if ($item) {
        // Цена продажи = уровень * 5 (как в клиенте)
        $price = max(1, $item->level * 5);
        $char->gold += $price;
        if (!$char->save()) {
            Yii::error('Failed to save character gold: ' . print_r($char->errors, true));
            throw new BadRequestHttpException('Failed to update gold');
        }
        Yii::info("Sold item {$item->name} for $price gold", 'inventory');
    }

    // Удаляем из инвентаря
    $inv->delete();

    // Проверяем, используется ли этот предмет ещё кем-то
    $exists = Inventory::find()->where(['item_id' => $itemId])->exists();
    if (!$exists && $item) {
        $item->delete();
        Yii::info("Item {$item->name} deleted from items table", 'inventory');
    }

    $char->refresh();
    return ['success' => true, 'character' => $char->toApiResponse()];
}
}