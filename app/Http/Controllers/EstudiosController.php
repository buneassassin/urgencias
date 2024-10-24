<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\TiposDeEstudio;
use App\Models\Personal;
use Illuminate\Http\Request;
use Faker\Factory as Faker;

//razas
class EstudiosController extends Controller
{
    public function create(Request $request)
    {
        try { //para cachar errores de validación
            $faker = Faker::create();
            //mandar credenciales a la sig api
            $login = Http::post('http://192.168.118.187:3325/login', [                         
                'email' => $request->input('emails'),
                'password' => $request->input('passwords'),
            ]);
            $token = $login->json()['token_2'];
    
            $response = Http::withToken($token)
                ->timeout(80)
                //crear en la tabla de la sig api
                ->post('http://192.168.118.187:3325/razas/crear',[
                        'email' => $request->input('email'),
                        'password' => $request->input('password'),

                        'nombre' => $faker->word,
                        'descripcion' => $faker->sentence(10),
                ]);
            $datas = $response->json();

        $request->validate([
            'tipos_de_estudios_id' => 'required|exists:tipos_de_estudios,id',
            'personal_id' => 'required|exists:personal,id'
        ]);

        $estudio = Estudio::create([
            'tipos_de_estudios_id' => $request->tipos_de_estudios_id,
            'personal_id' => $request->personal_id
        ]);

        return response()->json($estudio, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()], 422);
        }
    }

    public function read($id = null)
    {
        if ($id) {
            //lógica para acceder al sig api
            $login = Http::post('http://192.168.118.187:3325/login', [                         
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);
            $token = $login->json()['token_2'];

            $response = Http::withToken($token)
                ->timeout(80)
            //read a la sig appi
                ->get('http://192.168.118.187:3325/razas/'.$id,[
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);

            $datas = $response->json();

            //this appi
            $estudio = Estudio::find($id);
            if (!$estudio) {
                return response()->json(['message' => 'No encontrado'], 404);
            }
        } else {
            $estudio = Estudio::all();
        }

        return response()->json([
            'estudio' => $estudio,
            'razas' => $datas //respuesta del sig appi
        ], 200);
    }

    public function update(Request $request, $id)
    {
        //sig appi acceso
        $faker= Faker::create();
        $login = Http::post('http://192.168.118.187:3325/login', [                         
        'email' => $request->input('email'),
        'password' => $request->input('password'),
        ]);
        $token = $login->json()['token_2'];
        //sig appi petición
        $response = Http::withToken($token)
            ->timeout(80)
            ->put('http://192.168.118.187:3325/razas/'.$id.'/editar',[
                'email' => $request->input('email'),
                'password' => $request->input('password'),

                'nombre' => $faker->word,
                'descripcion' => $faker->sentence(10),
            ]);
         $datas = $response->json();

        //this appi
        $estudio = Estudio::find($id);
        if (!$estudio) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'tipos_de_estudios_id' => 'required|exists:tipos_de_estudios,id',
            'personal_id' => 'required|exists:personal,id'
        ]);

        $estudio->update($request->only(['tipos_de_estudios_id', 'personal_id']));
        return response()->json(['message' => 'Datos actualizado correctamente'], 200);
    }

    public function delete($id)
    {
        try {
            $login = Http::post('http://192.168.118.187:3325/login', [                         
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);
            $token = $login->json()['token_2'];
    
            $response = Http::withToken($token)
                ->timeout(80)
                ->delete('http://192.168.118.187:3325/razas/'.$id,[
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                ]);
            $datas = $response->json();

        $estudio = Estudio::find($id);
        if (!$estudio) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $estudio->delete();
        return response()->json(['message' => 'Eliminado'], 204);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->validator->errors()], 422);
}
    }
}
