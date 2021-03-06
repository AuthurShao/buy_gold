<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class GoldFlow extends Model
{
    use SoftDeletes;
    /**
     * @var string
     */
    protected $table = 'gold_flow';
    /**
     * @var array
     */
    protected $fillable = ['type','gold','is_income','user_id','other'];
    /**
     * @var array
     */
    protected $hidden = ['buy_gold_detail','other','order'];
    /**
     * @var int
     */
    public $query_page = 15;
    /**
     * @var array
     */
    protected $and_fields = ['user_id','type','is_income'];
    /**
     * @var array
     */
    protected $appends = ["order","show_type"];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function buy_gold_detail()
    {
        return $this->hasOne('App\BuyGoldDetail','flow_id');
    }
    /**
     * @return array
     */
    public function getAndFieds():array
    {
        return $this->and_fields??[];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member()
    {
        return $this->belongsTo('App\Member','user_id');
    }

    /**
     * @return mixed
     */
    public function getOrderAttribute()
    {
          return  $this->buy_gold_detail->buy_gold ?? null;
    }

    /**
     * @return float
     * @see 金币池出
     */
    public function getGoldPullOut():float
    {
        // 领取扣除
        $fGoldA = $this->getAutoGoldSum();
        // 下线免费领取金币（含手动和自）上线得20%
        $fParentGold = $this->getParentAutoGoldSum();
        // 充值
        $fGoldR = $this->getRechargeNum(9);
        return bcadd(bcadd($fGoldA,$fGoldR,2),$fParentGold,2);
    }

    /**
     * @param int $iType 9 后台充值增加 10后台充值减少
     * @return float
     * @see 金币充值
     */
    public function getRechargeNum(int $iType):float
    {
        return $this->where(['is_statistical' => 0,'type'=>$iType])->sum('gold');
    }
    /**
     * @return float金币池进
     */
    public function getGoldPullIn():float
    {
        // 金币燃烧返回金币池
        $bNum = $this->getReturnBurnGoldSum();
        // 金币购物返回金币池
        $sNum = $this->getReturnShopGoldNum();
        // 充值扣除返回金币池
        $rNum = $this->getRechargeNum(10);
        // 积分兑换返回金币池
        $iNum = $this->getReturnIntegralToGold();
        // 15天没有登陆返回
        $nNum = $this->getReturnNotLoginGoldNum();

        return bcadd(bcadd($bNum,$sNum,5),bcadd(bcadd($rNum,$nNum,5),$iNum,5),2);
    }

    /**
     * @return float
     * @see 购物消耗
     * @see 购物金币流向金币池
     */
    public function getReturnShopGoldNum():float
    {
        return $this->where(['is_statistical' => 0,'type'=>12])->sum('gold');
    }

    /**
     * @return float
     * @see 金币兑换
     */
    public function getReturnIntegralToGold():float
    {
        return $this->where(['is_statistical' => 0,'type'=>16])->sum('gold');
    }

    /**
     * @return float
     */
    public function getReturnBurnGoldSum():float
    {
        return $this->where(['is_statistical' => 0,'type'=>5])->sum('gold');
    }

    /**
     * @return float
     */
    public function getReturnNotLoginGoldNum():float
    {
        return $this->where(['is_statistical' => 0,'type'=>14])->sum('gold') ?? 0.00;
    }

    /**
     * @return float
     * @彻底燃烧金币
     */
    public function getBurnGoldSum():float
    {
        return $this->where('type',11)->sum('gold');
    }

    /**
     * @return float
     */
    public function getAutoGoldSum():float
    {
        return $this->where(['is_statistical' => 0,'type'=> 4])->sum('gold');
    }

    /**
     * @return float
     * @see 下线免费领取金币（含手动和自）上线得20%
     */
    public function getParentAutoGoldSum():float
    {
        return $this->where(['is_statistical' => 0,'type'=> 21])->sum('gold') ?? 0.00;
    }

    /**
     * @see 获取最后一条领取数据
     */
    public function getLastAutoGold()
    {
        return $this->where('user_id',userId())->where('type',4)->orderBy('id','desc')->first() ?? [];
    }

    /**
     * @see
     */
    public function getShowTypeAttribute()
    {
        return config("czf.gold_show_type")[$this->type];
    }

}
