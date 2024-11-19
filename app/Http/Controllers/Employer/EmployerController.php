<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserEducationQualification;
use Illuminate\Http\Request;

class EmployerController extends Controller
{
    public function index(Request $request)
    {
        // Available filters
        $filters = $request->only(['service', 'expert_level', 'location', 'country', 'state', 'qualification', 'search', 'type']);

        // Base query with relations
        $candidates = User::query()->candidate()
            ->with(['category', 'countries', 'states', 'expertLevel', 'qualification']);
        
        // Filter by service category
        if (!empty($filters['service'])) {
            $candidates->whereHas('category', function ($query) use ($filters) {
                $query->where('title', 'LIKE', '%' . $filters['service'] . '%');
            });
        }

        // Filter by expert level
        if (!empty($filters['expert_level'])) {
            $candidates->whereHas('expertLevel', function ($query) use ($filters) {
                $query->where('title', 'LIKE', '%' . $filters['expert_level'] . '%');
            });
        }

        // Filter by location (country and state)
        if (!empty($filters['country'])) {
            $candidates->whereHas('countries', function ($query) use ($filters) {
                $query->where('name', 'LIKE', '%' . $filters['country'] . '%');
            });
        }

        if (!empty($filters['state'])) {
            $candidates->whereHas('states', function ($query) use ($filters) {
                $query->where('name', 'LIKE', '%' . $filters['state'] . '%');
            });
        }

        // Filter by qualification
        if (!empty($filters['qualification'])) {
            $candidates->whereHas('qualification', function ($query) use ($filters) {
                $query->where('title', 'LIKE', '%' . $filters['qualification'] . '%');
            });
        }

        // Filter by search term (name or email)
        if (!empty($filters['search']) && !empty($filters['type']) && in_array($filters['type'], ['name', 'email'])) {
            $candidates->where($filters['type'], 'LIKE', '%' . $filters['search'] . '%');
        }

        $data['candidates'] = $candidates->paginate();

        return response()->json($data);
    }
}
