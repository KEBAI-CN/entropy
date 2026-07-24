<?php
namespace app\controller\Api;

use think\facade\Request;
use think\response\Json;
use think\facade\Cache;
use app\model\Coupon as CouponModel;
use app\model\Shop;

class Coupon
{
    private function getUserInfo()
    {
        $authorization = Request::header('Authorization');
        if (!$authorization) return null;
        $token = str_replace('Bearer ', '', $authorization);
        $cached = Cache::get('token_' . $token);
        if (!$cached) return null;
        
        if (is_array($cached)) {
            if (!isset($cached['type'])) return null;
            return ['id' => $cached['id'], 'type' => $cached['type']];
        } else {
            return ['id' => $cached, 'type' => 'admin'];
        }
    }

    /**
     * Check coupon validity
     */
    public function check(): Json
    {
        $code = input('code');
        $shopId = input('shop_id');
        
        if (empty($code)) {
            return json(['code' => 400, 'msg' => '请输入优惠券代码']);
        }

        $coupon = CouponModel::where('code', $code)->find();
        
        if (!$coupon) {
            return json(['code' => 404, 'msg' => '优惠券不存在']);
        }

        if ($coupon->status != 1) {
            return json(['code' => 400, 'msg' => '优惠券已失效']);
        }

        if ($coupon->quantity <= $coupon->used_quantity) {
            return json(['code' => 400, 'msg' => '优惠券已领完']);
        }

        // Check ownership (if not platform coupon, must belong to shop)
        if ($coupon->is_platform == 0) {
            if (!$shopId) {
                return json(['code' => 400, 'msg' => '参数缺失: shop_id']);
            }
            
            $shop = Shop::find($shopId);
            if (!$shop) {
                return json(['code' => 404, 'msg' => '店铺不存在']);
            }
            
            if ($coupon->user_id != $shop->user_id) {
                return json(['code' => 400, 'msg' => '该优惠券不适用于此店铺']);
            }
        }

        return json([
            'code' => 200, 
            'msg' => '优惠券可用', 
            'data' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => intval($coupon->type ?? 1),
                'amount' => $coupon->amount,
                'min_amount' => floatval($coupon->min_amount ?? 0),
                'is_platform' => $coupon->is_platform
            ]
        ]);
    }

    /**
     * Get user coupon list
     */
    public function list(): Json
    {
        $user = $this->getUserInfo();
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $limit = input('limit', 20);
        $page = input('page', 1);
        $code = input('code', '');

        $where = [];
        $where[] = ['user_id', '=', $user['id']]; // Only show own coupons
        
        if ($code) {
            $where[] = ['code', 'like', '%' . $code . '%'];
        }

        $list = CouponModel::where($where)
            ->order('create_time', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $list
        ]);
    }

    /**
     * Generate coupon
     */
    public function save(): Json
    {
        $user = $this->getUserInfo();
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $id = input('id'); 
        $data = input('post.');
        
        if ($id) {
            // Edit mode
            $coupon = CouponModel::where('id', $id)->where('user_id', $user['id'])->find();
            if (!$coupon) {
                return json(['code' => 404, 'msg' => '优惠券不存在']);
            }
            // Only allow changing status for now
            if (isset($data['status'])) {
                $coupon->status = $data['status'];
                $coupon->save();
            }
            return json(['code' => 200, 'msg' => '更新成功']);
        }

        // Create mode
        if (empty($data['code'])) {
            return json(['code' => 400, 'msg' => '优惠券代码不能为空']);
        }

        $type = intval($data['type'] ?? 1);
        if (!in_array($type, [1, 2, 3])) {
            return json(['code' => 400, 'msg' => '优惠券类型无效']);
        }

        if ($type === 3) {
            if (!isset($data['amount']) || $data['amount'] <= 0 || $data['amount'] >= 10) {
                return json(['code' => 400, 'msg' => '折扣率无效，请输入0.1~9.9']);
            }
        } else {
            if (!isset($data['amount']) || $data['amount'] <= 0) {
                return json(['code' => 400, 'msg' => '优惠金额无效']);
            }
        }

        if (!isset($data['quantity']) || $data['quantity'] < 1) {
            return json(['code' => 400, 'msg' => '可使用次数无效']);
        }

        $minAmount = floatval($data['min_amount'] ?? 0);
        if ($type === 2 && $minAmount <= 0) {
            return json(['code' => 400, 'msg' => '满减券必须设置最低消费金额']);
        }

        // Check if code exists
        if (CouponModel::where('code', $data['code'])->find()) {
            return json(['code' => 400, 'msg' => '优惠券代码已存在']);
        }

        $amount = floatval($data['amount']);
        $quantity = intval($data['quantity']);

        // Admin bypasses balance check
        if ($user['type'] === 'admin') {
            $couponData = [
                'user_id' => 0, // Admin created
                'code' => $data['code'],
                'type' => $type,
                'amount' => $amount,
                'min_amount' => $minAmount,
                'discount' => 0,
                'quantity' => $quantity,
                'used_quantity' => 0,
                'status' => 1,
                'is_platform' => 1, // Platform coupon
            ];
            CouponModel::create($couponData);
            return json(['code' => 200, 'msg' => '生成成功']);
        }

        $couponData = [
            'user_id' => $user['id'],
            'code' => $data['code'],
            'type' => $type,
            'amount' => $amount,
            'min_amount' => $minAmount,
            'discount' => 0,
            'quantity' => $quantity,
            'used_quantity' => 0,
            'status' => 1,
            'is_platform' => 0,
        ];
        CouponModel::create($couponData);

        return json(['code' => 200, 'msg' => '生成成功']);
    }

    /**
     * Delete coupon (Soft delete)
     */
    public function delete(): Json
    {
        $user = $this->getUserInfo();
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $id = input('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $coupon = CouponModel::where('id', $id)->where('user_id', $user['id'])->find();
        if (!$coupon) {
            return json(['code' => 404, 'msg' => '优惠券不存在']);
        }

        $coupon->delete();
        return json(['code' => 200, 'msg' => '删除成功']);
    }
}
