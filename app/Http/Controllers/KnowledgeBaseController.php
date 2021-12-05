<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KnowledgeBaseController extends Controller
{
    // Returns the folders
    public function getFolders(Request $request)
    {
        $folder_parent_id = $request->input('folder_parent_id');

        if ($folder_parent_id == null) {
            $folder_parent_id = 0;
        }

        // Create activity
        DB::table('knowledge_base_folder_activity')->insert([
            'knowledge_base_folder_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
            'knowledge_base_folder_activity_folder_id' => $folder_parent_id,
            'knowledge_base_folder_activity_action' => 'open_folder',
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        $folders = DB::table('knowledge_base_folders')
            ->where('knowledge_base_folder_parent_id', $folder_parent_id)
            ->get();

        return response()->json($folders);
    }
    
    // Returns the files
    public function getFiles(Request $request)
    {
        $folder_parent_id = $request->input('folder_parent_id');

        if ($folder_parent_id == null) {
            $folder_parent_id = 0;
        }

        $folders = DB::table('knowledge_base_files')
            ->where('knowledge_base_file_folder_id', $folder_parent_id)
            ->orderBy('knowledge_base_file_name', 'asc')
            ->get();

        return response()->json($folders);
    }


    /*
        Directly readable file formats:
            - JPG/JPEG
            - PNG
            - GIF
            - TIFF
            - PDF
            - TXT
            - MARKDOWN
            - HTML
        Every other file format will be directly downloaded
    */
    public function isFileReadable($file_extension) {
        $readable_file_formats = array(
            'pdf',
            'txt',
            'md',
            'html'
        );

        if (in_array($file_extension, $readable_file_formats)) {
            return true;
        } else {
            return false;
        }
    }

    // Check Parent Folder Permissions
    public function checkParentFolderPermissions($folder_id, $parentIsNull = false) {

        $folder_permissions = DB::table('knowledge_base_permissions')
            ->where('knowledge_base_permission_folder_id', $folder_id)
            ->where(function ($query) {
                $query->where('knowledge_base_permission_user_id', JWTAuth::parseToken()->authenticate()->id)
                ->orWhere('knowledge_base_permission_user_id', 0);
            })
            ->first();
            
        if ($folder_permissions == null || $parentIsNull == true) {
            return array(
                'write' => false,
                'delete' => false,
                'modify' => false
            );
        } else {
            
            $writePermission = false;
            $deletePermission = false;
            $modifyPermission = false;

            if ($folder_permissions->knowledge_base_permission_write == 1) {
                $writePermission = true;
            }

            if ($folder_permissions->knowledge_base_permission_delete == 1) {
                $deletePermission = true;
            }

            if ($folder_permissions->knowledge_base_permission_modify == 1) {
                $modifyPermission = true;
            }

            return array(
                'write' => $writePermission,
                'delete' => $deletePermission,
                'modify' => $modifyPermission
            );

        }

    }

    // Gets file
    public function getFile(Request $request)
    {
        $file_id = $request->input('file_id');
        $folder_id = $request->input('folder_id');

        if ($file_id != null) {
            $file = DB::table('knowledge_base_files')
                ->where('id', $file_id)
                ->first();
    
            // Create activity
            DB::table('knowledge_base_file_activity')->insert([
                'knowledge_base_file_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
                'knowledge_base_file_activity_file_id' => $file_id,
                'knowledge_base_file_activity_action' => 'get_file',
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);
    
            $file->file_readable = $this->isFileReadable($file->knowledge_base_file_extension);
    
            $file_permissions = $this->checkParentFolderPermissions($file->knowledge_base_file_folder_id);

            return response()->json($file);
        } else {
            $file = DB::table('knowledge_base_files')
                ->where('knowledge_base_file_folder_id', $folder_id)
                ->where('knowledge_base_file_slug', 'index')
                ->first();

            // Create activity
            DB::table('knowledge_base_file_activity')->insert([
                'knowledge_base_file_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
                'knowledge_base_file_activity_file_id' => $file->id,
                'knowledge_base_file_activity_action' => 'get_folder_index',
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);

            $file->file_readable = $this->isFileReadable($file->knowledge_base_file_extension);

            $file_permissions = $this->checkParentFolderPermissions($file->knowledge_base_file_folder_id);

            return response()->json($file);
        }
    }

    // Gets folder
    public function getFolder(Request $request)
    {
        $folder_id = $request->input('folder_id');

        if ($folder_id == 0) {
            $folder = array("permissions" => $this->checkParentFolderPermissions($folder_id));
        } else {
            $folder = DB::table('knowledge_base_folders')
                ->where('id', $folder_id)
                ->first();
    
            $folder->permissions = $this->checkParentFolderPermissions($folder_id);
        }

        return response()->json($folder);
    }

    // Reads file
    public function readFile(Request $request)
    {
        $file_id = $request->input('file_id');
        $folder_id = $request->input('folder_id');

        if ($file_id != null) {
            $file = DB::table('knowledge_base_files')
                ->where('id', $file_id)
                ->first();
    
            $path = resource_path() . '/knowledge-base-data/' . $file->knowledge_base_file_path;
            
            // Create activity
            DB::table('knowledge_base_file_activity')->insert([
                'knowledge_base_file_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
                'knowledge_base_file_activity_file_id' => $file_id,
                'knowledge_base_file_activity_action' => 'read_file',
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);
    
            $file->file_readable = $this->isFileReadable($file->knowledge_base_file_extension);
    
            $file_permissions = $this->checkParentFolderPermissions($file->knowledge_base_file_folder_id);
    
    
            if ($this->isFileReadable($file->knowledge_base_file_extension)) {
                $file = File::get($path);
                $type = File::mimeType($path);    
                $response = Response::make($file, 200);
                $response->header("Content-Type", $type);
                return $response;
            } else {
    
                $file = File::get($path);
                $type = File::mimeType($path);    
                $response = Response::make($file, 200);
                $response->header("Content-Type", $type)
                    ->header("Content-Disposition", "inline")
                    ->header("filename", $path)
                    ->header("Content-Transfer-Encoding", "binary");
                return $response;
    
            }
        } else {
            $file = DB::table('knowledge_base_files')
                ->where('knowledge_base_file_folder_id', $folder_id)
                ->where('knowledge_base_file_slug', 'index')
                ->first();
    
            $path = resource_path() . '/knowledge-base-data/' . $file->knowledge_base_file_path;
            
            // Create activity
            DB::table('knowledge_base_file_activity')->insert([
                'knowledge_base_file_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
                'knowledge_base_file_activity_file_id' => $file->id,
                'knowledge_base_file_activity_action' => 'read_folder_index',
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);
    
            $file->file_readable = $this->isFileReadable($file->knowledge_base_file_extension);
    
            $file_permissions = $this->checkParentFolderPermissions($file->knowledge_base_file_folder_id);
    
            $file = File::get($path);
            $type = File::mimeType($path);    
            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);
            return $response;
        }

    }

    // Create Folder
    public function createFolder(Request $request)
    {
        $folder_name = $request->input('folder_name');
        $folder_parent_id = $request->input('folder_parent_id');

        $folder = DB::table('knowledge_base_folders')
            ->where('knowledge_base_folder_name', $folder_name)
            ->where('knowledge_base_folder_parent_id', $folder_parent_id)
            ->first();

        if ($folder != null) {
            $folder_name = $folder_name . "-1";
        }

        $slug = str_slug($folder_name);

        // Check if slug is already in use
        $folder = DB::table('knowledge_base_folders')
            ->where('knowledge_base_folder_slug', $slug)
            ->first();
        if ($folder != null) {
            $slug = $slug . '-' . $folder_parent_id;
        }

        // Create Folder
        $folder = DB::table('knowledge_base_folders')
            ->insert([
                'knowledge_base_folder_name' => $folder_name,
                'knowledge_base_folder_slug' => $slug,
                'knowledge_base_folder_parent_id' => $folder_parent_id,
                'knowledge_base_folder_user_id' => JWTAuth::parseToken()->authenticate()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'knowledge_base_folder_status' => 1
            ]);

        // Get Folder ID
        $folder_id = DB::getPdo()->lastInsertId();

        // Permissions
        DB::table('knowledge_base_permissions')->insert([
            'knowledge_base_permission_user_id' => 0, // All Users
            'knowledge_base_permission_folder_id' => $folder_id,
            'knowledge_base_permission_write' => 1,
            'knowledge_base_permission_delete' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create activity
        DB::table('knowledge_base_folder_activity')->insert([
            'knowledge_base_folder_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
            'knowledge_base_folder_activity_folder_id' => $folder_id,
            'knowledge_base_folder_activity_action' => 'create_folder',
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        // Response
        return response()->json(array(
            'success' => true,
            'folder_id' => $folder_id
        ));
    }
}
