<?php

namespace App\Http\Controllers;

use App\Models\DashboardAnnouncement;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Candidate;
use App\Models\ExamType;
use App\Models\Combination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $events = collect();
        $news = collect();

        if (Schema::hasTable('dashboard_announcements')) {
            $events = DashboardAnnouncement::query()
                ->active()
                ->events()
                ->orderBy('sort_order')
                ->orderBy('publish_date')
                ->limit(3)
                ->get()
                ->map(fn (DashboardAnnouncement $announcement) => $this->withNormalizedLink($announcement));

            $news = DashboardAnnouncement::query()
                ->active()
                ->news()
                ->orderBy('sort_order')
                ->orderByDesc('publish_date')
                ->limit(3)
                ->get()
                ->map(fn (DashboardAnnouncement $announcement) => $this->withNormalizedLink($announcement));
        }

        return view('dashboard.home', compact('events', 'news'));
    }

    public function oldIndex()
    {
        $stats = [
            'regions' => Region::count(),
            'schools' => School::count(),
            'candidates' => Candidate::count(),
            'exam_types' => ExamType::count(),
        ];

        return view('dashboard.index', $stats);
    }

    /**
     * Show ACSEE exam dashboard
     */
    public function acseeExam()
    {
        return view('dashboard.exam-acsee');
    }

    /**
     * Get ACSEE candidates with filters
     */
    public function getAcseeCandicates(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 15);
        $search = $request->get('search', '');
        $schoolId = $request->get('school_id');
        $districtId = $request->get('district_id');
        $regionId = $request->get('region_id');

        $query = Candidate::where('exam_type', 'ACSEE')
            ->with('school.district.region')
            ->orderBy('candidate_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($districtId) {
            $query->whereHas('school', function ($q) use ($districtId) {
                $q->where('district_id', $districtId);
            });
        }

        if ($regionId) {
            $query->whereHas('school.district', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }

        $candidates = $query->paginate($pageSize);

        $data = $candidates->map(function ($candidate) {
            return [
                'id' => $candidate->id,
                'candidate_id' => $candidate->candidate_id,
                'full_name' => $candidate->full_name,
                'gender' => $candidate->gender,
                'combination' => $candidate->combination,
                'school_name' => $candidate->school?->name ?? '-',
                'school_id' => $candidate->school_id,
                'district_id' => $candidate->school?->district_id,
                'district_name' => $candidate->school?->district?->name ?? '-',
                'region_id' => $candidate->school?->district?->region_id,
                'region_name' => $candidate->school?->district?->region?->name ?? '-',
                'allocated_subjects' => $this->getCombinationSubjects($candidate->combination),
                'exam_type' => $candidate->exam_type,
            ];
        });

        return response()->json([
            'candidates' => $data,
            'pagination' => [
                'page' => $candidates->currentPage(),
                'page_size' => $pageSize,
                'total_count' => $candidates->total(),
                'total_pages' => $candidates->lastPage(),
                'has_previous' => $candidates->currentPage() > 1,
                'has_next' => $candidates->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get filter options for ACSEE candidates
     */
    public function getAcseeFilterData()
    {
        $regions = Region::whereHas('districts.schools.candidates', function ($q) {
            $q->where('exam_type', 'ACSEE');
        })->orderBy('name')->get(['id', 'name']);

        $districts = District::whereHas('schools.candidates', function ($q) {
            $q->where('exam_type', 'ACSEE');
        })->orderBy('name')->get(['id', 'name', 'region_id']);

        $schools = School::whereHas('candidates', function ($q) {
            $q->where('exam_type', 'ACSEE');
        })->with('district')
         ->orderBy('name')
         ->get(['id', 'name', 'district_id']);

        return response()->json([
            'regions' => $regions,
            'districts' => $districts,
            'schools' => $schools,
        ]);
    }

    /**
     * Get subjects for a combination
     */
    private function getCombinationSubjects($combinationCode)
    {
        if (!$combinationCode) {
            return [];
        }

        $combination = Combination::where('code', $combinationCode)
            ->with('subjects')
            ->first();

        if (!$combination) {
            return [];
        }

        return $combination->subjects->map(function ($subject) {
            return [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
            ];
        })->toArray();
    }

    private function withNormalizedLink(DashboardAnnouncement $announcement): DashboardAnnouncement
    {
        $announcement->setAttribute('resolved_link_url', $this->normalizeAnnouncementLink($announcement->link_url));

        return $announcement;
    }

    private function normalizeAnnouncementLink(?string $link): string
    {
        $link = trim((string) $link);
        if ($link === '') {
            return '#';
        }

        $path = parse_url($link, PHP_URL_PATH);
        if (is_string($path) && Str::startsWith($path, ['/r/', '/results/'])) {
            $query = parse_url($link, PHP_URL_QUERY);

            return $query ? $path . '?' . $query : $path;
        }

        return $link;
    }
}
