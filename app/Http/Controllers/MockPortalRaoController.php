<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;
use App\Models\School;
use App\Models\Candidate;
use App\Models\District;
use App\Models\DistrictCouncil;
use App\Models\Region;

class MockPortalRaoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('mock-portal.login');
            }

            // RAO must be associated with a region
            $user = auth()->user();
            $regionId = $user->region_id;

            if (!$regionId && !$user->isAdmin()) {
                return redirect()->back()->withErrors(['region' => 'No region assigned to your account. Please contact the administrator.']);
            }

            // Tab management
            $tab = $request->query('tab', 'overview');

            // Scope data by region
            $region = Region::with('councils')->find($regionId);
            
            if (!$region) {
                $region = Region::with('councils')->first();
                if (!$region) abort(404, 'No regions found in the database.');
                $regionId = $region->id;
            }



            // Summary Stats
            $stats = [
                'total_districts' => \App\Models\District::where('region_id', $regionId)->count(),
                'total_schools'   => School::where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH'])->count(),
                'total_candidates' => Candidate::whereHas('school', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
                })->count(),
                'total_users'     => User::where('region_id', $regionId)->count(),
            ];

            // Search Filter
            $search = $request->query('search');

            // Overview Data - Show all councils in the region (DAOs use councils)
            $districtStats = \App\Models\DistrictCouncil::where('region_id', $regionId)
                ->orderBy('name')
                ->get()
                ->map(function($council) {
                    $schoolsCount = \App\Models\School::where('council_id', $council->id)
                        ->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH'])
                        ->count();
                        
                    $candidatesCount = \App\Models\Candidate::whereHas('school', function($q) use ($council) {
                            $q->where('council_id', $council->id)
                              ->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
                        })
                        ->count();

                    // Manually set counts so the view works as expected
                    $council->schools_count = $schoolsCount;
                    $council->candidates_count = $candidatesCount;
                    return $council;
                });

            // DAO Users
            $daoUsers = User::where('region_id', $regionId)
                ->where('portal_role', 'mock_dao')
                ->with('council')
                ->orderBy('name');
            
            if ($tab === 'users' && $search) {
                $daoUsers->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }
            $daoUsers = $daoUsers->paginate(20, ['*'], 'user_page')->withQueryString();

            // Registration Status by School - Optimized for large regions
            $schoolsQuery = School::where('region_id', $regionId)
                ->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH'])
                ->withCount('candidates')
                ->with('council')
                ->orderBy('name');
            
            if ($tab === 'schools' && $search) {
                $schoolsQuery->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }
            $schoolsList = $schoolsQuery->paginate(20, ['*'], 'school_page')->withQueryString();

            // Corrected/Rejected Errors
            $rejectionsList = Candidate::whereHas('school', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
                })
                ->where(function($q) {
                    $q->where('status', 'rejected')
                      ->orWhereNotNull('rejection_reason');
                })
                ->with('school')
                ->orderBy('updated_at', 'desc')
                ->paginate(20, ['*'], 'error_page')->withQueryString();

            // All Candidates for Management
            $candidatesQuery = Candidate::whereHas('school', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
                })
                ->with(['school.council'])
                ->orderBy('full_name');
            
            if ($tab === 'candidates' && $search) {
                $candidatesQuery->where(function($q) use ($search) {
                    $q->where('full_name', 'LIKE', "%{$search}%")
                      ->orWhere('candidate_id', 'LIKE', "%{$search}%")
                      ->orWhere('prem_no', 'LIKE', "%{$search}%");
                });
            }
            $candidatesList = $candidatesQuery->paginate(20, ['*'], 'cand_page')->withQueryString();

            // Real-time Data Integrity Issues (Auto-Detection)
            $detectedErrors = collect();
            
            // 1. Missing PREM Numbers in the region
            $missingPrem = Candidate::whereHas('school', function($q) use ($regionId) {
                $q->where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
            })->where(function($q) {
                $q->whereNull('prem_no')->orWhere('prem_no', '');
            })->with('school')->get();
            
            foreach($missingPrem as $mp) {
                $detectedErrors->push([
                    'id' => $mp->id,
                    'type' => 'Critical',
                    'category' => 'Candidate',
                    'target_name' => $mp->full_name,
                    'search_term' => $mp->full_name,
                    'centre_number' => $mp->school?->code,
                    'description' => "Missing PREM Number for student: {$mp->full_name}",
                    'school' => $mp->school?->name ?? 'Unknown School',
                    'action' => 'Registration Required'
                ]);
            }

            // 2. Schools with zero candidates
            $emptySchools = School::where('region_id', $regionId)
                ->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH'])
                ->whereDoesntHave('candidates')
                ->get();
            
            foreach($emptySchools as $es) {
                $detectedErrors->push([
                    'id' => $es->id,
                    'type' => 'Warning',
                    'category' => 'School',
                    'target_name' => $es->name,
                    'search_term' => $es->name,
                    'centre_number' => $es->code,
                    'description' => "No candidates registered for this school.",
                    'school' => $es->name,
                    'action' => 'Review Required'
                ]);
            }

            $errorStats = [
                'total' => $detectedErrors->count(),
                'critical' => $detectedErrors->where('type', 'Critical')->count(),
                'warning' => $detectedErrors->where('type', 'Warning')->count(),
            ];

            $errorPage = max(1, (int) $request->query('detected_error_page', 1));
            $errorPerPage = 20;
            $detectedErrors = new LengthAwarePaginator(
                $detectedErrors->forPage($errorPage, $errorPerPage)->values(),
                $detectedErrors->count(),
                $errorPerPage,
                $errorPage,
                [
                    'path' => $request->url(),
                    'pageName' => 'detected_error_page',
                    'query' => $request->query(),
                ]
            );

            $rejectedCandidatesCount = Candidate::whereHas('school', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
                })
                ->where(function ($q) {
                    $q->where('status', 'rejected')
                        ->orWhereNotNull('rejection_reason');
                })
                ->count();

            $maleCandidatesCount = Candidate::whereHas('school', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
                })
                ->where('gender', 'M')
                ->count();

            $femaleCandidatesCount = Candidate::whereHas('school', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH']);
                })
                ->where('gender', 'F')
                ->count();

            $summaryCards = match ($tab) {
                'users' => [
                    ['label' => 'DAO Users', 'value' => number_format($daoUsers->total()), 'description' => 'District officers in current scope', 'icon' => 'fas fa-user-shield', 'color' => '#fff'],
                    ['label' => 'Councils Assigned', 'value' => number_format($daoUsers->getCollection()->pluck('council_id')->filter()->unique()->count()), 'description' => 'Visible DAO council assignments', 'icon' => 'fas fa-building-columns', 'color' => 'var(--tz-blue)'],
                    ['label' => 'Region Schools', 'value' => number_format($stats['total_schools']), 'description' => 'Primary centers under supervision', 'icon' => 'fas fa-school', 'color' => '#fff'],
                    ['label' => 'Region Candidates', 'value' => number_format($stats['total_candidates']), 'description' => 'Primary enrollment across the region', 'icon' => 'fas fa-users', 'color' => 'var(--tz-green)'],
                ],
                'schools' => [
                    ['label' => 'Visible Schools', 'value' => number_format($schoolsList->total()), 'description' => 'School records in current scope', 'icon' => 'fas fa-school', 'color' => '#fff'],
                    ['label' => 'Districts Covered', 'value' => number_format($districtStats->where('schools_count', '>', 0)->count()), 'description' => 'Districts with primary centres', 'icon' => 'fas fa-map-location-dot', 'color' => '#fff'],
                    ['label' => 'Registered Pupils', 'value' => number_format($stats['total_candidates']), 'description' => 'Pupils linked to listed centres', 'icon' => 'fas fa-users', 'color' => 'var(--tz-blue)'],
                    ['label' => 'Avg Candidates', 'value' => number_format((int) round($stats['total_candidates'] / max($stats['total_schools'], 1))), 'description' => 'Average candidates per school', 'icon' => 'fas fa-chart-column', 'color' => 'var(--tz-green)'],
                ],
                'errors' => [
                    ['label' => 'Detected Issues', 'value' => number_format($errorStats['total']), 'description' => 'Auto-detected regional integrity issues', 'icon' => 'fas fa-triangle-exclamation', 'color' => '#fff'],
                    ['label' => 'Critical Issues', 'value' => number_format($errorStats['critical']), 'description' => 'Records needing immediate correction', 'icon' => 'fas fa-circle-exclamation', 'color' => '#ff7b7b'],
                    ['label' => 'Warnings', 'value' => number_format($errorStats['warning']), 'description' => 'Advisory items for review', 'icon' => 'fas fa-bell', 'color' => 'var(--tz-blue)'],
                    ['label' => 'Rejected Records', 'value' => number_format($rejectedCandidatesCount), 'description' => 'Candidates already sent back', 'icon' => 'fas fa-rotate-left', 'color' => 'var(--tz-green)'],
                ],
                'candidates' => [
                    ['label' => 'Visible Candidates', 'value' => number_format($candidatesList->total()), 'description' => 'Candidate records in current scope', 'icon' => 'fas fa-id-card', 'color' => '#fff'],
                    ['label' => 'Male Candidates', 'value' => number_format($maleCandidatesCount), 'description' => 'Boys registered in the region', 'icon' => 'fas fa-person', 'color' => 'var(--tz-blue)'],
                    ['label' => 'Female Candidates', 'value' => number_format($femaleCandidatesCount), 'description' => 'Girls registered in the region', 'icon' => 'fas fa-person-dress', 'color' => '#ff9ec9'],
                    ['label' => 'Rejected Records', 'value' => number_format($rejectedCandidatesCount), 'description' => 'Candidate corrections pending', 'icon' => 'fas fa-user-xmark', 'color' => 'var(--tz-green)'],
                ],
                default => [
                    ['label' => 'Districts', 'value' => number_format($stats['total_districts']), 'description' => "In {$region->name} (Primary)", 'icon' => 'fas fa-city', 'color' => '#fff'],
                    ['label' => 'Total Schools', 'value' => number_format($stats['total_schools']), 'description' => 'PSLE centers', 'icon' => 'fas fa-school', 'color' => '#fff'],
                    ['label' => 'Registered Pupils', 'value' => number_format($stats['total_candidates']), 'description' => 'Regional enrollment', 'icon' => 'fas fa-users', 'color' => 'var(--tz-blue)'],
                    ['label' => 'Users Active', 'value' => number_format($stats['total_users']), 'description' => 'DAOs & Headteachers', 'icon' => 'fas fa-user-check', 'color' => 'var(--tz-green)'],
                ],
            };

            return view('mock-portal.rao.dashboard', compact(
                'user', 'region', 'stats', 'districtStats', 'schoolsList', 
                'rejectionsList', 'candidatesList', 'tab', 'daoUsers', 
                'detectedErrors', 'errorStats', 'search', 'summaryCards'
            ));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'System Error: ' . $e->getMessage()]);
        }
    }

    public function updateCandidate(Request $request, Candidate $candidate)
    {
        $user = Auth::user();
        // Authorize RAO or Admin
        if ($user->portal_role !== 'mock_rao' && !$user->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'candidate_id' => ['required', 'string', 'regex:/^PS[0-9]{7}-[0-9]{4}$/'],
            'full_name'    => 'required|string|max:255',
            'gender'       => 'required|in:M,F',
            'prem_no'      => 'required|string|size:11|regex:/^[0-9]+$/',
        ]);

        $candidate->update([
            'candidate_id' => $request->candidate_id,
            'full_name'    => $request->full_name,
            'gender'       => $request->gender,
            'prem_no'      => $request->prem_no,
        ]);

        return back()->with('success', "Candidate '{$candidate->full_name}' updated successfully.");
    }

    public function destroyCandidate(Candidate $candidate)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_rao' && !$user->isAdmin()) {
            abort(403);
        }

        $candidate->delete();

        return back()->with('success', "Candidate record deleted successfully.");
    }

    public function rejectCandidate(Request $request)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_rao' && !$user->isAdmin()) {
            abort(403);
        }

        $id = $request->input('id');
        $reason = $request->input('reason', 'Regional Administrative Officer requested correction.');

        $candidate = Candidate::find($id);
        if ($candidate) {
            $candidate->update([
                'status' => 'rejected',
                'rejection_reason' => $reason
            ]);
            return back()->with('success', "Candidate '{$candidate->full_name}' rejected and sent back for correction.");
        }

        return back()->with('error', "Candidate not found.");
    }
}
