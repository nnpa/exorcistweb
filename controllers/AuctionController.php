<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use app\models\Character;
use app\models\Item;
use app\models\Inventory;
use app\models\AuctionLot;
use app\models\AuctionLotItem;
use yii\data\Pagination;

class AuctionController extends Controller
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
                'list' => ['get'],
                'my' => ['get'],
                'create' => ['post'],
                'buy' => ['post'],
                'cancel' => ['post'],
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

    // GET /auction/list – список активных лотов с пагинацией и фильтрами
    public function actionList($page = 1, $type = null, $rarity = null, $minLevel = 0, $maxLevel = 100)
    {
        $query = AuctionLot::find()
            ->where(['status' => 0])
            ->andWhere(['>', 'end_time', date('Y-m-d H:i:s')])
            ->joinWith('items');

        if ($type) {
            $query->andWhere(['items.type' => $type]);
        }
        if ($rarity) {
            $query->andWhere(['items.rarity' => $rarity]);
        }
        if ($minLevel > 0) {
            $query->andWhere(['>=', 'items.level', $minLevel]);
        }
        if ($maxLevel < 100) {
            $query->andWhere(['<=', 'items.level', $maxLevel]);
        }

        $countQuery = clone $query;
        $pagination = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => 5,
            'pageParam' => 'page',
        ]);

        $lots = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $result = [];
        foreach ($lots as $lot) {
            $result[] = $lot->toApiResponse();
        }

        return [
            'success' => true,
            'items' => $result,
            'totalPages' => $pagination->getPageCount(),
            'currentPage' => $pagination->getPage() + 1,
        ];
    }

    // GET /auction/my – лоты текущего игрока
    public function actionMy()
    {
        $char = $this->getCharacter();
        $lots = AuctionLot::find()
            ->where(['seller_id' => $char->user_id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $result = [];
        foreach ($lots as $lot) {
            $result[] = $lot->toApiResponse();
        }
        return ['success' => true, 'items' => $result];
    }

    // POST /auction/create – выставить предметы на продажу
    public function actionCreate()
    {
        $char = $this->getCharacter();
        $slotIndices = Yii::$app->request->post('slotIndices'); // массив индексов слотов инвентаря
        $price = (int)Yii::$app->request->post('price');

        $activeLotsCount = AuctionLot::find()
    ->where(['seller_id' => $char->user_id, 'status' => 0])
    ->andWhere(['>', 'end_time', date('Y-m-d H:i:s')])
    ->count();
if ($activeLotsCount >= 5) {
    throw new BadRequestHttpException('You can have at most 5 active lots.');
}

        if (!$slotIndices || !is_array($slotIndices) || count($slotIndices) > 3) {
            throw new BadRequestHttpException('You can sell up to 3 items.');
        }
        if ($price <= 0) {
            throw new BadRequestHttpException('Price must be positive.');
        }

        // Проверяем, что предметы существуют и принадлежат персонажу
        $items = [];
        $slotIndexes = [];
        foreach ($slotIndices as $slotIndex) {
            $inv = Inventory::find()->where([
                'character_id' => $char->id,
                'slot_index' => $slotIndex,
                'equipped' => 0
            ])->one();
            if (!$inv) {
                throw new BadRequestHttpException("Item in slot $slotIndex not found or equipped.");
            }
            $items[] = $inv->item;
            $slotIndexes[] = $slotIndex;
        }

        // Создаём лот
        $lot = new AuctionLot();
        $lot->seller_id = $char->user_id;
        $lot->price = $price;
        $lot->start_time = date('Y-m-d H:i:s');
        $lot->end_time = date('Y-m-d H:i:s', strtotime('+2 days'));
        $lot->status = 0;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$lot->save()) {
                throw new BadRequestHttpException('Failed to create lot.');
            }

            // Привязываем предметы и удаляем их из инвентаря
            foreach ($items as $item) {
                $lotItem = new AuctionLotItem();
                $lotItem->lot_id = $lot->id;
                $lotItem->item_id = $item->id;
                if (!$lotItem->save()) {
                    throw new BadRequestHttpException('Failed to link item to lot.');
                }

                // Удаляем из инвентаря
                $inv = Inventory::find()->where([
                    'character_id' => $char->id,
                    'item_id' => $item->id
                ])->one();
                if ($inv) {
                    $inv->delete();
                }
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }

        $char->refresh();
        return ['success' => true, 'character' => $char->toApiResponse()];
    }

    // POST /auction/buy – купить лот
   public function actionBuy()
{
    $lotId = (int)Yii::$app->request->post('lotId');
    if (!$lotId) {
        throw new BadRequestHttpException('Missing lotId');
    }

    $char = $this->getCharacter();

    $lot = AuctionLot::findOne(['id' => $lotId, 'status' => 0]);
    if (!$lot) {
        throw new BadRequestHttpException('Lot not found or already sold.');
    }

    // Проверяем золото
    if ($char->gold < $lot->price) {
        throw new BadRequestHttpException('Not enough gold.');
    }

    // Проверяем свободные слоты в инвентаре
    $itemCount = $lot->getItems()->count();
    $freeSlots = $this->countFreeInventorySlots($char->id);
    if ($freeSlots < $itemCount) {
        throw new BadRequestHttpException('Not enough inventory space.');
    }

    $transaction = Yii::$app->db->beginTransaction();
    try {
        // Переводим золото
        $char->gold -= $lot->price;
        $sellerChar = Character::findOne(['user_id' => $lot->seller_id]);
        if ($sellerChar) {
            $sellerChar->gold += $lot->price;
            if (!$sellerChar->save()) {
                throw new BadRequestHttpException('Failed to update seller gold.');
            }
        }
        if (!$char->save()) {
            throw new BadRequestHttpException('Failed to update buyer gold.');
        }

        // Перемещаем предметы в инвентарь покупателя
        $items = $lot->getItems()->all();
        foreach ($items as $item) {
            $freeSlot = $this->findFreeSlot($char->id);
            if ($freeSlot === null) {
                throw new BadRequestHttpException('No free slots found.');
            }
            $inv = new Inventory();
            $inv->character_id = $char->id;
            $inv->item_id = $item->id;
            $inv->slot_index = $freeSlot;
            $inv->equipped = 0;
            $inv->equipped_slot = null;
            if (!$inv->save()) {
                throw new BadRequestHttpException('Failed to add item to inventory.');
            }
        }

        // ===== УДАЛЯЕМ ЛОТ И ЕГО СВЯЗИ (вместо смены статуса) =====
        // Явно удаляем связанные AuctionLotItem
        AuctionLotItem::deleteAll(['lot_id' => $lot->id]);
        // Удаляем сам лот
        $lot->delete();

        $transaction->commit();
    } catch (\Exception $e) {
        $transaction->rollBack();
        throw new BadRequestHttpException($e->getMessage());
    }

    $char->refresh();
    return ['success' => true, 'character' => $char->toApiResponse()];
}

    // POST /auction/cancel – снять лот с продажи (до окончания)
    public function actionCancel()
    {
        $lotId = (int)Yii::$app->request->post('lotId');
        if (!$lotId) {
            throw new BadRequestHttpException('Missing lotId');
        }

        $char = $this->getCharacter();
        $lot = AuctionLot::findOne(['id' => $lotId, 'seller_id' => $char->user_id, 'status' => 0]);
        if (!$lot) {
            throw new BadRequestHttpException('Lot not found or already sold.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Возвращаем предметы в инвентарь
            $items = $lot->getItems()->all();
            foreach ($items as $item) {
                $freeSlot = $this->findFreeSlot($char->id);
                if ($freeSlot === null) {
                    throw new BadRequestHttpException('No free slots to return items.');
                }
                $inv = new Inventory();
                $inv->character_id = $char->id;
                $inv->item_id = $item->id;
                $inv->slot_index = $freeSlot;
                $inv->equipped = 0;
                $inv->equipped_slot = null;
                if (!$inv->save()) {
                    throw new BadRequestHttpException('Failed to return item.');
                }
            }

            // Удаляем лот и связи (каскадно)
            $lot->delete();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }

        $char->refresh();
        return ['success' => true, 'character' => $char->toApiResponse()];
    }

    // Вспомогательные методы
    private function countFreeInventorySlots($characterId)
    {
        $usedSlots = Inventory::find()->where(['character_id' => $characterId])->select('slot_index')->column();
        $free = 0;
        for ($i = 0; $i < 20; $i++) {
            if (!in_array($i, $usedSlots)) {
                $free++;
            }
        }
        return $free;
    }

    private function findFreeSlot($characterId)
    {
        $usedSlots = Inventory::find()->where(['character_id' => $characterId])->select('slot_index')->column();
        for ($i = 0; $i < 20; $i++) {
            if (!in_array($i, $usedSlots)) {
                return $i;
            }
        }
        return null;
    }
}