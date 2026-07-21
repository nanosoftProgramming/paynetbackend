<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ClientService;
use App\Http\Resources\ClientsResource;

class AdminClientsController extends Controller
{
    protected $ClientService;

    // تم إضافة الـ Constructor هنا لحقن الـ Service وتعريف الخاصية
    public function __construct(ClientService $ClientService)
    {
        $this->ClientService = $ClientService;
    }

    public function index(Request $request)
    {
        try {
            $data = $request->all();
            $relations = [];
            $clients = $this->ClientService->active($data, $relations);

            return returnMessage(true, 'clients', ClientsResource::collection($clients)->response()->getData(true));

        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

public function toggleActivate(Request $request, User $user)
    {
        try {
            // تمرير الـ Request بالكامل للخدمة لالتقاط قيمة is_active المرسلة من الـ Frontend
            $updatedUser = $this->ClientService->toggleActivate($user, $request->all());
            
            return returnMessage(true, "User updated successfully", new ClientsResource($updatedUser));
        } catch (\Exception $e) {
            return returnMessage(false, $e->getMessage(), null, 'server_error');
        }
    }
}