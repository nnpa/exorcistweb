<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class CharacterTalent extends ActiveRecord
{
    public static function tableName()
    {
        return 'character_talents';
    }

    public function rules()
    {
        return [
            [['character_id', 'talent_id', 'level'], 'required'],
            [['character_id', 'level'], 'integer'],
            [['talent_id'], 'string', 'max' => 50],
        ];
    }

    public function getCharacter()
    {
        return $this->hasOne(Character::class, ['id' => 'character_id']);
    }
}