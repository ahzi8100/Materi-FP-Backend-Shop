<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class RajaOngkirController extends Controller
{
    public function getProvinces()
    {
        // Mengambil data provinsi dari API Raja Ongkir
        $response = Http::withHeaders([

            //headers yang diperlukan untuk API Raja Ongkir
            'Accept' => 'application/json',
            'key' => config('rajaongkir.api_key'),

        ])->get('https://rajaongkir.komerce.id/api/v1/destination/province');

        return response()->json([
            'success' => true,
            'message' => 'List Data Provinces',
            'data' => $response->json()['data'] ?? []
        ]);
    }

    public function getCities($provinceId)
    {
        // Mengambil data kota berdasarkan ID provinsi dari API Raja Ongkir
        $response = Http::withHeaders([

            //headers yang diperlukan untuk API Raja Ongkir
            'Accept' => 'application/json',
            'key' => config('rajaongkir.api_key'),

        ])->get("https://rajaongkir.komerce.id/api/v1/destination/city/{$provinceId}");

        return response()->json([
            'success' => true,
            'message' => 'List Data Cities',
            'data' => $response->json()['data'] ?? []
        ]);
    }

    public function getDistricts($cityId)
    {
        // Mengambil data kecamatan berdasarkan ID kota dari API Raja Ongkir
        $response = Http::withHeaders([

            //headers yang diperlukan untuk API Raja Ongkir
            'Accept' => 'application/json',
            'key' => config('rajaongkir.api_key'),

        ])->get("https://rajaongkir.komerce.id/api/v1/destination/district/{$cityId}");

        return response()->json([
            'success' => true,
            'message' => 'List Data Districts',
            'data' => $response->json()['data'] ?? []
        ]);
    }

    public function checkOngkir(Request $request)
    {
        $response = Http::asForm()->withHeaders([

            //headers yang diperlukan untuk API Raja Ongkir
            'Accept' => 'application/json',
            'key'    => config('rajaongkir.api_key'),

        ])->post('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost', [
            'origin'      => 2619, // ID kecamatan Ngaglik (ganti sesuai kebutuhan)
            'destination' => $request->district_id, // ID kecamatan tujuan
            'weight'      => $request->weight, // Berat dalam gram
            'courier'     => $request->courier, // Kode kurir (jne, tiki, pos)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'List Data Ongkir',
            'data' => $response->json()['data'] ?? []
        ]);
    }
}
