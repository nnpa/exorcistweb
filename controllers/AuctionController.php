<?php
// controllers/AuctionController.php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use app\models\Character;
use app\models\Auction;
use app\models\Inventory;
use app\models\Item;

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
                'sell' => ['post'],
                'buy' => ['post'],
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

    // GET /auction/list – пагинация и фильтры
    public function actionList()
    {
        $query = Auction::find()->where(['sold' => 0])->orderBy(['id' => SORT_DESC]);

        // Фильтры
        $type = Yii::$app->request->get('type');
        $rarity = Yii::$app->request->get('rarity');
        $minLevel = (int)Yii::$app->request->get('minLevel');
        $maxLevel = (int)Yii::$app->request->get('maxLevel');
        if ($type) $query->joinWith('item')->andWhere(['item.type' => $type]);
        if ($rarity) $query->joinWith('item')->andWhere(['item.rarity' => $rarity]);
        if ($minLevel) $query->joinWith('item')->andWhere(['>=', 'item.level', $minLevel]);
        if ($maxLevel) $query->joinWith('item')->andWhere(['<=', 'item.level', $maxLevel]);

        $page = (int)Yii::$app->request->get('page', 1);
        $perPage = 20;
        $total = $query->count();
        $query->offset(($page - 1) * $perPage)->limit($perPage);

        $list = [];
        foreach ($query->all() as $lot) {
            $list[] = $lot->toApiArray();
        }

        return [
            'items' => $list,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    // POST /auction/sell – передать slot_index и price
    public function actionSell()
    {
        $slot = Yii::$app->request->post('slot');
        $price = (int)Yii::$app->request->post('price');
        if ($slot === null || $price < 0) {
            throw new BadRequestHttpException('Invalid slot or price');
        }

        $char = $this->getCharacter();
        $inv = Inventory::find()->where(['character_id' => $char->id, 'slot_index' => $slot])->one();
        if (!$inv) {
            throw new BadRequestHttpException('Item not found in inventory');
        }
        if ($inv->equipped) {
            throw new BadRequestHttpException('Cannot sell equipped item');
        }

        $item = $inv->item;
        if (!$item) {
            throw new BadRequestHttpException('Item data missing');
        }

        // Удаляем из инвентаря
        $inv->delete();

        // Создаём лот
        $auction = new Auction();
        $auction->seller_id = $char->id;
        $auction->item_id = $item->id;
        $auction->price = $price;
        $auction->save();

        return ['success' => true, 'auction' => $auction->toApiArray()];
    }

    // POST /auction/buy – передать lot_id
    public function actionBuy()
    {
        $lotId = Yii::$app->request->post('lotId');
        if (!$lotId) {
            throw new BadRequestHttpException('Missing lotId');
        }

        $buyer = $this->getCharacter();
        $lot = Auction::findOne($lotId);
        if (!$lot || $lot->sold) {
            throw new BadRequestHttpException('Lot not available');
        }
        if ($lot->seller_id == $buyer->id) {
            throw new BadRequestHttpException('Cannot buy your own lot');
        }

        $item = $lot->item;
        if (!$item) {
            throw new BadRequestHttpException('Item missing');
        }

        // Проверка золота у покупателя
        if ($buyer->gold < $lot->price) {
            throw new BadRequestHttpException('Not enough gold');
        }

        // Транзакция
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Продавец получает золото
            $seller = Character::findOne($lot->seller_id);
            if ($seller) {
                $seller->gold += $lot->price;
                $seller->save();
            }

            // Покупатель отдаёт золото
            $buyer->gold -= $lot->price;
            $buyer->save();

            // Добавляем предмет в инвентарь покупателя
            $slots = Inventory::find()->where(['character_id' => $buyer->id])->select('slot_index')->column();
            $freeSlot = null;
            for ($i = 0; $i < 20; $i++) {
                if (!in_array($i, $slots)) {
                    $freeSlot = $i;
                    break;
                }
            }
            if ($freeSlot === null) {
                throw new BadRequestHttpException('Buyer inventory full');
            }

            $inv = new Inventory();
            $inv->character_id = $buyer->id;
            $inv->item_id = $item->id;
            $inv->slot_index = $freeSlot;
            $inv->equipped = 0;
            $inv->save();

            // Помечаем лот как проданный
            $lot->sold = 1;
            $lot->buyer_id = $buyer->id;
            $lot->save();

            $transaction->commit();

            return [
                'success' => true,
                'character' => $buyer->toApiResponse(),
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}