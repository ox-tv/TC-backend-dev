<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{

    public function upload(Request $request){

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,png,mov,mp4,mkv',
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

}
