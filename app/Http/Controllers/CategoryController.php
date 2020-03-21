<?php

namespace App\Http\Controllers;

use App\Category;
use App\User;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }
    public function index()
    {
        $categories = Category::all();

        if (is_object($categories) && !empty($categories)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'categories' => $categories
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'Error al mostrar categorias'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function show($category)
    {
        $category = Category::find($category);

        if (is_object($category) && !empty($category)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'categories' => $category
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La categoria seleccionada no coincide en la base de datos'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'category' => 'Error al guardar Categoria'
                );
            } else {
                $category = new Category;
                $category->name = $params_array['name'];
                $category->save();

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'category' => $category
                );
            }
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'category' => 'Error al guardar Categoria'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {

        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($id) && !empty($params_array)) {

            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'category' => 'Error al validar Categoria'
                );
            } else {
                //fields I don't want to update from category table
                unset($params_array['id']);
                unset($params_array['created_at']);

                $category_update = Category::where('id', $id)->update($params_array);

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'category' => $params_array
                );
            }
        } else {
            $data = array(
                'code' => 400,
                'status' => 'errpr',
                'category' => 'Error al actualizar la categoria, no ID o request vacia'
            );
        }
        return response()->json($data, $data['code']);
    }
}
