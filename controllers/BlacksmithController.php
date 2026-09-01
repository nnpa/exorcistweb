<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use app\models\Character;
use app\models\Inventory;
use app\models\Item;
use app\models\ItemSocket;

class BlacksmithController extends Controller
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
                'insert' => ['post'],
                'remove' => ['post'],
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

    // ===== Предметы с пустыми сокетами (не надетые) =====
    public function actionItems()
    {
        $char = $this->getCharacter();

        $invRows = Inventory::find()
            ->where(['character_id' => $char->id, 'equipped' => 0])
            ->all();

        $result = [];

        foreach ($invRows as $inv) {
            $item = $inv->item;
            if (!$item || $item->socket_count <= 0) continue;
            if ($item->type === 'Gem') continue;

            $sockets = $this->buildSocketList($item);

            $emptyCount = 0;
            foreach ($sockets as $s) {
                if ($s['gem'] === null) $emptyCount++;
            }
            if ($emptyCount <= 0) continue;

            $result[] = [
                'item' => $item->toApiArray(),
                'slot' => $inv->slot_index,
                'sockets' => $sockets,
            ];
        }

        return ['success' => true, 'items' => $result];
    }

    // ===== Камни в инвентаре (не надетые) =====
    public function actionGems()
    {
        $char = $this->getCharacter();

        $invRows = Inventory::find()
            ->where(['character_id' => $char->id, 'equipped' => 0])
            ->all();

        $result = [];

        foreach ($invRows as $inv) {
            $item = $inv->item;
            if (!$item || $item->type !== 'Gem') continue;

            $result[] = [
                'item' => $item->toApiArray(),
                'slot' => $inv->slot_index,
            ];
        }

        return ['success' => true, 'gems' => $result];
    }

    private function buildSocketList(Item $item)
    {
        $rows = ItemSocket::find()->where(['item_id' => $item->id])->all();

        $byIndex = [];
        foreach ($rows as $r) {
            $byIndex[$r->socket_index] = $r;
        }

        $sockets = [];
        for ($i = 0; $i < $item->socket_count; $i++) {
            $row = $byIndex[$i] ?? null;
            $gem = null;
            if ($row && $row->gem_item_id) {
                $gemItem = $row->gem;
                if ($gemItem) {
                    $gem = $gemItem->toApiArray();
                }
            }
            $sockets[] = ['index' => $i, 'gem' => $gem];
        }

        return $sockets;
    }

    // ===== Вставить камень в сокет =====
    public function actionInsert()
    {
        $itemId = Yii::$app->request->post('itemId');
        $socketIndex = (int) Yii::$app->request->post('socketIndex');
        $gemItemId = Yii::$app->request->post('gemItemId');

        if (!$itemId || !$gemItemId) {
            throw new BadRequestHttpException('Missing itemId or gemItemId');
        }

        $char = $this->getCharacter();

        $itemInv = Inventory::find()->where([
            'character_id' => $char->id,
            'item_id' => $itemId,
            'equipped' => 0,
        ])->one();

        if (!$itemInv) {
            throw new BadRequestHttpException('Item not found in inventory or is equipped');
        }

        $item = $itemInv->item;
        if (!$item || $socketIndex < 0 || $socketIndex >= $item->socket_count) {
            throw new BadRequestHttpException('Invalid socket index');
        }

        $gemInv = Inventory::find()->where([
            'character_id' => $char->id,
            'item_id' => $gemItemId,
            'equipped' => 0,
        ])->one();

        if (!$gemInv) {
            throw new BadRequestHttpException('Gem not found in inventory');
        }

        $gemItem = $gemInv->item;
        if (!$gemItem || $gemItem->type !== 'Gem') {
            throw new BadRequestHttpException('Selected item is not a gem');
        }

        $existing = ItemSocket::find()->where([
            'item_id' => $itemId,
            'socket_index' => $socketIndex,
        ])->one();

        if ($existing && $existing->gem_item_id) {
            throw new BadRequestHttpException('Socket already occupied');
        }

        $socket = $existing ?? new ItemSocket();
        $socket->item_id = $itemId;
        $socket->socket_index = $socketIndex;
        $socket->gem_item_id = $gemItemId;

        if (!$socket->save()) {
            Yii::error('Failed to save socket: ' . print_r($socket->errors, true));
            throw new BadRequestHttpException('Failed to insert gem');
        }

        // Камень теперь "внутри" сокета — убираем из инвентаря
        $gemInv->delete();

        if (!$char->recalcStats()) {
            throw new BadRequestHttpException('Failed to update character stats');
        }

        $char->refresh();
        return ['success' => true, 'character' => $char->toApiResponse()];
    }

    // ===== Извлечь камень из сокета =====
    public function actionRemove()
    {
        $itemId = Yii::$app->request->post('itemId');
        $socketIndex = (int) Yii::$app->request->post('socketIndex');

        if (!$itemId) {
            throw new BadRequestHttpException('Missing itemId');
        }

        $char = $this->getCharacter();

        $itemInv = Inventory::find()->where([
            'character_id' => $char->id,
            'item_id' => $itemId,
        ])->one();

        if (!$itemInv) {
            throw new BadRequestHttpException('Item not found in inventory');
        }

        $socket = ItemSocket::find()->where([
            'item_id' => $itemId,
            'socket_index' => $socketIndex,
        ])->one();

        if (!$socket || !$socket->gem_item_id) {
            throw new BadRequestHttpException('Socket is empty');
        }

        $gemItemId = $socket->gem_item_id;

        $usedSlots = Inventory::find()
            ->where(['character_id' => $char->id])
            ->andWhere(['not', ['slot_index' => null]])
            ->select('slot_index')
            ->column();

        $freeSlot = null;
        for ($i = 0; $i < 20; $i++) {
            if (!in_array($i, $usedSlots)) { $freeSlot = $i; break; }
        }

        if ($freeSlot === null) {
            throw new BadRequestHttpException('Inventory full, cannot remove gem');
        }

        $gemInv = new Inventory();
        $gemInv->character_id = $char->id;
        $gemInv->item_id = $gemItemId;
        $gemInv->slot_index = $freeSlot;
        $gemInv->equipped = 0;
        $gemInv->equipped_slot = null;

        if (!$gemInv->save()) {
            throw new BadRequestHttpException('Failed to return gem to inventory');
        }

        $socket->gem_item_id = null;
        if (!$socket->save()) {
            throw new BadRequestHttpException('Failed to clear socket');
        }

        if (!$char->recalcStats()) {
            throw new BadRequestHttpException('Failed to update character stats');
        }

        $char->refresh();
        return ['success' => true, 'character' => $char->toApiResponse()];
    }
    
    public function actionAllSockets()
{
    $char = $this->getCharacter();

    $itemIds = [];
    foreach ($char->inventoryItems as $inv) {
        if ($inv->item) {
            $itemIds[] = $inv->item->id;
        }
    }

    $result = [];

    if (!empty($itemIds)) {

        $sockets = \app\models\ItemSocket::find()
            ->where(['item_id' => $itemIds])
            ->andWhere(['not', ['gem_item_id' => null]])
            ->all();

        foreach ($sockets as $socket) {

            $gem = $socket->gem;
            if (!$gem) {
                continue;
            }

            if (!isset($result[$socket->item_id])) {
                $result[$socket->item_id] = [];
            }

            $result[$socket->item_id][] = [
                'index' => $socket->socket_index,
                'gem' => $gem->toApiArray(),
            ];
        }
    }

    return ['success' => true, 'sockets' => $result];
}
}