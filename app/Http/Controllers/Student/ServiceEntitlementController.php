<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Services\StudentEntitlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceEntitlementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        StudentEntitlementService::expireStaleForUser((int) $user->id);

        $entitlements = StudentServiceEntitlement::query()
            ->forUser((int) $user->id)
            ->with([
                'servicePackage:id,name,slug',
                'tutoringGroup:id,title,slug',
                'academicYear:id,name',
                'academicSubject:id,name',
            ])
            ->orderByDesc('id')
            ->paginate(20);

        $totals = [
            'individual' => StudentEntitlementService::unitsLeft((int) $user->id, ServicePackage::SCOPE_TUTORING_INDIVIDUAL),
            'collective' => StudentEntitlementService::unitsLeft((int) $user->id, ServicePackage::SCOPE_TUTORING_COLLECTIVE),
            'private' => StudentEntitlementService::unitsLeft((int) $user->id, ServicePackage::SCOPE_PRIVATE_LESSONS),
            'global' => StudentEntitlementService::unitsLeft((int) $user->id, ServicePackage::SCOPE_GLOBAL),
        ];

        $packages = ServicePackage::storefrontCatalog(12);

        return view('student.service-entitlements.index', compact('entitlements', 'totals', 'packages'));
    }
}
