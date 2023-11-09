<?php


namespace App\Http\Controllers\Api;

use App\Article;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * 文章
 * Class ArticleController
 * @package App\Http\Controllers\Api
 * @author 春风 <860646000@qq.com>
 */
class ArticleController extends Controller
{
    /**
     * ArticleController constructor.
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['index', 'detail']]);
    }

    /**
     * 文章列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = Article::select(['id', 'title'])
            ->where('type', 1)
            ->get()
            ->toArray();
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 文章详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make(request()->input(), [
            'id' => ['required', 'integer', 'min:1'],
        ], [
            'id.required' => '缺少参数:文章Id',
            'id.integer' => '文章Id错误',
            'id.min' => '文章Id错误'
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }
        $id = request()->get('id');
        $article = Article::query()->find($id);
        if (!$article) {
            return self::apiJson(500, '文章Id错误');
        }
        return self::apiJson(200, 'ok', $article);
    }
}
