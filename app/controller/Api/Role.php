<?php
namespace app\controller\Api;

use app\model\AuthGroup;
use think\facade\Request;
use think\facade\Cache;
use think\response\Json;
use think\facade\Db;

class Role
{
    private function getAdminInfo()
    {
        $authorization = Request::header('Authorization');
        if (!$authorization) return null;
        $token = str_replace('Bearer ', '', $authorization);
        $cached = Cache::get('token_' . $token);
        
        if (!$cached) return null;
        
        if (is_array($cached)) {
            if (!isset($cached['type']) || $cached['type'] !== 'admin') return null;
            return $cached;
        } else {
            return ['id' => $cached, 'type' => 'admin'];
        }
    }

    /**
     * List roles
     */
    public function list(): Json
    {
        if (!$this->getAdminInfo()) {
            return json(['code' => 401, 'msg' => 'Unauthorized']);
        }

        // Check and restore default Super Admin Role
        $superAdmin = AuthGroup::find(1);
        if (!$superAdmin) {
            AuthGroup::create([
                'id' => 1,
                'name' => '超级管理员',
                'description' => '拥有所有权限，不可删除和编辑',
                'rules' => '*', 
                'status' => 1,
            ]);
        }

        $page = Request::param('current', 1);
        $limit = Request::param('size', 10);
        $keyword = Request::param('keyword', '');

        $query = AuthGroup::order('create_time', 'asc');

        if (!empty($keyword)) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        $list = $query->paginate(['page' => $page, 'list_rows' => $limit]);

        return json([
            'code' => 200, 
            'msg' => 'Success', 
            'data' => [
                'records' => $list->items(),
                'total' => $list->total(),
                'current' => $list->currentPage(),
                'size' => $list->listRows(),
            ]
        ]);
    }

    /**
     * Create role
     */
    public function create(): Json
    {
        if (!$this->getAdminInfo()) {
            return json(['code' => 401, 'msg' => 'Unauthorized']);
        }

        $name = Request::param('name');
        $description = Request::param('description', '');
        $rules = Request::param('rules', ''); // JSON or comma separated string
        $status = Request::param('status', 1);

        if (empty($name)) {
            return json(['code' => 400, 'msg' => '权限组名称不能为空']);
        }

        $exists = AuthGroup::where('name', $name)->find();
        if ($exists) {
            return json(['code' => 400, 'msg' => '权限组名称已存在']);
        }

        AuthGroup::create([
            'name' => $name,
            'description' => $description,
            'rules' => $rules,
            'status' => $status,
        ]);

        return json(['code' => 200, 'msg' => '创建成功']);
    }

    /**
     * Backward-compatible save endpoint.
     */
    public function save(): Json
    {
        $id = Request::param('id');
        return $id ? $this->update() : $this->create();
    }

    /**
     * Update role
     */
    public function update(): Json
    {
        if (!$this->getAdminInfo()) {
            return json(['code' => 401, 'msg' => 'Unauthorized']);
        }

        $id = Request::param('id');
        if (empty($id)) {
            return json(['code' => 400, 'msg' => 'ID不能为空']);
        }

        // Super Admin Protection
        if ($id == 1) {
            // Only update description, status? Or maybe allow nothing but rules?
            // User requirement: "不能删除和编辑这个权限组"
            // So we block update for ID 1 entirely or partially?
            // "不能删除和编辑这个权限组" -> strict interpretation: cannot edit at all.
            // But maybe we want to allow updating name? No, "Super Admin" should be fixed.
            return json(['code' => 403, 'msg' => '超级管理员权限组不可编辑']);
        }

        $group = AuthGroup::find($id);
        if (!$group) {
            return json(['code' => 404, 'msg' => '权限组不存在']);
        }

        $data = Request::only(['name', 'description', 'rules', 'status']);
        
        if (isset($data['name']) && $data['name'] !== $group->name) {
             $exists = AuthGroup::where('name', $data['name'])->where('id', '<>', $id)->find();
             if ($exists) {
                 return json(['code' => 400, 'msg' => '权限组名称已存在']);
             }
        }

        $group->save($data);

        // 清理该权限组下管理员的菜单缓存，避免权限更新后仍使用旧菜单
        $adminIds = Db::name('admin_users')->where('group_id', $id)->column('id');
        foreach ($adminIds as $adminId) {
            Cache::delete('menus_admin_' . $adminId);
        }

        return json(['code' => 200, 'msg' => '更新成功']);
    }

    /**
     * Delete role
     */
    public function delete(): Json
    {
        if (!$this->getAdminInfo()) {
            return json(['code' => 401, 'msg' => 'Unauthorized']);
        }

        $id = Request::param('id');
        if (empty($id)) {
            return json(['code' => 400, 'msg' => 'ID不能为空']);
        }

        if ($id == 1) {
            return json(['code' => 403, 'msg' => '超级管理员权限组不可删除']);
        }

        // Check if users exist
        $userCount = Db::name('admin_users')->where('group_id', $id)->count();
        if ($userCount > 0) {
             return json(['code' => 400, 'msg' => '该权限组下还有管理员，无法删除']);
        }

        AuthGroup::destroy($id);

        return json(['code' => 200, 'msg' => '删除成功']);
    }
}
