<?php

namespace app\controller\Admin;

use app\BaseController;
use app\model\Coupon as CouponModel;
use app\model\User;
use think\response\Json;

class Coupon extends BaseController
{
    /**
     * Get coupon list
     */
    public function index(): Json
    {
        $limit = input('limit', 20);
        $page = input('page', 1);
        $code = input('code', '');
        $userId = input('user_id');
        $isPlatform = input('is_platform');

        $where = [];
        if ($code) {
            $where[] = ['code', 'like', '%' . $code . '%'];
        }
        if ($userId !== null && $userId !== '') {
            $where[] = ['user_id', '=', $userId];
        }
        if ($isPlatform !== null && $isPlatform !== '') {
            $where[] = ['is_platform', '=', $isPlatform];
        }

        $list = CouponModel::with(['user' => function($query) {
                $query->with(['shop' => function($q) {
                    $q->field('user_id,avatar');
                }])->field('id,username,qq');
            }])
            ->where($where)
            ->order('create_time', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $list
        ]);
    }

    /**
     * Save coupon (Create or Update)
     */
    public function save(): Json
    {
        $id = input('id');
        $data = input('post.');
        
        // Basic validation
        if (empty($data['code'])) {
            return json(['code' => 400, 'msg' => '优惠券代码不能为空']);
        }

        $type = intval($data['type'] ?? 1);
        if (!in_array($type, [1, 2, 3])) {
            return json(['code' => 400, 'msg' => '优惠券类型无效']);
        }

        // 折扣券 amount 为折扣率(0.1~9.9)，其他类型为减免金额
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
            return json(['code' => 400, 'msg' => '发放数量无效']);
        }

        // 满减券必须设置最低消费金额
        $minAmount = floatval($data['min_amount'] ?? 0);
        if ($type === 2 && $minAmount <= 0) {
            return json(['code' => 400, 'msg' => '满减券必须设置最低消费金额']);
        }

        // 确保字段完整
        $data['type'] = $type;
        $data['min_amount'] = $minAmount;

        // Admin generated coupons are always platform coupons
        $data['is_platform'] = 1;
        // If user_id is not provided, it implies a global platform coupon or similar.
        // For now, if user_id is 0, it's a global platform coupon.
        if (!isset($data['user_id'])) {
            $data['user_id'] = 0;
        }

        if ($id) {
            $coupon = CouponModel::find($id);
            if (!$coupon) {
                return json(['code' => 404, 'msg' => '优惠券不存在']);
            }
            // If updating, prevent changing sensitive fields if needed.
            // For now, allow updating.
            $coupon->save($data);
        } else {
            // Check if code exists
            if (CouponModel::where('code', $data['code'])->find()) {
                return json(['code' => 400, 'msg' => '优惠券代码已存在']);
            }
            CouponModel::create($data);
        }

        return json(['code' => 200, 'msg' => '保存成功']);
    }

    /**
     * Delete coupon
     */
    public function delete(): Json
    {
        $id = input('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $coupon = CouponModel::find($id);
        if (!$coupon) {
            return json(['code' => 404, 'msg' => '优惠券不存在']);
        }

        $coupon->delete();
        return json(['code' => 200, 'msg' => '删除成功']);
    }
}
