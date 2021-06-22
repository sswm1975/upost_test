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
        $upload_dir = public_path(env('APP_UPLOAD_FOLDER', '/content/files/'));
        $files = $request->allFiles();
        /** @var $file UploadedFile $file */
        foreach ($files['images'] as $file) {
            $ext = $file->getExtension();
            echo $ext;die;
            if(!in_array($ext, ['jpg', 'png', 'gif', 'mp4', 'avi', 'mov'])) {
                throw new ErrorException('File format is not supported');
            }

            $url[] = env('APP_UPLOAD_FOLDER', '/content/files/') . $file->getFilename();
        }

        return response()->json([
            'status' => true,
            'url' => $url,
        ]);
    }
}
