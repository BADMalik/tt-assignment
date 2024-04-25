<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmployeeController extends Controller
{

    const BASE_API = 'https://smoke.staffr.net/rest/';

    private $credentials;
    private $token;

    /**
     * Store  a resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $this->getMappedRequestData($request);
            return $this->postOrUpdateData($data);

            return response()->json(['message' => 'Data posted']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to load data']);
        }
    }

    /**
     * update  a resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $this->getMappedRequestData($request);
            return $this->postOrUpdateData($data, $id);

            return response()->json(['message' => 'Data Updated']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to load data']);
        }
    }

    private function getMappedRequestData($request)
    {
        return match ($request->provider) {
            'provider1' => $this->mapProvider1Fields($request),
            'provider2' => $this->mapProvider2Fields($request),
        };
    }

    private function mapProvider1Fields($request)
    {
        return [
            'JobTitle'          => $request->title,
            'gender'            => $request->gender ?? 'M',
            'firstName'         => $request->first_name,
            'lastName'          => $request->last_name,
        ];
    }

    private function mapProvider2Fields($request)
    {
        return [
            'JobTitle'          => $request->jobTitle,
            'gender'            => $request->gender ?? 'M',
            'firstName'         => $request->firstName,
            'lastName'          => $request->lastName,
        ];
    }

    private function postOrUpdateData($data, $id = '')
    {
        $requestType = 'post';
        if ($id) {
            $id = '/' . $id;
            $requestType = 'put';
        }

        $this->credentials = \json_decode(\file_get_contents(\base_path('credentials.json')), true);
        $this->token       = \json_decode(\file_get_contents(\base_path('token.json')), true);
        $response          = Http::withToken(
            $this->token['access_token']
        )->$requestType(self::BASE_API . 'v1/employees' . $id, $data);


        if ($response->status() == 401) {
            $response = Http::post(self::BASE_API . 'oauth2/access_token', ['grant_type' => 'refresh_token', ...$this->token, ...$this->credentials]);
            \file_put_contents(\base_path('token.json'), \json_encode($response->json()));
            $this->postOrUpdateData($data);
        }

        return $response->body();
    }
}
