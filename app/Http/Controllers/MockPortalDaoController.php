<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\School;
use App\Models\Candidate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class MockPortalDaoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Ensure only DAO can access this page
        if ($user->portal_role !== 'mock_dao' && !$user->isAdmin()) {
            return redirect()->route('mock-portal.welcome');
        }

        $districtCouncilId = $user->district_council_id;

        if (!$districtCouncilId && $user->isAdmin()) {
            $districtCouncilId = \App\Models\DistrictCouncil::has('schools')->first()?->id;
        }

        if ($districtCouncilId) {
            $headteachersCount = User::where('district_council_id', $districtCouncilId)
                ->where('portal_role', 'mock_headteacher')
                ->count();

            $schoolsCount = School::where('council_id', $districtCouncilId)->count();

            $candidatesCount = Candidate::whereHas('school', function ($q) use ($districtCouncilId) {
                $q->where('council_id', $districtCouncilId);
            })->count();

            $stats = [
                'users_count'      => $headteachersCount,
                'schools_count'    => $schoolsCount,
                'candidates_count' => $candidatesCount,
                'errors_count'     => 0,
            ];
        } else {
        $stats = [
                'users_count'      => 0,
                'schools_count'    => 0,
                'candidates_count' => 0,
                'errors_count'     => 0,
            ];
        }

        $tab = $request->get('tab', 'schools');
        $search = $request->get('search');

        // Schools Query
        $schoolsQuery = $this->buildDaoSchoolsQuery($districtCouncilId, $tab === 'schools' ? $search : null);
        $schools = $schoolsQuery->orderBy('name')->paginate(20, ['*'], 'schools_page')->withQueryString();

        // Headteachers Query
        $headteachersQuery = User::where('portal_role', 'mock_headteacher')
            ->with('school');

        if ($districtCouncilId) {
            $headteachersQuery->where('district_council_id', $districtCouncilId);
        } else {
            $headteachersQuery->whereRaw('1=0');
        }

        if ($tab === 'users' && $search) {
            $headteachersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhereHas('school', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                  });
            });
        }
        $headteachers = $headteachersQuery->orderBy('name')->paginate(20, ['*'], 'users_page')->withQueryString();

        // Headteacher Stats
        $userStats = [
            'total' => User::where('district_council_id', $districtCouncilId)->where('portal_role', 'mock_headteacher')->count(),
            'male' => User::where('district_council_id', $districtCouncilId)->where('portal_role', 'mock_headteacher')->where('gender', 'M')->count(),
            'female' => User::where('district_council_id', $districtCouncilId)->where('portal_role', 'mock_headteacher')->where('gender', 'F')->count(),
        ];

        // Candidates Query
        $candidatesQuery = Candidate::with('school');
        $schoolFilter = $request->get('school_id');

        if ($districtCouncilId) {
            $candidatesQuery->whereHas('school', function($q) use ($districtCouncilId) {
                $q->where('council_id', $districtCouncilId);
            });
        } else {
            $candidatesQuery->whereRaw('1=0');
        }

        if ($tab === 'candidates') {
            if ($schoolFilter) {
                $candidatesQuery->where('school_id', $schoolFilter);
            }
            if ($search) {
                $candidatesQuery->where(function($q) use ($search) {
                    $q->where('full_name', 'LIKE', "%{$search}%")
                      ->orWhere('prem_no', 'LIKE', "%{$search}%")
                      ->orWhereHas('school', function($sq) use ($search) {
                          $sq->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('code', 'LIKE', "%{$search}%");
                      });
                });
            }
        }
        $candidatesList = $candidatesQuery->orderBy('full_name')->paginate(20, ['*'], 'candidates_page')->withQueryString();

        // Schools for filter
        $districtSchools = School::where('council_id', $districtCouncilId)
            ->whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH'])
            ->orderBy('name')
            ->get();

        // Candidate Stats
        $candidateStats = [
            'total' => Candidate::whereHas('school', function($q) use ($districtCouncilId) {
                $q->where('council_id', $districtCouncilId);
            })->count(),
            'male' => Candidate::whereHas('school', function($q) use ($districtCouncilId) {
                $q->where('council_id', $districtCouncilId);
            })->where('gender', 'M')->count(),
            'female' => Candidate::whereHas('school', function($q) use ($districtCouncilId) {
                $q->where('council_id', $districtCouncilId);
            })->where('gender', 'F')->count(),
        ];

        // Errors Detection (Real-time validation)
        $rawErrors = collect();
        
        if ($districtCouncilId) {
            // 1. Candidates with missing PREM numbers
            $missingPrem = Candidate::whereHas('school', function($q) use ($districtCouncilId) {
                $q->where('council_id', $districtCouncilId);
            })->where(function($q) {
                $q->whereNull('prem_no')->orWhere('prem_no', '');
            })->get();
            
            foreach($missingPrem as $mp) {
                $rawErrors->push([
                    'id' => $mp->id,
                    'type' => 'Critical',
                    'category' => 'Candidate',
                    'description' => "Missing PREM Number for student: {$mp->full_name}",
                    'school' => $mp->school?->name ?? 'Unknown School',
                    'action' => 'Registration Required'
                ]);
            }

            // 2. Schools with zero candidates
            $emptySchools = School::where('council_id', $districtCouncilId)
                ->whereDoesntHave('candidates')
                ->get();
            
            foreach($emptySchools as $es) {
                $rawErrors->push([
                    'id' => $es->id,
                    'type' => 'Warning',
                    'category' => 'School',
                    'description' => "No candidates registered for this school.",
                    'school' => $es->name,
                    'action' => 'Review Required'
                ]);
            }
        }

        // Apply Filtering & Search to Errors
        $errorSearch = $request->input('error_search');
        $errorCat = $request->input('error_category');

        $errorsList = $rawErrors->filter(function($item) use ($errorSearch, $errorCat) {
            $matchSearch = true;
            $matchCat = true;

            if ($errorSearch) {
                $searchLower = strtolower($errorSearch);
                $matchSearch = str_contains(strtolower($item['description']), $searchLower) || 
                              str_contains(strtolower($item['school']), $searchLower);
            }

            if ($errorCat) {
                $matchCat = ($item['category'] === $errorCat);
            }

            return $matchSearch && $matchCat;
        });

        $errorStats = [
            'total' => $rawErrors->count(),
            'critical' => $rawErrors->where('type', 'Critical')->count(),
            'warning' => $rawErrors->where('type', 'Warning')->count(),
            'candidate' => $rawErrors->where('category', 'Candidate')->count(),
            'school' => $rawErrors->where('category', 'School')->count(),
        ];

        $stats['errors_count'] = $errorStats['total'];

        // Paginate Errors List
        $perPage = 20;
        $page = $request->input('errors_page', 1);
        $errorsList = new LengthAwarePaginator(
            $errorsList->forPage($page, $perPage),
            $errorsList->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'errors_page']
        );

        $district = $districtCouncilId ? \App\Models\DistrictCouncil::with('region')->find($districtCouncilId) : null;

        return view('mock-portal.dao.dashboard', compact('stats', 'user', 'schools', 'headteachers', 'candidatesList', 'candidateStats', 'userStats', 'district', 'tab', 'districtSchools', 'errorsList', 'errorStats'));
    }

    public function schoolsPdfReport(Request $request)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_dao' && !$user->isAdmin()) {
            abort(403);
        }

        $districtCouncilId = $user->district_council_id;
        if (!$districtCouncilId && $user->isAdmin()) {
            $districtCouncilId = \App\Models\DistrictCouncil::has('schools')->first()?->id;
        }

        if (!$districtCouncilId) {
            abort(404, 'No district is assigned to this DAO account.');
        }

        $district = \App\Models\DistrictCouncil::with('region')->findOrFail($districtCouncilId);
        $search = trim((string) $request->query('search', ''));
        $schools = $this->buildDaoSchoolsQuery($districtCouncilId, $search === '' ? null : $search)
            ->orderBy('name')
            ->get();

        $districtCode = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $district->name ?? 'DIS'), 0, 3));
        $barcodePayload = 'STANDARD SEVEN TASIDO-' . $districtCode . '-' . now()->format('Ymd-His') . '-WEB';
        $barcodeBars = $this->getCode39Bars($barcodePayload);

        $filename = 'dao_schools_report_' . strtolower(preg_replace('/[^a-z0-9]+/i', '_', $district->name ?? 'district')) . '_' . now()->format('Ymd_His') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('mock-portal.dao.schools-report-pdf', compact('district', 'schools', 'search', 'barcodePayload', 'barcodeBars'))
            ->setPaper('a4', 'portrait')
            ->setOption('isPhpEnabled', true);

        return $pdf->download($filename);
    }

    public function downloadCalZip(Request $request)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $user = Auth::user();
        if ($user->portal_role !== 'mock_dao' && !$user->isAdmin()) {
            abort(403);
        }

        $districtCouncilId = $user->district_council_id;
        if (!$districtCouncilId && $user->isAdmin()) {
            $districtCouncilId = \App\Models\DistrictCouncil::has('schools')->first()?->id;
        }

        if (!$districtCouncilId) {
            return back()->with('error', 'No district is assigned to this DAO account.');
        }

        $district = \App\Models\DistrictCouncil::with('region')->findOrFail($districtCouncilId);
        $schools = $this->buildDaoSchoolsQuery($districtCouncilId)
            ->with(['council', 'region', 'district'])
            ->orderBy('code')
            ->get();

        if ($schools->isEmpty()) {
            return back()->with('error', 'No schools found under your district.');
        }

        $subjects = \App\Models\Subject::whereHas('examType', function ($q) {
            $q->where('code', 'PSLE');
        })->orderBy('code')->get();

        if ($subjects->isEmpty()) {
            return back()->with('error', 'PSLE Subjects not found. Please contact administrator.');
        }

        $districtName = $this->sanitizeFileName($district->name ?? 'district');
        $zipFileName = strtoupper($districtName) . '_DAO_CAL_FILES_MOCK_2026_' . now()->format('Ymd_His') . '.zip';
        $tempZip = tempnam(sys_get_temp_dir(), 'dao_cal_');
        $zip = new \ZipArchive();
        $emblemData = $this->getEmblemDataUri();

        if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Failed to generate CAL zip file. Please try again.');
        }

        $includedSchools = 0;
        $skippedSchools = 0;
        $candidateCount = 0;
        $fileCount = 0;
        $candidatesBySchool = Candidate::whereIn('school_id', $schools->pluck('id'))
            ->orderBy('candidate_id')
            ->get()
            ->groupBy('school_id');

        try {
            foreach ($schools as $school) {
                $candidates = $candidatesBySchool->get($school->id, collect());

                if ($candidates->isEmpty()) {
                    $skippedSchools++;
                    continue;
                }

                $includedSchools++;
                $candidateCount += $candidates->count();

                foreach ($subjects as $subject) {
                    $pdfFileName = $this->sanitizeFileName(
                        ($school->code ?? 'SCH') . '_' . preg_replace('/[^a-z0-9]+/i', '_', $subject->name) . '_CAL.pdf'
                    );

                    $schoolCodeStr = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $school->code ?? 'SCH'), 0, 8));
                    $subjectCodeStr = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $subject->code ?? 'SUB'), 0, 8));
                    $barcodePayload = 'CAL-' . $schoolCodeStr . '-' . $subjectCodeStr . '-' . now()->format('Ymd-His');
                    $barcodeBars = $this->getCode39Bars($barcodePayload);

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('mock-portal.headteacher.cal-report-pdf', compact('school', 'candidates', 'subject', 'barcodePayload', 'barcodeBars', 'emblemData'))
                        ->setPaper('a4', 'portrait')
                        ->setOption('isPhpEnabled', true);

                    $zip->addFromString($pdfFileName, $pdf->output());
                    $fileCount++;
                }
            }

            $zip->close();
        } catch (\Throwable $e) {
            $zip->close();
            if (is_file($tempZip)) {
                @unlink($tempZip);
            }

            Log::error('DAO CAL zip generation failed', [
                'user_id' => $user->id,
                'district_council_id' => $districtCouncilId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to generate CAL zip file. Please try again.');
        }

        if ($fileCount === 0) {
            if (is_file($tempZip)) {
                @unlink($tempZip);
            }

            return back()->with('error', 'No registered candidates found for schools under your district.');
        }

        Log::info('DAO downloaded CAL zip', [
            'action' => 'dao_download_cal_zip',
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->portal_role,
            'district_council_id' => $districtCouncilId,
            'district_council_name' => $district->name,
            'schools_included' => $includedSchools,
            'schools_skipped' => $skippedSchools,
            'candidates_included' => $candidateCount,
            'files_included' => $fileCount,
        ]);

        return response()->download($tempZip, $zipFileName)->deleteFileAfterSend(true);
    }

    public function storeSchool(Request $request)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_dao' && !$user->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:20|unique:schools,code',
            'school_type' => 'required|in:PRIMARY,SECONDARY,BOTH',
            'ownership'   => 'required|in:GOVERNMENT,NON-GOVERNMENT',
            'council_id'  => 'required|exists:district_councils,id',
            'region_id'   => 'required|exists:regions,id',
        ]);

        School::create([
            'name'            => strtoupper($request->name),
            'code'            => strtoupper($request->code),
            'school_type'     => $request->school_type,
            'ownership'       => $request->ownership,
            'council_id'      => $request->council_id,
            'region_id'       => $request->region_id,
            'is_active'       => true,
            'source_system'   => 'MOCK',
            'education_level' => $request->school_type === 'PRIMARY' ? 'PRIMARY' : 'SECONDARY',
        ]);

        return back()->with('success', 'School successfully added to your district.');
    }

    public function updateSchool(Request $request, School $school)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_dao' && !$user->isAdmin()) {
            abort(403);
        }

        // Ensure DAO can only edit schools in their district
        if (!$user->isAdmin() && $school->council_id !== $user->district_council_id) {
            abort(403);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:20|unique:schools,code,' . $school->id,
            'school_type' => 'required|in:PRIMARY,SECONDARY,BOTH',
            'ownership'   => 'required|in:GOVERNMENT,NON-GOVERNMENT',
        ]);

        $school->update([
            'name'        => strtoupper($request->name),
            'code'        => strtoupper($request->code),
            'school_type' => $request->school_type,
            'ownership'   => $request->ownership,
            'education_level' => $request->school_type === 'PRIMARY' ? 'PRIMARY' : 'SECONDARY',
        ]);

        return back()->with('success', 'School updated successfully.');
    }

    public function destroySchool(School $school)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_dao' && !$user->isAdmin()) {
            abort(403);
        }

        // Ensure DAO can only delete schools in their district
        if (!$user->isAdmin() && $school->council_id !== $user->district_council_id) {
            abort(403);
        }

        $school->delete();

        return back()->with('success', 'School has been successfully deleted.');
    }

    public function rejectError(Request $request)
    {
        $id = $request->input('id');
        $category = $request->input('category');
        $reason = $request->input('reason', 'Data inconsistent or missing required information.');

        if ($category === 'Candidate') {
            $candidate = Candidate::find($id);
            if ($candidate) {
                $candidate->status = 'rejected';
                $candidate->rejection_reason = $reason;
                $candidate->save();
                return back()->with('success', "Candidate '{$candidate->full_name}' has been rejected and returned for correction.");
            }
        } elseif ($category === 'School') {
            $school = School::find($id);
            if ($school) {
                // For schools, we'll just flash a message since there's no status column yet
                return back()->with('success', "Notification sent to '{$school->name}' regarding registration errors.");
            }
        }

        return back()->with('error', "Unable to process rejection at this time.");
    }

    private function buildDaoSchoolsQuery(?int $districtCouncilId, ?string $search = null)
    {
        $query = School::whereIn('school_type', ['PRIMARY', 'SECONDARY', 'BOTH'])
            ->withCount('candidates');

        if ($districtCouncilId) {
            $query->where('council_id', $districtCouncilId);
        } else {
            $query->whereRaw('1=0');
        }

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    private function sanitizeFileName(string $value): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim($value));
        $name = trim($name, '._-');

        return $name !== '' ? $name : 'CAL_FILE';
    }

    private function getEmblemDataUri(): string
    {
        $paths = [
            base_path('images/emblem.png'),
            public_path('images/emblem.png'),
            base_path('../public_html/images/emblem.png'),
            base_path('../../public_html/images/emblem.png'),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
            }
        }

        return '';
    }

    private function getCode39Bars(string $value): array
    {
        $patterns = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        ];
        $code = '*' . strtoupper($value) . '*';
        $bars = [];
        foreach (str_split($code) as $char) {
            $pattern = $patterns[$char] ?? $patterns['-'];
            foreach (str_split($pattern) as $index => $bar) {
                $isBlack = ($index % 2 === 0);
                $isWide = ($bar === 'w');
                $width = $isWide ? 1.25 : 0.5;
                $bars[] = ['color' => $isBlack ? '#0b1d3a' : 'transparent', 'width' => $width];
            }
            $bars[] = ['color' => 'transparent', 'width' => 0.5];
        }
        return $bars;
    }

}
