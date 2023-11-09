<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/24 14:25
 */

namespace App\Http\Controllers\Api;

use App\Admin\Actions\User\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Base64 图片上传
     * @return \Illuminate\Http\JsonResponse
     */
    public function image_base64()
    {
        $data = request()->post('data');
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $result)) {
            $type = $result[2];
            if (in_array($type, array('jpeg', 'jpg', 'gif', 'png'))) {
                $file_name = auth('api')->id().'_'.Str::random().'.'.$type;
                $dir_path = 'images/'.date('Ym').'/'.date('d').'/';
                $file_path = public_path('uploads') .'/'. $dir_path;
                if (!is_dir($file_path)){
                    mkdir($file_path,0777,true);
                }
                $url_path = config('filesystems.disks.common.url').'/'.$dir_path.$file_name;
                $file_path .= $file_name;
                if (file_put_contents($file_path, base64_decode(str_replace($result[1], '', $data)))) {
                    \Log::info($file_path);
                    $tempFile = fopen($file_path, "rb");
                    $bin = fread($tempFile, 2); //只读2字节
                    fclose($tempFile);
                    $strInfo = unpack("C2chars", $bin);
                    $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
                    $fileType = '';
                    switch ($typeCode) { // 6677:bmp 255216:jpg 7173:gif 13780:png 7790:exe 8297:rar 8075:zip tar:109121 7z:55122 gz 31139
                        case '255216':
                            $fileType = 'jpg';
                            break;
                        case '7173':
                            $fileType = 'gif';
                            break;
                        case '13780':
                            $fileType = 'png';
                            break;
                        default:
                            $fileType = 'unknown';
                    }
                    $fileSize = filesize($file_path);
                    if ($fileSize > 512 * 1024){
                        unlink($file_path);
                        return self::apiJson(500,'图片超出最大限制，最大支持512KB的图像文件。');
                    }
                    
                    if (in_array($fileType, ['jpg', 'gif', 'png'])){
                        return self::apiJson(200,'ok',[
                            'url' =>  $url_path
                        ]);
                    } else {
                        unlink($file_path);
                        return self::apiJson(500,'只支持jpg, gif, png 格式的图片文件！');
                    }

                } else {
                    return self::apiJson(500,'上传失败！');
                }
            } else {
                return self::apiJson(500,'图片上传类型错误！');
            }
        } else {
            return self::apiJson(500,'上传失败，数据验证失败！');
        }
    }
}
