<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Region;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ZonalControlCentreController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized access.');
        }

        if (!$user->isAdmin() && $user->portal_role !== 'mock_secretariat') {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized access.');
        }

        $regionSearch = trim((string) $request->query('region_search', ''));
        $districtSearch = trim((string) $request->query('district_search', ''));
        $schoolSearch = trim((string) $request->query('school_search', ''));

        $regions = Region::query()
            ->where('name', 'NOT LIKE', '%UNASSIGNED%')
            ->whereHas('schools', function ($query) {
                $query->where('education_level', 'PRIMARY');
            })
            ->withCount('districts')
            ->withCount([
                'schools as primary_schools_count' => function ($query) {
                    $query->where('education_level', 'PRIMARY');
                },
            ])
            ->addSelect([
                'rao_count' => User::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('region_id', 'regions.id')
                    ->where('portal_role', 'mock_rao'),
            ])
            ->addSelect([
                'dao_count' => User::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('region_id', 'regions.id')
                    ->where('portal_role', 'mock_dao'),
            ]);

        if ($regionSearch !== '') {
            $regions->where(function ($query) use ($regionSearch) {
                $query->where('name', 'LIKE', "%{$regionSearch}%")
                    ->orWhere('code', 'LIKE', "%{$regionSearch}%");
            });
        }

        $regions = $regions
            ->orderBy('name')
            ->paginate(12, ['*'], 'region_page')
            ->withQueryString();

        $districts = District::query()
            ->whereHas('schools', function ($query) {
                $query->where('education_level', 'PRIMARY');
            })
            ->with(['region:id,name'])
            ->withCount([
                'schools as primary_schools_count' => function ($query) {
                    $query->where('education_level', 'PRIMARY');
                },
            ])
            ->addSelect([
                'candidates_count' => DB::table('candidates')
                    ->join('schools', 'schools.id', '=', 'candidates.school_id')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('schools.district_id', 'districts.id')
                    ->where('schools.education_level', 'PRIMARY'),
            ]);

        if ($districtSearch !== '') {
            $districts->where(function ($query) use ($districtSearch) {
                $query->where('name', 'LIKE', "%{$districtSearch}%")
                    ->orWhere('code', 'LIKE', "%{$districtSearch}%")
                    ->orWhereHas('region', function ($regionQuery) use ($districtSearch) {
                        $regionQuery->where('name', 'LIKE', "%{$districtSearch}%");
                    });
            });
        }

        $districts = $districts
            ->orderBy('name')
            ->paginate(12, ['*'], 'district_page')
            ->withQueryString();

        $schools = School::query()
            ->where('education_level', 'PRIMARY')
            ->with(['region:id,name', 'district:id,name'])
            ->withCount('candidates')
            ->addSelect([
                'headteacher_count' => User::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('school_id', 'schools.id')
                    ->where('portal_role', 'mock_headteacher'),
            ]);

        if ($schoolSearch !== '') {
            $schools->where(function ($query) use ($schoolSearch) {
                $query->where('name', 'LIKE', "%{$schoolSearch}%")
                    ->orWhere('code', 'LIKE', "%{$schoolSearch}%")
                    ->orWhere('registration_number', 'LIKE', "%{$schoolSearch}%")
                    ->orWhereHas('district', function ($districtQuery) use ($schoolSearch) {
                        $districtQuery->where('name', 'LIKE', "%{$schoolSearch}%");
                    })
                    ->orWhereHas('region', function ($regionQuery) use ($schoolSearch) {
                        $regionQuery->where('name', 'LIKE', "%{$schoolSearch}%");
                    });
            });
        }

        $schools = $schools
            ->orderBy('name')
            ->paginate(12, ['*'], 'school_page')
            ->withQueryString();

        $primaryRegionIds = Region::query()
            ->where('name', 'NOT LIKE', '%UNASSIGNED%')
            ->whereHas('schools', function ($query) {
                $query->where('education_level', 'PRIMARY');
            })
            ->pluck('id');

        $regionIdsWithRao = User::query()
            ->where('portal_role', 'mock_rao')
            ->whereNotNull('region_id')
            ->whereIn('region_id', $primaryRegionIds)
            ->pluck('region_id');

        $regionsWithoutRao = Region::query()
            ->whereIn('id', $primaryRegionIds)
            ->whereNotIn('id', $regionIdsWithRao)
            ->orderBy('name')
            ->pluck('name');

        // Only flag districts that have not onboarded any schools at all.
        // Districts that intentionally carry secondary-only schools are outside
        // this PRIMARY onboarding warning and should not inflate the count.
        $districtsWithoutPrimarySchools = District::query()
            ->doesntHave('schools')
            ->count();

        $schoolsWithoutHeadteacher = School::query()
            ->where('education_level', 'PRIMARY')
            ->whereDoesntHave('users', function ($query) {
                $query->where('portal_role', 'mock_headteacher');
            })
            ->count();

        $schoolsWithoutCandidates = School::query()
            ->where('education_level', 'PRIMARY')
            ->whereDoesntHave('candidates')
            ->count();

        $stats = [
            'regions' => Region::where('name', 'NOT LIKE', '%UNASSIGNED%')->count(),
            'districts' => District::count(),
            'schools' => School::where('education_level', 'PRIMARY')->count(),
            'candidates' => DB::table('candidates')
                ->join('schools', 'schools.id', '=', 'candidates.school_id')
                ->where('schools.education_level', 'PRIMARY')
                ->count(),
        ];

        $alerts = collect([
            [
                'severity' => 'Critical',
                'title' => 'Regions without RAO coverage',
                'value' => $regionsWithoutRao->count(),
                'details' => $regionsWithoutRao->take(5)->implode(', '),
            ],
            [
                'severity' => 'Warning',
                'title' => 'Districts without PRIMARY schools',
                'value' => $districtsWithoutPrimarySchools,
                'details' => 'District follow-up required for school onboarding.',
            ],
            [
                'severity' => 'Warning',
                'title' => 'Schools without Headteacher account',
                'value' => $schoolsWithoutHeadteacher,
                'details' => 'Secretariat should coordinate account registration.',
            ],
            [
                'severity' => 'Info',
                'title' => 'Schools with zero registered candidates',
                'value' => $schoolsWithoutCandidates,
                'details' => 'Registration progress follow-up required.',
            ],
        ]);

        return view('admin.zonal-control-centre', compact(
            'stats',
            'alerts',
            'regions',
            'districts',
            'schools',
            'regionSearch',
            'districtSearch',
            'schoolSearch'
        ));
    }
}
