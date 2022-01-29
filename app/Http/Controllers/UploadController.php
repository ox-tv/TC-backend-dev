<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class UploadController extends Controller
{

    public function uploadToLocal(Request $request){

        $request->validate([
            'file' => [
                'required',
                'file',
                'image',
                ],
        ]);

        try{
            $uploadedFile = Storage::disk('public')->put('/', $request->file('file'));

            return response()->json([
                'file' => $uploadedFile
            ]);

        }catch (Exception $e){
            return response()->json([
                'message'=> $e->getMessage()
            ], 500);
        }
    }


    public function UploadToS3(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                //'image',
            ],
        ]);

        try{
            $imageManager = new ImageManager();
            $s3 = Storage::disk('s3');

            $directory = 'files';
            $file = $request->file('file');
            $isImage = false;

            if(substr($file->getMimeType(), 0, 5) == 'image') {
                $isImage = true;
            }

            $originalFilePath = $s3->putFile($directory, $file, 'public');
            $url = $s3->url($originalFilePath);

            $urls = [];
            $urls['original'] = $url;

            // Create multiple sizes
            if ($isImage){
                $sizes = [
                    ['w' => 300, 'h' => 300],
                    ['w' => null, 'h' => 120],
                    ['w' => 2000, 'h' => null],
                ];

                $originalImage = $imageManager->make($url);
                $fileName = pathinfo($url, PATHINFO_BASENAME);

                foreach ($sizes as $size){
                    $image = clone $originalImage;
                    $key = ($size['w']?:'auto') . '_' . ($size['h']?:'auto');
                    $filePath = $directory . "/{$key}/" . $fileName;

                    //$this->testCrop($image);
                    $image->resize($size['w'], $size['h'], function ($constraint) use ($size) {
                        if (empty($size['w']) || empty($size['h'])){
                            $constraint->aspectRatio();
                        }
                    });

                    $s3->put($filePath, $image->stream(), 'public');

                    $urls[$key] = $s3->url($filePath);
                }
            }

            return response()->json([
                'file' => $url,
                'all' => $urls
            ]);

        }catch (Exception $e){
            return response()->json([
                'message'=> $e->getMessage()
            ], 500);
        }
    }

}
