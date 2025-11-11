<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Upload;
use App\Jobs\ProcessCsvUpload;
use App\Http\Resources\UploadResource;

class UploadController extends Controller
{
    public function index()
    {
        return view('uploads', [
            'uploads' => Upload::latest('id')->take(50)->get(),
        ]);
    }
    
    public function list()
    {
        $uploads = Upload::latest('id')->take(50)->get();
        return UploadResource::collection($uploads);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);
        
        $file = $validated['file'];
        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs('uploads', Str::uuid() . '_' . $originalName);
        $absolutePath = Storage::path($path);
        $checksum = hash_file('sha256', $absolutePath);
        
        $upload = Upload::create([
            'original_name' => $originalName,
            'storage_path' => $path,
            'checksum' => $checksum,
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
            'failed_rows' => 0,
        ]);
        
        dispatch(new ProcessCsvUpload($upload->id));
        
        return redirect()->back()->with('status', 'File queued for processing.');
    }
}
