<?php
namespace Api\Service;

use Api\Model\CoindetailModel;
use Think\Service;

class CoindetailService extends Service
{
    protected $_modelName = '\Api\Model\CoindetailModel';

    /**
     * @var AreaModel
     */
    protected $_model;

    //查询礼物方法
    public function get_gift($touid,$giftList,$is_num='')
    {
        $img = C("APP_URL_image");
        $coinRes = $this->getGiftCount($touid);
        if (empty($coinRes)) {
            return [];
        }
        $res = [];
        $num = 0;
        foreach ($giftList as $key => $value) {
            if (!empty($coinRes[$value['gift_id']])) {
                $res[$key]['gift_image'] = $value['gift_image']?$img.$value['gift_image']:'';
                $res[$key]['gift_animation'] = $value['gift_animation']?$img.$value['gift_animation']:'';
                $res[$key]['animation'] = $value['animation']?$img.$value['animation']:'';
                $res[$key]['num'] = $coinRes[$value['gift_id']];
                $res[$key]['gift_name'] = $value['gift_name']?$value['gift_name']:'';
                $res[$key]['gift_type'] = $value['gift_type'];
                $num += $coinRes[$value['gift_id']];
            }
        }
        $res = array_values($res);
        if (!empty($is_num)) {//判断返回个数
            return [$res,$num];
        }else{
            return $res;
        }
        
    }

    /**获取该用户在某个对应房间消费爵位操作(统计不同房间的同一房间的数据)
     * @return mixed
     */
    public function getRoomduke($user_id){
        return $this -> _model -> getRoomduke($user_id);
    }

    /**获取用户在某个对应用户消费爵位操作(统计同一用户的数据)
     * @return mixed
     */
    public function getMemberduke($user_id){
        return $this -> _model -> getMemberduke($user_id);
    }

    /**获取用户在某个对应用户消费爵位等级名称操作(统计同一用户的数据)
     * @param uid 当前消费用户id
     * @param user_id   获取礼物用户id
     * @return mixed
     */
    public function getMembercoin($uid,$user_id){
        return $this -> _model -> getMembercoin($uid,$user_id);
    }

    //查询收到礼物个数
    public function getGiftCount($uid)
    {
        $res = $this -> _model -> getSelfList($uid);
        if ($res) {
            $data = array_column($res,'num', 'giftid');
        }
        return $data;
    }


}