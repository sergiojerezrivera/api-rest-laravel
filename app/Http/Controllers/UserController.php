<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{
    public function register(Request $request)
    {

        //Tasks to do for register user into DB

        //Collect user data using POST method and create an object and Array
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Check if data is empty before running all validation 
        if (!empty($params_array) && !empty($params)) {
            //Trim input
            $params_array = array_map('trim', $params_array);

            //Validate Data
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users', //Check if User already exists (to avoid duplication)
                'password'  => 'required|alpha_num'
            ]);

            //Response message for true or false options in case of error with JSON return
            if ($validate->fails()) {
                $data = array(
                    'status'    => 'error',
                    'code'      => '400',
                    'message'   => 'El usuario no se pudo crear.',
                    'error'     => $validate->errors()
                );
            } else {
                //Encrypt password
                $pwd = password_hash($params->password, PASSWORD_BCRYPT, ['cost' => 4]);

                //Create User
                $user = new User;
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                $user->save();

                $data = array(
                    'status'    => 'success',
                    'code'      => '200',
                    'message'   => 'El usuario se ha creado correctamente.',
                );
            }
        } else {
            $data = array(
                'status'    => 'error',
                'code'      => '400',
                'message'   => 'No pueden quedar campos vacios, por favor rellena todos los campos.',
                'error'     => $validate->errors()
            );
        }
        


        return response()->json($data, $data['code']);
    }

    public function login(Request $request) {
        $jwtAuth = new \JwtAuth();
        
        return $jwtAuth->signup();
    }
}
