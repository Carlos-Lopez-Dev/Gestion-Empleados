<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordMail;


class UsersController extends Controller
{
   

    public function createUser (Request $req){
        $response = ['status'=>1, "msg"=>""];

        $datos = $req->getContent();

        $validator = Validator::make(json_decode($req->getContent(),true),[
            'name' => 'required|max:255',
            'email' => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix|unique:users|max:255',
            'password' => 'required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}/',
            'puesto' => 'required|in:Direccion,RRHH,Empleado',
            'salario' => 'required|max:255',
            'biografia' => 'required',

        ]); 
        if ($validator->fails()){
           $response['msg'] = 'Ha ocurrido un error' . $validator->errors()->first();
        }else{

            $datos = json_decode($datos);

            try{

                $user = new User();

                $user->name = $datos->name;
                $user->email = $datos->email;
                $user->password = Hash::make($datos->password);
                $user->puesto = $datos->puesto;
                $user->salario = $datos->salario;
                $user->biografia = $datos->biografia;
                

                $user->save();
                $response['msg'] = "Usuario guardado";
            }catch(\Exception $e){
                $response['msg'] = $e->getMessage();
                $response['status'] = 0;
            }

         
        }
           return response()->json($response);
    }


    public function login (Request $req){
        $response = ['status'=>1, "msg"=>""];

        $users = "";

        if ($req->has('email') && ($req->input('email') != "")){
            $users = User::where('email', $req->input('email'))->first();
        }else{
            $response['msg']= 'Introduce el email';
        }
        if($users){
            if(Hash::check($req->input('password'), $users->password)){
                try{
                    $token = Hash::make(now(). $users->id);
                    $users->api_token = $token;
                    $users->save();
                    $response['msg'] = "Session token: " . $users->api_token;

                }catch(\Exception $e){
                $response['msg'] = $e->getMessage();
                $response['status'] = 0;
                }
            }else{
                $response['msg'] = 'Contraseña incorrecta';
            }
        }else{
            $response['msg'] .= ', No hay usuarios con este email';
        }

        return response()->json($response);

    }


    public function userList (Request $req){
        $response = ['status'=>1, "msg"=>""];

        $user = $req->user;
        $puesto = $user->puesto;

        try{
            if($puesto == 'RRHH'){
                $response['msg'] = DB::table('users')
                ->select('name', 'puesto', 'salario')
                ->where('puesto', 'Empleado')
                ->get();

            }else{
                $response['msg'] = DB::table('users')
                ->select('name', 'puesto', 'salario')
                ->where('puesto', 'Empleado')
                ->orWhere('puesto', 'RRHH')
                ->get();
            }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
        }

        return response()->json($response);
    }


    public function profile (Request $req){
        $response = ['status'=>1, "msg"=>""];
        $user = $req->user;

        try{
            $response['msg'] = $user;
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
        }

        return response()->json($response);
    }


    public function userData (Request $req){
        $response = ['status'=>1, "msg"=>""];
        $user = $req->user;
        $id = $req->input('id');
        $userById = User::find($id);

        try{

            if($user->id == $id){
                    $response['msg'] = $user;
                }else if($user->puesto == $userById->puesto){
                    $response['msg'] = "No tienes permisos para realizar esta operacion";
                }else if($user->puesto != $userById->puesto && $user->puesto == "RRHH"){
                    $response['msg'] = "No tienes permisos para realizar esta operacion";   
                }else{
                    $response['msg'] = $userById;
                }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
        }

        
        return response()->json($response);
    }


    public function edit(Request $req){
        $response = ['status'=>1, "msg"=>""];

        $user = $req->user;
        $id = $req->input('id');

        $dataUser = $req->getContent();
        $dataUser = json_decode($dataUser);
        $userById = User::find($id);
         

        if($userById){
            
            try{
                if(isset($dataUser->name))
                    $userById->name = $dataUser->name;

                if(isset($dataUser->email))
                    $userById->email = $dataUser->email;

                if(isset($dataUser->password))
                    $userById->password = Hash::make($dataUser->password);

                if(isset($dataUser->puesto))
                    $userById->puesto = $dataUser->puesto;

                if(isset($dataUser->biografia))
                    $userById->biografia = $dataUser->biografia;

                if(isset($dataUser->salario))
                    $userById->salario = $dataUser->salario;

            

                if($user->id == $id){
                    $userById->save();
                }else if($user->puesto == $userById->puesto){
                    $response['msg'] = "No tienes permisos para realizar esta operacion";
                }else if($user->puesto != $userById->puesto && $userById->puesto == "Dirección"){
                    $response['msg'] = "No tienes permisos para realizar esta operacion";   
                }else{
                    $userById->save();
                    $response['msg'] = "Usuario actualizado";
                }
                
            }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            }
        }else{
            $response['msg'] = "Empleado no encontrado";
            $response['status'] = 0;
        }

        return response() -> json($response);
    }

    public function newPassword(Request $req){
        $response = ['status'=>1, "msg"=>""];

        $email = $req->input('email');
        $user = User::where('email', $req->input('email'))->first();

        if($user){

            try{

                
                $newPassword = $this->randomPassword(10);
                $newPassword = $newPassword . "0!";
                $user -> password = Hash::make($newPassword);


                Mail::to($email)->send(new PasswordMail("Tu nueva contraseña", $newPassword));


                $response['msg'] = "Emai enviado";

            }catch(\Exception $e){
                $response['msg'] = $e->getMessage();
                $response['status'] = 0;
                }
        }else{
            $response['msg'] = "Usuario no encontrado";
            $response['status'] = 0;
        }


        return response() -> json($response);    
    }


    public function randomPassword($length){
        $variables = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($variables),0,$length);
    }
}   
