<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
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
                'email'     => 'required|email|unique:users',
                'password'  => 'required|alpha_num'
            ]);

            //Response message for true or false options in case of error with JSON return
            if ($validate->fails()) {
                $data = array(
                    'status'    => 'error',
                    'code'      => '401',
                    'message'   => 'El usuario no se pudo crear.',
                    'error'     => $validate->errors()
                );
            } else {
                //Encrypt password
                //$pwd = password_hash($params->password, PASSWORD_BCRYPT, ['cost' => 4]);
                $pwd = hash('sha256', $params->password);

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

    public function login(Request $request)
    {
        $jwtAuth = new \JwtAuth();

        //Collect Data from Client or Front End
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Validate
        $validate = \Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required|alpha_num'
        ]);

        //Response message of validation
        if ($validate->fails()) {
            $signup = array(
                'status'    => 'error',
                'code'      => '400',
                'message'   => 'El usuario no se pudo logear.',
                'error'     => $validate->errors()
            );
        } else {
            //Encrypt Password
            $pwd = hash('sha256', $params->password);
        }
        //Obtain Token or Data
        $signup = $jwtAuth->signup($params->email, $pwd);

        if (!empty($params->getToken)) {
            $signup = $jwtAuth->signup($params->email, $pwd, true);
        }

        //Return
        return response()->json($signup, 200);
    }

    //Check if user is logged and update DB else error message
    public function update(Request $request)
    {
        //Collect JWT from Headers, and check token
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);


        //Update user in DB via PUT from Request
        //Collect data to update via JSON request and decode
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if ($checkToken && !empty($params_array)) {

            //Grab ID of user logged to make an exception in validation
            $user = $jwtAuth->checkToken($token, true);

            //Validate
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users,' . $user->sub
            ]);

            //fields I don't want to update from user table
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Updating in DB just needed fields
            $user_update = User::where('id', $user->sub)->update($params_array);

            //Return array response
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }

        return response()->json($data, $data['code']);
    }
}
