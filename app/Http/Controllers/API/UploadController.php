<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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
        $files = $request->allFiles();

        if (empty($files)) {
            return response()->json([
                'status' => false,
                'message' => 'Error uploaded files',
            ]);
        }

        $url = [];
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
            'message' => __('message.file_uploaded'),
            'url' => $url,
        ]);
    }
}
