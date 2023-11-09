<?php

namespace App\Admin\Actions\User;

use App\UserTag;
use App\UserTagList;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;


class SetTag extends BatchAction
{
    public $name = '设置标签';

    public function handle(Collection $collection)
    {
        $tags = request()->post('tags');
        $tagIds = [];
        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $tagIds[] = $tag;
            }
        }
        if (empty($tagIds)) {
            return $this->response()->error('请选择标签');
        }
        foreach ($collection as $model) {
            //删除用户就Tag
            UserTag::where('user_id', $model->id)->delete();
            //添加新标签
            foreach ($tagIds as $tagId) {
                $tagModel = new UserTag();
                $tagModel->user_id = $model->id;
                $tagModel->tag_id = $tagId;
                $tagModel->save();
            }
        }
        return $this->response()->success('操作成功！')->refresh();
    }

    public function form()
    {
        $this->multipleSelect('tags', '标签')->options(UserTagList::all()->pluck('name', 'id'))->required();
    }
}
