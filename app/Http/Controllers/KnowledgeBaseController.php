<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\RequestException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse\header;

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

        $user_id = JWTAuth::parseToken()->authenticate()->id;
        $folders = DB::table('knowledge_base_folders')
            ->join('knowledge_base_permissions', 'knowledge_base_folders.id', '=', 'knowledge_base_permissions.knowledge_base_permission_folder_id')
            ->select(
                'knowledge_base_folders.*',
                'knowledge_base_permissions.knowledge_base_permission_user_id',
                'knowledge_base_permissions.knowledge_base_permission_folder_id',
                'knowledge_base_permissions.knowledge_base_permission_read'
            )
            ->where('knowledge_base_folders.knowledge_base_folder_parent_id', $folder_parent_id)
            ->where('knowledge_base_permissions.knowledge_base_permission_read', 1)
            ->where(function ($query) use ($user_id) {
                $query->where('knowledge_base_permissions.knowledge_base_permission_user_id', $user_id)
                    ->orWhere('knowledge_base_permissions.knowledge_base_permission_user_id', 0);
            })
            ->groupBy('knowledge_base_folders.id')
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
            ->where('knowledge_base_file_status', 1)
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
            - TXT
            - MARKDOWN
            - HTML
        Every other file format will be directly downloaded
    */
    public function isFileReadable($file_extension) {
        $readable_file_formats = array(
            'txt',
            'md',
            'html',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'tiff'
        );

        if (in_array($file_extension, $readable_file_formats)) {
            return true;
        } else {
            return false;
        }
    }

    // Check Parent Folder Permissions
    public function checkParentFolderPermissions($folder_id, $parentIsNull = false) {

        // Check if user is admin
        $user_id = JWTAuth::parseToken()->authenticate()->id;
        $user_is_admin = DB::table('users')
            ->where('id', $user_id)
            ->where('is_admin', 1)
            ->count();

        if ($user_is_admin == 1) {
            return array(
                'read' => true,
                'write' => true,
                'delete' => true,
                'modify' => true
            );
        } else {
            $folder_permissions = DB::table('knowledge_base_permissions')
                ->where('knowledge_base_permission_folder_id', $folder_id)
                ->where(function ($query) {
                    $query->where('knowledge_base_permission_user_id', 0)
                    ->orWhere('knowledge_base_permission_user_id', JWTAuth::parseToken()->authenticate()->id);
                })
                ->orderBy('knowledge_base_permission_user_id', 'desc')
                ->first();

            if ($folder_permissions == null || $parentIsNull == true) {
                return array(
                    'read' => false,
                    'write' => false,
                    'delete' => false,
                    'modify' => false
                );
            } else {

                $readPermission = false;
                $writePermission = false;
                $deletePermission = false;
                $modifyPermission = false;

                if ($folder_permissions->knowledge_base_permission_read == 1) {
                    $readPermission = true;
                }

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
                    'read' => $readPermission,
                    'write' => $writePermission,
                    'delete' => $deletePermission,
                    'modify' => $modifyPermission
                );
            }
        }


    }

    // Gets file
    public function getFile(Request $request)
    {
        $file_id = $request->input('file_id');
        $folder_id = $request->input('folder_id');
        $isHistory = false;

        if (starts_with($file_id, "history_")) {
            $isHistory = true;
            $file_id = str_replace("history_", "", $file_id);
        }

        if ($file_id != null) {

            if (!$isHistory) {
                $file = DB::table('knowledge_base_files')
                    ->where('id', $file_id)
                    ->first();
            } else {
                $history = DB::table('knowledge_base_file_history')
                    ->where('id', $file_id)
                    ->first();

                $file = DB::table('knowledge_base_files')
                    ->where('id', $history->knowledge_base_file_history_id)
                    ->first();
            }

            if ($file == null) {
                return response()->json(array(
                    'error' => 'File not found'
                ));
            }


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

            $file->permissions = $file_permissions;

            return response()->json($file);
        } else {
            $file = DB::table('knowledge_base_files')
                ->where('knowledge_base_file_folder_id', $folder_id)
                ->where('knowledge_base_file_slug', 'index')
                ->first();

            if ($file == null) {
                return response()->json(array(
                    'error' => 'Index not found'
                ));
            }

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

            if ($folder == null) {
                return response()->json(array(
                    'error' => 'Folder not found'
                ));
            }

            $folder->permissions = $this->checkParentFolderPermissions($folder_id);
        }

        return response()->json($folder);
    }

    // Creates download-token
    public function createDownloadToken(Request $request) {
        $user_id = JWTAuth::parseToken()->authenticate()->id;
        $file_id = $request->input('file_id');

        $token = str_random(20) . time() . $user_id . $file_id;

        // Get File
        $file = DB::table('knowledge_base_files')
            ->where('id', $file_id)
            ->first();

        $file_permissions = $this->checkParentFolderPermissions($file->knowledge_base_file_folder_id);

        if (!$file_permissions["read"]) {
            return response()->json(array(
                'error' => 'You do not have permission to read this file'
            ));
        }

        DB::table('knowledge_base_file_downloads')->insert([
            'knowledge_base_file_download_user_id' => $user_id,
            'knowledge_base_file_download_file_id' => $file_id,
            'knowledge_base_file_download_token' => $token,
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        return response()->json(array(
            'token' => $token
        ));
    }

    // Reads file
    public function readFile(Request $request)
    {
        $file_id = $request->input('file_id');
        $folder_id = $request->input('folder_id');
        $isHistory = false;

        if (starts_with($file_id, "history_")) {
            $isHistory = true;
            $file_id = str_replace("history_", "", $file_id);
        }


        if ($file_id != null) {

            $file = null;

            if (!$isHistory) {
                $file = DB::table('knowledge_base_files')
                    ->where('id', $file_id)
                    ->first();

                $path = resource_path() . '/knowledge-base-data/' . $file->knowledge_base_file_path;
            } else {

                $history = DB::table('knowledge_base_file_history')
                    ->where('id', $file_id)
                    ->first();

                $file = DB::table('knowledge_base_files')
                    ->where('id', $history->knowledge_base_file_history_id)
                    ->where('knowledge_base_file_status', 1)
                    ->first();

                $path = resource_path() . '/knowledge-base-data/' . $history->knowledge_base_file_history_path;
            }


            $file->file_readable = $this->isFileReadable($file->knowledge_base_file_extension);

            if ($this->isFileReadable($file->knowledge_base_file_extension)) {

                // Create activity
                DB::table('knowledge_base_file_activity')->insert([
                    'knowledge_base_file_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
                    'knowledge_base_file_activity_file_id' => $file_id,
                    'knowledge_base_file_activity_action' => 'read_file',
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);

                $file_permissions = $this->checkParentFolderPermissions($file->knowledge_base_file_folder_id);

                if (!$file_permissions["read"]) {
                    return response()->json(array(
                        'error' => 'You do not have permission to read this file'
                    ));
                }

                $imageTypes = array(
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'tiff'
                );

                if (in_array($file->knowledge_base_file_extension, $imageTypes)) {
                    $fileObject = File::get($path);
                    $imageEncoded = base64_encode($fileObject);
                    $base64Str = 'data:image/' . $file->knowledge_base_file_extension . ';base64,' . $imageEncoded;
                    return $base64Str;
                } else {
                    $file = File::get($path);
                    $type = File::mimeType($path);
                    $response = Response::make($file, 200);
                    $response->header("Content-Type", $type);
                    return $response;
                }

            } else {

                // Get Token
                $token = $request->input('token');

                // Check if token is valid
                $token_valid = DB::table('knowledge_base_file_downloads')
                    ->where('knowledge_base_file_download_token', $token)
                    ->where('knowledge_base_file_download_file_id', $file_id)
                    ->where('knowledge_base_file_download_status', 0)
                    ->first();

                if ($token_valid == null) {
                    return response()->json(array(
                        'error' => 'Invalid token'
                    ));
                }

                header("Content-disposition: attachment; filename=" . $file->knowledge_base_file_name . '.' . $file->knowledge_base_file_extension);
                header("Content-type: " . mime_content_type($path));

                // Set download as read
                DB::table('knowledge_base_file_downloads')
                    ->where('knowledge_base_file_download_token', $token)
                    ->where('knowledge_base_file_download_file_id', $file_id)
                    ->update([
                        'knowledge_base_file_download_time' => date('Y-m-d H:i:s'),
                        'knowledge_base_file_download_status' => 1
                    ]);

                // Create activity
                DB::table('knowledge_base_file_activity')->insert([
                    'knowledge_base_file_activity_user_id' => $token_valid->knowledge_base_file_download_user_id,
                    'knowledge_base_file_activity_file_id' => $file_id,
                    'knowledge_base_file_activity_action' => 'download_file',
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);

                return readfile($path);

            }
        } else {
            $file = DB::table('knowledge_base_files')
                ->where('knowledge_base_file_folder_id', $folder_id)
                ->where('knowledge_base_file_slug', 'index')
                ->first();

            if ($file == null) {
                return response()->json(array(
                    'error' => 'Index not found'
                ));
            }

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

            $imageTypes = array(
                'jpg',
                'jpeg',
                'png',
                'gif',
                'tiff'
            );

            if (in_array($file->knowledge_base_file_extension, $imageTypes)) {
                $fileObject = File::get($path);
                $imageEncoded = base64_encode($fileObject);
                $base64Str = 'data:image/' . $file->knowledge_base_file_extension . ';base64,' . $imageEncoded;
                return $base64Str;
            } else {
                $file = File::get($path);
                $type = File::mimeType($path);
                $response = Response::make($file, 200);
                $response->header("Content-Type", $type);
                return $response;
            }

        }

    }

    // Create Folder
    public function createFolder(Request $request)
    {
        $folder_name = trim($request->input('folder_name'));
        $folder_parent_id = trim($request->input('folder_parent_id'));
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        if ($folder_name == '') {
            return response()->json(array(
                'success' => false,
                'error' => 'The Folder name cannot be empty. Please enter a folder name.'
            ));
        }

        $folder = DB::table('knowledge_base_folders')
            ->where('knowledge_base_folder_name', $folder_name)
            ->where('knowledge_base_folder_parent_id', $folder_parent_id)
            ->first();

        $folderNameIsNotAvailable = true;
        $folderNameExtension = 1;

        while ($folderNameIsNotAvailable) {
            $folder = DB::table('knowledge_base_folders')
                ->where('knowledge_base_folder_name', $folder_name)
                ->where('knowledge_base_folder_parent_id', $folder_parent_id)
                ->count();

            if ($folder > 0) {
                $folder_name = $folder_name . '-' . $folderNameExtension;
            } else {
                $folderNameIsNotAvailable = false;
            }

            $folderNameExtension++;
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
            'knowledge_base_permission_user_id' => ($request->input('create_permission') == "true" ? 0 : $user_id), // All Users
            'knowledge_base_permission_folder_id' => $folder_id,
            'knowledge_base_permission_read' => 1,
            'knowledge_base_permission_write' => 1,
            'knowledge_base_permission_delete' => 1,
            'knowledge_base_permission_modify' => 1,
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

    // Modify Folder
    public function modifyFolder(Request $request) {
        $folder_id = trim($request->input('folder_id'));
        $folder_name = trim($request->input('folder_name'));
        $folder_description = trim($request->input('folder_description'));

        if ($folder_name == '') {
            return response()->json(array(
                'success' => false,
                'folder_id' => $folder_id,
                'error' => 'The Folder name cannot be empty. Please enter a folder name.'
            ));
        }

        // Check if folder name already exists in this parent
        $folder = DB::table('knowledge_base_folders')
            ->where('id', $folder_id)
            ->first();

        if ($folder->knowledge_base_folder_name !== $folder_name) {
            $existingFolder = DB::table('knowledge_base_folders')
                ->where('knowledge_base_folder_name', $folder_name)
                ->where('knowledge_base_folder_parent_id', $folder->knowledge_base_folder_parent_id)
                ->count();
    
            if ($existingFolder > 0) {
                return response()->json(array(
                    'success' => false,
                    'error' => 'The Folder name already exists. Please enter a different folder name.'
                ));
            }
        }


        DB::table('knowledge_base_folders')
            ->where('id', $folder_id)
            ->update([
                'knowledge_base_folder_name' => $folder_name,
                'knowledge_base_folder_description' => $folder_description,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Response
        return response()->json(array(
            'success' => true,
            'folder_id' => $folder_id
        ));
    }

    // Save File
    public function saveFile(Request $request) {
        $file_id = $request->input('file_id');
        $file_content = $request->input('file_content');

        // Get File Details
        $file = DB::table('knowledge_base_files')
            ->where('id', $file_id)
            ->first();

        // Check if file is editable by file-editor
        $file_extension = $file->knowledge_base_file_extension;
        $file_editable = $this->isFileReadable($file_extension);

        if ($file_editable) {

            $time = time();

            // Save File
            $path = resource_path() . '/knowledge-base-data/' . $file->knowledge_base_file_path;
            File::copy($path, resource_path() . '/knowledge-base-data/modified_' . $time . '_' . $file->knowledge_base_file_path);
            if (File::put($path, $file_content)) {

                // Create Activity
                DB::table('knowledge_base_file_activity')->insert([
                    'knowledge_base_file_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
                    'knowledge_base_file_activity_file_id' => $file_id,
                    'knowledge_base_file_activity_action' => 'save_file',
                    "created_at" => date('Y-m-d H:i:s', $time),
                    "updated_at" => date('Y-m-d H:i:s', $time),
                ]);

                // Create File-History
                DB::table('knowledge_base_file_history')->insert([
                    'knowledge_base_file_history_user_id' => JWTAuth::parseToken()->authenticate()->id,
                    'knowledge_base_file_history_id' => $file_id,
                    'knowledge_base_file_history_path' => 'modified_' . $time . '_' . $file->knowledge_base_file_path,
                    "created_at" => date('Y-m-d H:i:s', $time),
                    "updated_at" => date('Y-m-d H:i:s', $time),
                ]);

                // Update File Last Activity
                DB::table('knowledge_base_files')
                    ->where('id', $file_id)
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s', $time),
                    ]);

                // Response
                return response()->json(array(
                    'success' => true,
                    'file_id' => $file_id
                ));
            } else {
                // Response
                return response()->json(array(
                    'success' => false,
                    'error' => 'There was an error saving the file. Please try again later.',
                    'file_id' => $file_id
                ));
            }
        }
    }

    // Modify File
    public function modifyFile(Request $request) {
        $user_id = JWTAuth::parseToken()->authenticate()->id;
        $time = time();
        $file_id = trim($request->input('file_id'));
        $file_name = trim($request->input('file_name'));
        $file_description = trim($request->input('file_description'));
        $file_slug = str_slug($file_name);
        $modify_file = $request->input('modify_file');

        if ($file_name == '') {
            return response()->json(array(
                'success' => false,
                'file_id' => $file_id,
                'error' => 'The File name cannot be empty. Please enter a file name.'
            ));
        }

        // Check if file name already exists in this parent
        $file = DB::table('knowledge_base_files')
            ->where('id', $file_id)
            ->first();
        
        $existingFile = DB::table('knowledge_base_files')
            ->where('knowledge_base_file_slug', $file_slug)
            ->where('knowledge_base_file_folder_id', $file->knowledge_base_file_folder_id)
            ->count();
        
        if ($existingFile > 0) {
            if ($file_name != $file->knowledge_base_file_name) {
                return response()->json(array(
                    'success' => false,
                    'file_id' => $file_id,
                    'error' => 'The File name already exists. Please enter a different file name.'
                ));
            }
        }

        // Get File Details
        $file = DB::table('knowledge_base_files')
            ->where('id', $file_id)
            ->first();

        // Update File Details
        DB::table('knowledge_base_files')
            ->where('id', $file_id)
            ->update([
                'knowledge_base_file_name' => $file_name,
                'knowledge_base_file_slug' => $file_slug,
                'knowledge_base_file_description' => $file_description,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Upload new file version if modified
        if ($modify_file == "true") {
            if ($_FILES["modified_file"]) {
                $upload_file_name = $_FILES["modified_file"]["name"];
                $upload_file_tmp_name = $_FILES["modified_file"]["tmp_name"];
                $upload_file_ext = explode(".", $upload_file_name);
                $upload_file_ext = strtolower(end($upload_file_ext));
                $error = $_FILES["modified_file"]["error"];

                if($error > 0){
                    // Response
                    return response()->json(array(
                        "status" => "error",
                        "error" => true,
                        "message" => "Error uploading the file!"
                    ));
                } else {

                    // Generate a new File Name
                    $generatedFileName = time() . '-' . preg_replace('/\s+/', '-', $upload_file_name);
                    $generatedFileName = "modified_" . $time . "_" . strtolower($generatedFileName);

                    if (move_uploaded_file($upload_file_tmp_name , resource_path() . "/knowledge-base-data/$generatedFileName")) {

                        // Create File-History
                        DB::table('knowledge_base_file_history')->insert([
                            'knowledge_base_file_history_user_id' => JWTAuth::parseToken()->authenticate()->id,
                            'knowledge_base_file_history_id' => $file_id,
                            'knowledge_base_file_history_path' => $file->knowledge_base_file_path,
                            "created_at" => date('Y-m-d H:i:s', $time),
                            "updated_at" => date('Y-m-d H:i:s', $time),
                        ]);

                        // Update File Details
                        DB::table('knowledge_base_files')
                            ->where('id', $file_id)
                            ->update([
                                'knowledge_base_file_path' => $generatedFileName,
                                'knowledge_base_file_extension' => $upload_file_ext,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);

                        // Create Activity
                        DB::table('knowledge_base_file_activity')->insert([
                            'knowledge_base_file_activity_user_id' => $user_id,
                            'knowledge_base_file_activity_file_id' => $file_id,
                            'knowledge_base_file_activity_action' => 'modify_file',
                            "created_at" => date('Y-m-d H:i:s'),
                            "updated_at" => date('Y-m-d H:i:s'),
                        ]);

                        // Response
                        return response()->json(array(
                            "status" => "success",
                            "error" => false,
                            "message" => ""
                        ));

                    } else {
                        // Response
                        return response()->json(array(
                            "status" => "success",
                            "error" => false,
                            "message" => ""
                        ));
                    }
                }
            }
        }

        // Response
        return response()->json(array(
            'success' => true,
            "message" => ""
        ));
    }

    // Get File History
    public function getFileHistory(Request $request) {
        $file_id = $request->input('file_id');

        // Get File History
        $file_history = DB::table('knowledge_base_file_history')
            ->where('knowledge_base_file_history_id', $file_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Loop through file history
        foreach ($file_history as $key => $value) {
            $file_history[$key]->user_name = DB::table('users')
                ->where('id', $value->knowledge_base_file_history_user_id)
                ->first()->name;
        }

        // Response
        return response()->json(array(
            'success' => true,
            'file_history' => $file_history
        ));
    }

    // Restore from history
    public function restoreFromHistory(Request $request) {
        $file_history_id = $request->input('file_history_id');

        // Get File History
        $file_history = DB::table('knowledge_base_file_history')
            ->where('id', $file_history_id)
            ->first();

        // Get File Details
        $file = DB::table('knowledge_base_files')
            ->where('id', $file_history->knowledge_base_file_history_id)
            ->first();

        // Create Activity
        DB::table('knowledge_base_file_activity')->insert([
            'knowledge_base_file_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
            'knowledge_base_file_activity_file_id' => $file->id,
            'knowledge_base_file_activity_action' => 'restore_from_history',
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        $time = time();

        // Copy the current file to a new file
        $path_1 = resource_path() . '/knowledge-base-data/' . $file->knowledge_base_file_path;
        File::copy($path_1, resource_path() . '/knowledge-base-data/modified_' . $time . '_' . $file->knowledge_base_file_path);

        // Copy the file history to the new file
        $path_2 = resource_path() . '/knowledge-base-data/' . $file_history->knowledge_base_file_history_path;
        File::copy($path_2, resource_path() . '/knowledge-base-data/restored_' . $time . '_' . $file_history->knowledge_base_file_history_path);

        // Create File-History
        DB::table('knowledge_base_file_history')->insert([
            'knowledge_base_file_history_user_id' => JWTAuth::parseToken()->authenticate()->id,
            'knowledge_base_file_history_id' => $file->id,
            'knowledge_base_file_history_path' => 'modified_' . $time . '_' . $file->knowledge_base_file_path,
            "created_at" => date('Y-m-d H:i:s', $time),
            "updated_at" => date('Y-m-d H:i:s', $time),
        ]);

        // Update File Last Activity
        DB::table('knowledge_base_files')
            ->where('id', $file->id)
            ->update([
                'knowledge_base_file_path' => 'restored_' . $time . '_' . $file_history->knowledge_base_file_history_path,
                'updated_at' => date('Y-m-d H:i:s', $time),
            ]);

        // Response
        return response()->json(array(
            'success' => true,
            'file_id' => $file->id
        ));
    }

    // Upload File
    public function uploadFile(Request $request) {
        $folder_id = $request->input('folder_id');
        $file_name = $request->input('file_name');
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        // Move File to Folder
        if ($_FILES['file']) {
            $upload_file_name = $_FILES["file"]["name"];
            $upload_file_tmp_name = $_FILES["file"]["tmp_name"];
            $upload_file_ext = explode(".", $upload_file_name);
            $upload_file_ext = strtolower(end($upload_file_ext));
            $error = $_FILES["file"]["error"];

            if ($file_name == "" || $file_name == null) {
                $file_name = $upload_file_name;
                // Remove File Extension
                $file_name = str_replace('.' . $upload_file_ext, '', $file_name);
            }

            if($error > 0){
                // Response
                $response = array(
                    'success' => false,
                    "error" => "Error uploading the file! Please try again. (Error Code: " . $error . ")"
                );
            } else {
                $generatedFileName = time() . '-' . preg_replace('/\s+/', '-', $upload_file_name);
                $generatedFileName = strtolower($generatedFileName);

                if (move_uploaded_file($upload_file_tmp_name , resource_path() . "/knowledge-base-data/$generatedFileName")) {
                    $fileNameIsNotAvailable = true;
                    $fileNameExtension = 1;

                    while ($fileNameIsNotAvailable) {
                        $fileSlug = DB::table('knowledge_base_files')
                            ->where('knowledge_base_file_name', $file_name)
                            ->where('knowledge_base_file_folder_id', $folder_id)
                            ->count();

                        if ($fileSlug > 0) {
                            $file_name = $file_name . '-' . $fileNameExtension;
                        } else {
                            $fileNameIsNotAvailable = false;
                        }

                        $fileNameExtension++;
                    }

                    $slug = str_slug($file_name);

                    // Create File
                    $file_id = DB::table('knowledge_base_files')->insertGetId([
                        'knowledge_base_file_name' => $file_name,
                        'knowledge_base_file_slug' => str_slug($file_name),
                        'knowledge_base_file_description' => '',
                        'knowledge_base_file_extension' => $upload_file_ext,
                        'knowledge_base_file_path' => $generatedFileName,
                        'knowledge_base_file_folder_id' => $folder_id,
                        'knowledge_base_file_user_id' => $user_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    // Create Activity
                    DB::table('knowledge_base_file_activity')->insert([
                        'knowledge_base_file_activity_user_id' => $user_id,
                        'knowledge_base_file_activity_file_id' => $file_id,
                        'knowledge_base_file_activity_action' => 'upload_file',
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);

                    // Response
                    $response = array(
                        'success' => true,
                        "message" => "File uploaded successfully!"
                    );

                } else {
                    // Response
                    $response = array(
                        'success' => false,
                        "error" => "Error uploading the file! Please try again."
                    );
                }
            }
        } else {
            // Response
            $response = array(
                'success' => false,
                "error" => "Error uploading the file! Please try again."
            );
        }

        return response()->json($response);
    }

    // Delete File
    public function deleteFile(Request $request) {
        // Check if the user has the delete permission
        $folder_id = $request->input('folder_id');
        $file_id = $request->input('file_id');
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        $file_permissions = $this->checkParentFolderPermissions($folder_id);

        if ($file_permissions["delete"]) {
            // Get File path
            $file = DB::table('knowledge_base_files')
                ->where('id', $file_id)
                ->first();

            // Delete File
            $path = resource_path() . '/knowledge-base-data/' . $file->knowledge_base_file_path;
            File::delete($path);
            DB::table('knowledge_base_files')->where('id', $file_id)->delete();

            // Create Activity
            DB::table('knowledge_base_file_activity')->insert([
                'knowledge_base_file_activity_user_id' => $user_id,
                'knowledge_base_file_activity_file_id' => $file_id,
                'knowledge_base_file_activity_action' => 'delete_file',
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);

            // Get File history
            $file_history = DB::table('knowledge_base_file_history')
                ->where('knowledge_base_file_history_id', $file_id)
                ->get();

            // Delete File history
            foreach ($file_history as $file_history_item) {
                $path = resource_path() . '/knowledge-base-data/' . $file_history_item->knowledge_base_file_history_path;
                File::delete($path);
                DB::table('knowledge_base_file_history')->where('id', $file_history_item->id)->delete();

                // Create Activity
                DB::table('knowledge_base_file_activity')->insert([
                    'knowledge_base_file_activity_user_id' => $user_id,
                    'knowledge_base_file_activity_file_id' => $file_id,
                    'knowledge_base_file_activity_action' => 'delete_file_history',
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);
            }

            // Delete File comments
            DB::table('knowledge_base_file_comments')->where('knowledge_base_file_comment_file_id', $file_id)->delete();

            // Create Activity
            DB::table('knowledge_base_file_activity')->insert([
                'knowledge_base_file_activity_user_id' => $user_id,
                'knowledge_base_file_activity_file_id' => $file_id,
                'knowledge_base_file_activity_action' => 'delete_file_comments',
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);

            return response()->json(array(
                "status" => "success",
                "error" => false,
                "message" => ""
            ));
        } else {
            return response()->json(array(
                "status" => "error",
                "error" => true,
                "message" => "You don't have the permission to delete this file!"
            ));
        }

    }

    // Delete Folder
    public function deleteFolder(Request $request) {
        // Check if the user has the delete permission
        $folder_id = $request->input('folder_id');
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        $file_permissions = $this->checkParentFolderPermissions($folder_id);

        if ($file_permissions["delete"]) {

            // Get File path
            $files = DB::table('knowledge_base_files')
                ->where('knowledge_base_file_folder_id', $folder_id)
                ->get();

            foreach ($files as $file) {
                // Delete File
                $path = resource_path() . '/knowledge-base-data/' . $file->knowledge_base_file_path;
                File::delete($path);
                DB::table('knowledge_base_files')->where('id', $file->id)->delete();

                // Create Activity
                DB::table('knowledge_base_file_activity')->insert([
                    'knowledge_base_file_activity_user_id' => $user_id,
                    'knowledge_base_file_activity_file_id' => $file->id,
                    'knowledge_base_file_activity_action' => 'delete_file',
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);

                // Get File history
                $file_history = DB::table('knowledge_base_file_history')
                    ->where('knowledge_base_file_history_id', $file->id)
                    ->get();

                    // Delete File history
                foreach ($file_history as $file_history_item) {
                    $path = resource_path() . '/knowledge-base-data/' . $file_history_item->knowledge_base_file_history_path;
                    File::delete($path);
                    DB::table('knowledge_base_file_history')->where('id', $file_history_item->id)->delete();

                    // Create Activity
                    DB::table('knowledge_base_file_activity')->insert([
                        'knowledge_base_file_activity_user_id' => $user_id,
                        'knowledge_base_file_activity_file_id' => $file->id,
                        'knowledge_base_file_activity_action' => 'delete_file_history',
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);
                }

                // Delete File comments
                DB::table('knowledge_base_file_comments')->where('knowledge_base_file_comment_file_id', $file->id)->delete();

                // Create Activity
                DB::table('knowledge_base_file_activity')->insert([
                    'knowledge_base_file_activity_user_id' => $user_id,
                    'knowledge_base_file_activity_file_id' => $file->id,
                    'knowledge_base_file_activity_action' => 'delete_file_comments',
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);
            }

            // Delete Folder
            DB::table('knowledge_base_folders')->where('id', $folder_id)->delete();

            // Delete Subfolders
            DB::table('knowledge_base_folders')
                ->where('knowledge_base_folder_parent_id', $folder_id)
                ->delete();

            // Delete Permission
            DB::table('knowledge_base_permissions')->where('knowledge_base_permission_folder_id', $folder_id)->delete();

            // Create Activity
            DB::table('knowledge_base_folder_activity')->insert([
                'knowledge_base_folder_activity_user_id' => $user_id,
                'knowledge_base_folder_activity_folder_id' => $folder_id,
                'knowledge_base_folder_activity_action' => 'delete_folder',
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);

            return response()->json(array(
                "status" => "success",
                "error" => false,
                "message" => ""
            ));
        } else {
            return response()->json(array(
                "status" => "error",
                "error" => true,
                "message" => "You don't have the permission to delete this folder!"
            ));
        }

    }

    // Create new Markdown File
    public function createNewFile(Request $request) {
        $folder_id = $request->input('folder_id');
        $file_name = $request->input('file_name');
        $file_description = $request->input('file_description');
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        if ($file_name == '') {
            return response()->json(array(
                'success' => false,
                'error' => 'The File name cannot be empty. Please enter a file name.'
            ));
        }

        $fileNameIsNotAvailable = true;
        $fileNameExtension = 1;

        while ($fileNameIsNotAvailable) {
            $fileSlug = DB::table('knowledge_base_files')
                ->where('knowledge_base_file_name', $file_name)
                ->where('knowledge_base_file_folder_id', $folder_id)
                ->count();

            if ($fileSlug > 0) {
                $file_name = $file_name . '-' . $fileNameExtension;
            } else {
                $fileNameIsNotAvailable = false;
            }

            $fileNameExtension++;
        }

        $slug = str_slug($file_name);

        $generatedFileName = time() . '-' . preg_replace('/\s+/', '-', $slug);
        $generatedFileName = strtolower($generatedFileName);

        // Create File
        $file_id = DB::table('knowledge_base_files')->insertGetId([
            'knowledge_base_file_name' => $file_name,
            'knowledge_base_file_slug' => $slug,
            'knowledge_base_file_description' => $file_description,
            'knowledge_base_file_extension' => 'md',
            'knowledge_base_file_path' => $generatedFileName . '.md',
            'knowledge_base_file_folder_id' => $folder_id,
            'knowledge_base_file_user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Create Activity
        DB::table('knowledge_base_file_activity')->insert([
            'knowledge_base_file_activity_user_id' => $user_id,
            'knowledge_base_file_activity_file_id' => $file_id,
            'knowledge_base_file_activity_action' => 'create_file',
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        // Create the File
        $path = resource_path() . '/knowledge-base-data/' . $generatedFileName . '.md';
        File::put($path, '# Knowledge Base "' . $file_name . '"');

        // Response
        $response = array(
            'success' => true,
            'file_id' => $file_id,
            'folder_id' => $folder_id,
            "message" => "File uploaded successfully!"
        );

        return response()->json($response);
    }

    // Get Folder Permissions
    public function getPermissions(Request $request) {
        $folder_id = $request->input('folder_id');

        $permissions = DB::table('knowledge_base_permissions')
            ->where('knowledge_base_permission_folder_id', $folder_id)
            ->get()
            ->toArray();

        // Get Users
        foreach ($permissions as $key => $permission) {
            $user = DB::table('users')
                ->where('id', $permission->knowledge_base_permission_user_id)
                ->first();

            $permissions[$key]->user = $user;
        }

        return response()->json(array(
            "status" => "success",
            "error" => false,
            "message" => "",
            "permissions" => $permissions
        ));
    }

    // Modify Folder Permissions
    public function modifyPermission(Request $request) {
        $folder_id = $request->input('folder_id');
        $user_id = $request->input('user_id');


        // Update Permission
        DB::table('knowledge_base_permissions')
            ->where('knowledge_base_permission_folder_id', $folder_id)
            ->where('knowledge_base_permission_user_id', $user_id)
            ->update([
                'knowledge_base_permission_read' => ($request->input('read_permission') == "true" ? true : false),
                'knowledge_base_permission_write' => ($request->input('create_permission') == "true" ? true : false),
                'knowledge_base_permission_modify' => ($request->input('modify_permission') == "true" ? true : false),
                'knowledge_base_permission_delete' => ($request->input('delete_permission') == "true" ? true : false),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        // Create Activity
        DB::table('knowledge_base_folder_activity')->insert([
            'knowledge_base_folder_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
            'knowledge_base_folder_activity_folder_id' => $folder_id,
            'knowledge_base_folder_activity_action' => 'modify_folder_permissions',
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        return response()->json(array(
            "status" => "success",
            "error" => false,
            "message" => ""
        ));
    }

    // Remove Folder Permission
    public function removePermission(Request $request) {
        $folder_id = $request->input('folder_id');
        $user_id = $request->input('user_id');

        // Remove Permission
        DB::table('knowledge_base_permissions')
            ->where('knowledge_base_permission_folder_id', $folder_id)
            ->where('knowledge_base_permission_user_id', $user_id)
            ->delete();

        // Create Activity
        DB::table('knowledge_base_folder_activity')->insert([
            'knowledge_base_folder_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
            'knowledge_base_folder_activity_folder_id' => $folder_id,
            'knowledge_base_folder_activity_action' => 'remove_folder_permissions',
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        return response()->json(array(
            "status" => "success",
            "error" => false,
            "message" => ""
        ));
    }

    // Create Folder Permission
    public function createPermission(Request $request) {

        $folder_id = $request->input('folder_id');

        if ($request->input('user_email') == '0') {
            $user = 0;
        } else {
            // Get User by E-Mail
            $user = DB::table('users')
                ->where('email', $request->input('user_email'))
                ->first();

            if ($user == null) {
                return response()->json(array(
                    "status" => "error",
                    "error" => true,
                    "message" => 'The User does not exist. Please enter a valid User E-Mail.'
                ));
            }

            $user = $user->id;
        }



        // Check if User already has a permission
        $permission = DB::table('knowledge_base_permissions')
            ->where('knowledge_base_permission_folder_id', $folder_id)
            ->where('knowledge_base_permission_user_id', $user)
            ->first();

        if ($permission != null) {
            return response()->json(array(
                "status" => "error",
                "error" => true,
                "message" => 'The User already has a permission for this Folder.'
            ));
        }

        // Create Permission
        DB::table('knowledge_base_permissions')->insert([
            'knowledge_base_permission_user_id' => $user,
            'knowledge_base_permission_folder_id' => $folder_id,
            'knowledge_base_permission_read' => 1,
            'knowledge_base_permission_write' => 1,
            'knowledge_base_permission_delete' => 0,
            'knowledge_base_permission_modify' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create activity
        DB::table('knowledge_base_folder_activity')->insert([
            'knowledge_base_folder_activity_user_id' => JWTAuth::parseToken()->authenticate()->id,
            'knowledge_base_folder_activity_folder_id' => $folder_id,
            'knowledge_base_folder_activity_action' => 'create_permission',
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        return response()->json(array(
            "status" => "success",
            "error" => false,
            "message" => ''
        ));
    }
}
