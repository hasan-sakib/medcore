<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PublicAppointmentRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function home(): Response
    {
        $hospitals = Tenant::where('status', 'active')
            ->where('is_publicly_listed', true)
            ->get(['id', 'name', 'slug', 'tagline', 'city', 'logo_url', 'features']);

        return Inertia::render('Public/Home', [
            'hospitals' => $hospitals,
        ]);
    }

    public function hospitals(Request $request): Response
    {
        $query = Tenant::where('status', 'active')->where('is_publicly_listed', true);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%");
            });
        }

        $hospitals = $query->get(['id', 'name', 'slug', 'tagline', 'city', 'phone', 'logo_url', 'features', 'description']);

        return Inertia::render('Public/Hospitals/Index', [
            'hospitals' => $hospitals,
            'search' => $request->get('search', ''),
        ]);
    }

    public function hospitalShow(string $slug): Response
    {
        $hospital = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->where('is_publicly_listed', true)
            ->firstOrFail();

        $departments = Department::withoutTenant()
            ->where('tenant_id', $hospital->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'description']);

        $doctors = User::withoutTenant()
            ->where('tenant_id', $hospital->id)
            ->where('is_publicly_listed', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'doctor'))
            ->get(['id', 'name', 'specialty', 'bio', 'avatar_url']);

        return Inertia::render('Public/Hospitals/Show', [
            'hospital' => $hospital,
            'departments' => $departments,
            'doctors' => $doctors,
        ]);
    }

    public function doctors(Request $request): Response
    {
        $query = User::withoutTenant()
            ->where('is_publicly_listed', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'doctor'))
            ->with('tenant:id,name,slug,city');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%");
            });
        }

        if ($tenantId = $request->get('hospital_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($specialty = $request->get('specialty')) {
            $query->where('specialty', 'like', "%{$specialty}%");
        }

        $doctors = $query->paginate(20, ['id', 'name', 'specialty', 'bio', 'avatar_url', 'tenant_id']);

        $hospitals = Tenant::where('status', 'active')
            ->where('is_publicly_listed', true)
            ->get(['id', 'name']);

        $specialties = User::withoutTenant()
            ->where('is_publicly_listed', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'doctor'))
            ->whereNotNull('specialty')
            ->distinct()
            ->pluck('specialty');

        return Inertia::render('Public/Doctors/Index', [
            'doctors' => $doctors,
            'hospitals' => $hospitals,
            'specialties' => $specialties,
            'filters' => $request->only('search', 'hospital_id', 'specialty'),
        ]);
    }

    public function appointmentForm(Request $request): Response
    {
        $hospitals = Tenant::where('status', 'active')
            ->where('is_publicly_listed', true)
            ->get(['id', 'name', 'slug']);

        $selectedTenantId = $request->get('hospital_id');

        $departments = $selectedTenantId
            ? Department::withoutTenant()
                ->where('tenant_id', $selectedTenantId)
                ->where('is_active', true)
                ->get(['id', 'name'])
            : collect();

        $doctors = $selectedTenantId
            ? User::withoutTenant()
                ->where('tenant_id', $selectedTenantId)
                ->where('is_publicly_listed', true)
                ->whereHas('roles', fn ($q) => $q->where('name', 'doctor'))
                ->get(['id', 'name', 'specialty'])
            : collect();

        return Inertia::render('Public/Appointments/Book', [
            'hospitals' => $hospitals,
            'departments' => $departments,
            'doctors' => $doctors,
            'prefill' => $request->only('hospital_id', 'department_id', 'doctor_id'),
        ]);
    }

    public function appointmentStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'department_id' => 'nullable|exists:departments,id',
            'doctor_id' => 'nullable|exists:users,id',
            'patient_name' => 'required|string|max:200',
            'patient_phone' => 'required|string|max:30',
            'patient_email' => 'nullable|email|max:200',
            'preferred_date' => 'nullable|date|after:today',
            'message' => 'nullable|string|max:1000',
        ]);

        PublicAppointmentRequest::create($data);

        return redirect()->route('public.appointment.confirm');
    }

    public function appointmentConfirm(): Response
    {
        return Inertia::render('Public/Appointments/Confirm');
    }
}
