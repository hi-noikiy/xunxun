<?php

namespace Api\Model;
use Think\Model;

class CoindetailModel extends Model{
    /**获取该用户在某个对应房间消费爵位操作
     * @param $user_id
     * @return false|
     */
   	public function getRoomduke($user_id){
   	    $where['uid'] = $user_id;
        $where['room_id'] = array('gt','0');
        $where['status'] = 1;
        //首先查出消费记录里所有房间id,对应房间id进行统计coin，然后根据coin与爵位进行对比得到数据
        $field = "room_id,sum(coin)as coin";
        return $this ->field($field)->where($where)->group('room_id') ->having('coin>=1000') -> select();
        //SELECT id as free_id,`room_id`,sum(coin)as coin FROM `zb_coindetail` WHERE ( `uid` = 10063 ) AND ( `room_id` > '0' ) AND ( `status` = 1 ) GROUP BY room_id
//         echo $this->_Sql();die();
   	}
    /**获取该用户在某个对应用户消费爵位操作
     * @param $user_id
     * @return false|
     */
    public function getMemberduke($user_id){
        $where['uid'] = $user_id;
        $where['touid'] = array('gt','0');
        $where['status'] = 1;
        $field = "touid,sum(coin)as coin";
        return $this ->field($field)->where($where)->group('touid') ->having('coin>=1000') -> select();
    }

    /**获取该用户在某个对应用户消费爵位等级操作
     * @param $uid 消费用户
     * @param $user_id  收到礼物用户
     * @return false|
     */
    public function getMembercoin($uid,$user_id){
        $where['uid'] = $uid;
        $where['touid'] = $user_id;
        $where['status'] = 1;
        $field = "sum(coin)as coin";
        return $this ->field($field)->where($where)->group('touid') ->having('coin>=1000') -> select();
//        echo $this->_Sql();die();
    }
        /*
     * 增加数据
     */
    public function addData($data){
        return $this-> add($data);
    }
        /*
     * 查询 消费记录
     */
    public function record($where){
        return $this->where($where)->count();
    }
    
    /**MB明細model
     */
        public function mbdetails($userid,$where,$limit=""){
            return $this->field('uid as userid,action,addtime,coin')
                        ->table('zb_coindetail')
                        ->union("SELECT uid,action,addtime,bean as coin FROM zb_beandetail where uid='{$userid}' and action in('Modes','charges') limit {$limit}",true)       //后期将bean 换成 remark
                        ->where($where)
                        ->select();  
        }
        
                /**钻石明細model
         */
        public function diamonddetails($userid,$where,$limit=""){
         return   $this->field('uid as userid,action,addtime,coin')
            ->table('zb_coindetail')
            ->union("SELECT uid as userid,action,addtime,bean as coin FROM zb_beandetail where uid='{$userid}' and action in('get_gift') limit {$limit}",true)       //后期将bean 换成 remark
            ->where($where)
            ->select();
        }
        
            /*财富榜
     */
    public function randcoinsort($where){
     $field='uid userid,sum(coin) coin,m.nickname,m.avatar,m.sex';
     return   $this->field($field)
        ->join('left join zb_member m on uid=m.id')
        ->where($where)
        ->order('coin desc')
        ->group("uid")   
        ->limit(20)
        ->select();
     // $this->_Sql();die();
    }
}
