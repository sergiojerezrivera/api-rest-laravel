<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
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

            //Need to check the logged user via TOKEN
            $jwAuth = new JwtAuth;
            $token = $request->header('Authorization', null);
            $user = $jwAuth->checkToken($token, true);

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

                //Improved method to get the full object info
                $post = Post::where('id', $id)->updateOrCReate($params_array);

                $data = array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Datos actualizados correctamente.',
                    'post'      => $post,
                    'changes'   => $params_array
                );
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
}
