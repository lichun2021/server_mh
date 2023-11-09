<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use Encore\Admin\Controllers\AdminController;

/**
 * Class FileController
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/2/26
 * Time：13:46
 */
class FileController extends AdminController
{
    public function image(Request $request)
    {
        $fileFormat = $request->file('file')->getClientOriginalExtension();
        $PhotoValidFormat = array('jpg', 'png', 'gif', 'jpeg', 'bmp');

        if (in_array(strtolower($fileFormat), $PhotoValidFormat) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $PhotoName = uniqid() . '.' . $request->file('file')->getClientOriginalExtension();
            $fileSize = number_format($_FILES['file']['size'] / 1048576, 2);
            if ($fileSize <= 50) {
                if ($request->file('file')->move(public_path('uploads/images/articles'), $PhotoName)) {
                    return json_encode([
                        'location' => config('filesystems.disks.common.url').'/images/articles/' . $PhotoName
                    ]);
                } else {
                    $res = -1;
                }
            }
            else {
                if (isset($_FILES['file']['error']) && $_FILES['file']['error'] == 1)
                    $res = -1;
                else
                    $res = 0;
            }
            return json_encode([
                'res' => $res
            ]);
        }
    }
}
