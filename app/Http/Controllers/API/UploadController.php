<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Загрузить файл.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function upload(Request $request):JsonResponse
    {
        $url = [];
        $files = $request->allFiles();

        if($files) {
            /** @var $file UploadedFile $file */
            foreach ($files['files'] as $file) {
                $ext = $file->getClientOriginalExtension();
                if(!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'])) {
                    throw new ErrorException('File format is not supported (' . $file->getClientOriginalName() . ')');
                }
                $file->move(public_path(env('APP_UPLOAD_FOLDER', '/content/files/')), $file->getClientOriginalName());
                $url[] = env('APP_UPLOAD_FOLDER', '/content/files/') . $file->getClientOriginalName();
            }

            return response()->json([
                'status' => true,
                'url' => $url,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Error uploaded files',
            ]);
        }


    }
}
