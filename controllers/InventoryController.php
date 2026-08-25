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

    // ===== ПОДНЯТИЕ ПРЕДМЕТА =====
    public function actionPickup()
    {
        $request = Yii::$app->request;
        $itemData = $request->post('itemData');
        if (!$itemData) {
            throw new BadRequestHttpException('Missing itemData');
        }

        $char = $this->getCharacter();

        // 1. Пытаемся найти существующий предмет по ID
        $itemId = $itemData['id'] ?? null;
        $item = null;

        if ($itemId) {
            $item = Item::findOne($itemId);
        }

        // Если предмет не найден — создаём новый
        if (!$item) {
            $item = new Item();
            $item->id = $itemId ?? \Yii::$app->security->generateRandomString(36);
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

            if (!$item->save()) {
                Yii::error('Failed to save item: ' . print_r($item->errors, true));
                throw new BadRequestHttpException('Failed to save item data');
            }
        }

        // 2. Поиск свободного слота (0..19)
        $usedSlots = Inventory::find()
            ->where(['character_id' => $char->id])
            ->andWhere(['not', ['slot_index' => null]])
            ->select('slot_index')
            ->column();

        $freeSlot = null;
        for ($i = 0; $i < 20; $i++) {
            if (!in_array($i, $usedSlots)) {
                $freeSlot = $i;
                break;
            }
        }

        if ($freeSlot === null) {
            throw new BadRequestHttpException('Inventory full');
        }

        // 3. Добавляем в инвентарь
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

    // ===== ЭКИПИРОВКА =====
   

    public function actionEquip()
{
    $slot = (int) Yii::$app->request->post('slot');
    if ($slot === null) {
        throw new BadRequestHttpException('Missing slot');
    }

    $char = $this->getCharacter();

    // Ищем предмет в указанном слоте
    $inv = Inventory::find()->where([
        'character_id' => $char->id,
        'slot_index' => $slot
    ])->one();

    if (!$inv) {
        throw new BadRequestHttpException("No item in slot {$slot}");
    }

    if ($inv->equipped == 1) {
        throw new BadRequestHttpException("Item in slot {$slot} is already equipped");
    }

    $item = $inv->item;
    if (!$item) {
        throw new BadRequestHttpException('Item not found');
    }

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
    if (!$equipSlot) {
        throw new BadRequestHttpException('Cannot equip this item');
    }

    // ===== СНИМАЕМ СТАРЫЙ ПРЕДМЕТ С ЭТОГО ЖЕ СЛОТА (если есть) =====
    $old = Inventory::find()->where([
        'character_id' => $char->id,
        'equipped_slot' => $equipSlot,
        'equipped' => 1
    ])->one();

    if ($old) {
        // Находим свободный слот в инвентаре (0..19)
        $usedSlots = Inventory::find()
            ->where(['character_id' => $char->id])
            ->andWhere(['not', ['slot_index' => null]])
            ->select('slot_index')
            ->column();

        $freeSlot = null;
        for ($i = 0; $i < 20; $i++) {
            if (!in_array($i, $usedSlots)) {
                $freeSlot = $i;
                break;
            }
        }

        if ($freeSlot === null) {
            throw new BadRequestHttpException('Inventory full! Cannot unequip old item.');
        }

        // Снимаем старый предмет, помещая его в свободный слот
        $old->equipped = 0;
        $old->equipped_slot = null;
        $old->slot_index = $freeSlot;
        if (!$old->save()) {
            Yii::error('Failed to unequip old item: ' . print_r($old->errors, true));
            throw new BadRequestHttpException('Failed to unequip old item');
        }
    }

    // ===== ЭКИПИРУЕМ НОВЫЙ ПРЕДМЕТ =====
    $inv->equipped = 1;
    $inv->equipped_slot = $equipSlot;
    $inv->slot_index = null; // освобождаем слот

    if (!$inv->save()) {
        Yii::error('Failed to equip new item: ' . print_r($inv->errors, true));
        throw new BadRequestHttpException('Failed to equip item: ' . json_encode($inv->errors));
    }

    // Пересчёт статов персонажа
    if (!$char->recalcStats()) {
        throw new BadRequestHttpException('Failed to update character stats');
    }
    $char->refresh();

    return ['success' => true, 'character' => $char->toApiResponse()];
}
    // ===== ВЫБРОС =====
    public function actionDrop()
    {
        $slot = (int) Yii::$app->request->post('slot');
        if ($slot === null) throw new BadRequestHttpException('Missing slot');

        $char = $this->getCharacter();
        $inv = Inventory::find()->where(['character_id' => $char->id, 'slot_index' => $slot])->one();
        if (!$inv) throw new BadRequestHttpException('Item not found');

        $item = $inv->item;
        $itemId = $inv->item_id;

        if ($item) {
            $price = max(1, $item->level * 5);
            $char->gold += $price;
            if (!$char->save()) {
                Yii::error('Failed to save character gold: ' . print_r($char->errors, true));
                throw new BadRequestHttpException('Failed to update gold');
            }
            Yii::info("Sold item {$item->name} for $price gold", 'inventory');
        }

        $inv->delete();

        $exists = Inventory::find()->where(['item_id' => $itemId])->exists();
        if (!$exists && $item) {
            $item->delete();
            Yii::info("Item {$item->name} deleted from items table", 'inventory');
        }

        $char->refresh();
        return ['success' => true, 'character' => $char->toApiResponse()];
    }
    
    public function actionUnequip()
{
    $request = Yii::$app->request;
    $equippedSlot = $request->post('equipped_slot');
    if (!$equippedSlot) {
        throw new BadRequestHttpException('Missing equipped_slot');
    }

    $char = $this->getCharacter();

    // Найти экипированный предмет в указанном слоте
    $inv = Inventory::find()->where([
        'character_id' => $char->id,
        'equipped_slot' => $equippedSlot,
        'equipped' => 1
    ])->one();

    if (!$inv) {
        throw new BadRequestHttpException("No item equipped in slot '$equippedSlot'");
    }

    // Найти свободный слот в инвентаре (0..19)
    $usedSlots = Inventory::find()
        ->where(['character_id' => $char->id])
        ->andWhere(['not', ['slot_index' => null]])
        ->select('slot_index')
        ->column();

    $freeSlot = null;
    for ($i = 0; $i < 20; $i++) {
        if (!in_array($i, $usedSlots)) {
            $freeSlot = $i;
            break;
        }
    }

    if ($freeSlot === null) {
        throw new BadRequestHttpException('Inventory is full, cannot unequip');
    }

    // Снять предмет
    $inv->equipped = 0;
    $inv->equipped_slot = null;
    $inv->slot_index = $freeSlot;

    if (!$inv->save()) {
        Yii::error('Failed to unequip: ' . print_r($inv->errors, true));
        throw new BadRequestHttpException('Failed to unequip item');
    }

    // Пересчитать статы персонажа (если нужно)
    if (!$char->recalcStats()) {
        throw new BadRequestHttpException('Failed to update character stats');
    }
    $char->refresh();

    return ['success' => true, 'character' => $char->toApiResponse()];
}
}