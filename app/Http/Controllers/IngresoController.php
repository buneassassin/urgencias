<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Models\Paciente;
use App\Models\Diagnostico;
use App\Models\Cama;
use App\Models\Personal;
use Illuminate\Http\Request;
use Faker\Factory as Faker;

//consultas
class IngresoController extends Controller
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
                ->post('http://192.168.118.187:3325/consultas/crear',[
                        'email' => $request->input('email'),
                        'password' => $request->input('password'),
                        'mascota_id' => $faker->randomNumber(), 
                        'veterinario_id' => $faker->randomNumber(), 
                        'diagnostico' => $faker->sentence(5),
                        'tratamiento' => $faker->sentence(8),
                ]);
            $datas = $response->json();

        $request->validate([
            'pacientes_id' => 'required|exists:pacientes,id',
            'diagnostico_id' => 'required|exists:diagnosticos,id',
            'camas_id' => 'required|exists:camas,id',
            'personal_id' => 'required|exists:personal,id',
            'fecha_ingreso' => 'required|date',
            'motivo_ingreso' => 'required|string',
            'fecha_alta' => 'nullable|date'
        ]);

        $ingreso = Ingreso::create([
            'pacientes_id' => $request->pacientes_id,
            'diagnostico_id' => $request->diagnostico_id,
            'camas_id' => $request->camas_id,
            'personal_id' => $request->personal_id,
            'fecha_ingreso' => $request->fecha_ingreso,
            'motivo_ingreso' => $request->motivo_ingreso,
            'fecha_alta' => $request->fecha_alta
        ]);

        return response()->json($ingreso, 201);
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
                ->get('http://192.168.118.187:3325/consultas/'.$id,[
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);

            $datas = $response->json();

            //this appi
            
            $ingreso = Ingreso::find($id);
            if (!$ingreso) {
                return response()->json(['message' => 'Ingreso no encontrado'], 404);
            }
        } else {
            $ingreso = Ingreso::all();

        }

        return response()->json([
            'ingreso' => $ingreso,
            'consultas' => $datas //respuesta del sig appi
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
            ->put('http://192.168.118.187:3325/consultas/'.$id.'/editar',[
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'mascota_id' => $faker->randomNumber(), 
                'veterinario_id' => $faker->randomNumber(), 
                'diagnostico' => $faker->sentence(5),
                'tratamiento' => $faker->sentence(8),
            ]);
        $datas = $response->json();

    //this appi
        $ingreso = Ingreso::find($id);
        if (!$ingreso) {
            return response()->json(['message' => 'Ingreso no encontrado'], 404);
        }

        $request->validate([
            'pacientes_id' => 'required|exists:pacientes,id',
            'diagnostico_id' => 'required|exists:diagnosticos,id',
            'camas_id' => 'required|exists:camas,id',
            'personal_id' => 'required|exists:personal,id',
            'fecha_ingreso' => 'required|date',
            'motivo_ingreso' => 'required|string',
            'fecha_alta' => 'nullable|date'
        ]);

        $ingreso->update($request->only([
            'pacientes_id',
            'diagnostico_id',
            'camas_id',
            'personal_id',
            'fecha_ingreso',
            'motivo_ingreso',
            'fecha_alta'
        ]));

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
                ->delete('http://192.168.118.187:3325/consultas/'.$id,[
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                ]);
            $datas = $response->json();

        $ingreso = Ingreso::find($id);
        if (!$ingreso) {
            return response()->json(['message' => 'Ingreso no encontrado'], 404);
        }

        $ingreso->delete();
        return response()->json(['message' => 'Ingreso eliminado'], 204);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->validator->errors()], 422);
}
    }
}
