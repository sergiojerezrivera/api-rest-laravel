<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show',
         'getImage', 'getPostsByCategory', 'getPostsByUser']]);
    }

    public function index()
    {
        $post = Post::all()->load('Category');

        return response()->json($post, 200);
    }

    public function show($id)
    {
        $post = Post::find($id)->load('Category');

        if (is_object($post) && !empty($post)) {
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'post'      => $post
            );
        } else {
            $data = array(
                'code'      => 404,
                'status'    => 'error',
                'message'      => 'Error al encontrar el post seleccionado'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {

            //Get User Identify
            $user = $this->getIdentity($request);

            $validate = \Validator::make($params_array, [
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required',
                'image'         => 'required'
            ]);
            //|image|mimes:jpg,jpeg,png,gif

            if ($validate->fails()) {
                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   => 'Error al guardar la entrada, campos incompletos'
                );
            } else {
                $post = new Post;
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();

                $data = array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Datos guardados correctamente.',
                    'post'      => $post
                );
            }
        } else {
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al guardar la entrada, informacion incompleta'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {

            $validate = \Validator::make($params_array, [
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   => 'Error al actualizar la entrada, campos incompletos'
                );
            } else {
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_at']);
                unset($params_array['user']);

                //Get User Identify
                $user = $this->getIdentity($request);

                //Find the record to update
                $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();

                if (is_object($post) && !empty($post)) {

                    //Update the record with multiple where's
                    $post->update($params_array);

                    $data = array(
                        'code'      => 200,
                        'status'    => 'success',
                        'message'   => 'Datos actualizados correctamente.',
                        'post'      => $post,
                        'changes'   => $params_array
                    );
                } else {
                    $data = array(
                        'code'      => 400,
                        'status'    => 'error',
                        'message'   => 'Error entrada vacia.'
                    );
                }
            }
        } else {
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al actualizar la entrada.'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request)
    {
        //Get User Identify
        $user = $this->getIdentity($request);

        //Now I make sure the ID param matches with DB and
        //USER ID of Token matches with userID from DB and 
        //if both are correct then can delete.
        $post = Post::where('id', $id)->where('user_id', $user->sub)->first();

        if (is_object($post) && !empty($post)) {
            $post->delete();
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'ELemento eliminado permanentemente',
                'post'      => $post
            );
        } else {
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al eliminar el elemento buscado',
            );
        }
        return response()->json($data, $data['code']);
    }

    private function getIdentity($request)
    {
        //I want to to make sure that just the creator of the passed ID post
        //is the only one that can delete the post, and no one else.
        $jwAuth = new JwtAuth;
        $token = $request->header('Authorization', null);
        $user = $jwAuth->checkToken($token, true);
        return $user;
    }

    public function upload(Request $request)
    {
        //Collect image request
        $image = $request->file('file0');

        //Validate image
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        if ($validate->fails() || !$image) {
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al subir imagen'
            );
        } else {
            //Save the image in a disk called images
            $image_name = time() . $image->getClientOriginalName();
            Storage::disk('images')->put($image_name, \File::get($image));

            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'image'     => $image_name,
                'message'   => 'Imagen subida correctamente'
            );
        }
        //Return Data
        return response()->json($data, $data['code']);
    }

    public function getImage($filename)
    {
        $path = storage_path('app\\images\\' . $filename);

        //Check if filename exists then get it from Disk and print
        $issetFileName = Storage::disk('images')->exists($filename);

        if ($issetFileName) {
            $file = Storage::disk('images')->get($filename);
            //Next line optional to display pic  -> 
            $type = \File::mimeType($path);
            return response()->file($path, [
                'Content-Type' => $type
            ]);
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'Error al mostrar la imagen'
            );
            return response()->json($data, $data['code']);
        }
    }

    public function getPostsByCategory($id)
    {
        $posts = Post::where('category_id', $id)->get();

        if (!empty($posts) && is_object($posts)) {
            return response()->json([
                'status'    => 'success',
                'posts'     => $posts
            ], 200);
        } else {
            return response()->json([
                'status'    => 'error',
                'message'     => 'Error al recibir post'
            ], 400);
        }
    }

    public function getPostsByUser($id) {
        $posts = Post::where('user_id', $id)->get();

        if (!empty($posts) && is_object($posts)) {
            return response()->json([
                'status'    => 'success',
                'posts'     => $posts
            ], 200);
        } else {
            return response()->json([
                'status'    => 'error',
                'message'     => 'Error al recibir post'
            ], 400);
        }
    }
}
