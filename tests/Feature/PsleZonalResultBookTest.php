<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\User;
use App\Models\Role;
use App\Models\PslePrecalculatedEvaluation;
use App\Models\GovernanceAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PsleZonalResultBookTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guest;
    private Region $tabora;
    private Region $singida;
    private Region $iringa;
    private Region $dodoma;
    private District $district;
    private School $school;
    private ExamYear $examYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);

        $this->examYear = ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['is_active' => true]
        );

        $this->tabora = Region::factory()->create(['name' => 'TABORA']);
        $this->singida = Region::factory()->create(['name' => 'SINGIDA']);
        $this->iringa = Region::factory()->create(['name' => 'IRINGA']);
        $this->dodoma = Region::factory()->create(['name' => 'DODOMA']);

        $this->district = District::create([
            'name' => 'TABORA MC',
            'code' => 'TBMC',
            'region_id' => $this->tabora->id
        ]);

        $this->school = School::factory()->create([
            'name' => 'TABORA PRIMARY',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->tabora->id,
            'district_id' => $this->district->id,
            'ownership' => 'GOVERNMENT'
        ]);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->guest = User::factory()->create([
            'is_admin' => false,
            'portal_role' => 'editor',
            'status' => 'active',
        ]);
    }

    private function createSnapshotAndPayloads(bool $published = true, bool $hasActiveCorrection = false): int
    {
        $snapshotId = DB::table('result_snapshots')->insertGetId([
            'exam_year_id' => $this->examYear->id,
            'exam_type' => 'PSLE',
            'version' => 'v1',
            'is_active' => true,
            'is_rolled_back' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($published) {
            DB::table('psle_result_publications')->insert([
                'snapshot_id' => $snapshotId,
                'exam_year_id' => $this->examYear->id,
                'status' => 'published',
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($hasActiveCorrection) {
            DB::table('school_result_correction_batches')->insert([
                'exam_year' => 2026,
                'exam_type' => 'PSLE',
                'status' => 'open',
                'school_id' => $this->school->id,
                'reason' => 'Test Correction',
                'opened_by' => $this->admin->id,
                'opened_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed ready precalculated payloads with scope_type 'zonal' and scope_id null
        $payloads = [
            'general' => [
                'rows' => [
                    [
                        'gender' => 'FEMALE',
                        'grades' => [
                            'a' => ['t' => 10],
                            'b' => ['t' => 20],
                            'c' => ['t' => 30],
                            'd' => ['t' => 5],
                            'e' => ['t' => 2],
                        ],
                        'sat' => ['t' => 67],
                        'pass_ad' => ['t' => 65],
                    ],
                    [
                        'gender' => 'MALE',
                        'grades' => [
                            'a' => ['t' => 15],
                            'b' => ['t' => 25],
                            'c' => ['t' => 35],
                            'd' => ['t' => 8],
                            'e' => ['t' => 3],
                        ],
                        'sat' => ['t' => 86],
                        'pass_ad' => ['t' => 83],
                    ],
                ]
            ],
            'regionalwise' => [
                'rows' => [
                    [
                        'region' => 'TABORA',
                        'registered' => ['m' => 100, 'f' => 100, 't' => 200],
                        'sat' => ['m' => 86, 'f' => 67, 't' => 153],
                        'pass_ac' => ['t' => 135],
                        'grades' => ['d' => ['t' => 13], 'e' => ['t' => 5]],
                        'average_marks' => 38.50,
                        'avg_grade' => 'C',
                        'position' => 1,
                    ],
                    [
                        'region' => 'SINGIDA',
                        'registered' => ['m' => 100, 'f' => 100, 't' => 200],
                        'sat' => ['m' => 86, 'f' => 67, 't' => 153],
                        'pass_ac' => ['t' => 135],
                        'grades' => ['d' => ['t' => 13], 'e' => ['t' => 5]],
                        'average_marks' => 37.50,
                        'avg_grade' => 'C',
                        'position' => 2,
                    ]
                ]
            ],
            'councilwise' => [
                'rows' => [
                    [
                        'council' => 'TABORA MC',
                        'region' => 'TABORA',
                        'registered' => ['m' => 100, 'f' => 100, 't' => 200],
                        'sat' => ['m' => 86, 'f' => 67, 't' => 153],
                        'pass_ac' => ['t' => 135],
                        'grades' => ['d' => ['t' => 13], 'e' => ['t' => 5]],
                        'average_marks' => 38.50,
                        'avg_grade' => 'C',
                        'position' => 1,
                    ]
                ]
            ],
            'schoolwise' => [
                'rows' => [
                    [
                        'school' => 'TABORA PRIMARY',
                        'council' => 'TABORA MC',
                        'region' => 'TABORA',
                        'ownership' => 'GOVERNMENT',
                        'sat' => ['t' => 153],
                        'pass_ad' => ['t' => 148],
                        'average_marks' => 38.50,
                        'avg_grade' => 'C',
                        'position' => 1,
                    ]
                ]
            ],
            'subjectwise-result-evaluation' => [
                'rows' => [
                    [
                        'name' => 'Mathematics',
                        'sat' => 153,
                        'grade_a' => 25,
                        'grade_b' => 45,
                        'grade_c' => 65,
                        'grade_d' => 13,
                        'grade_e' => 5,
                        'avg_marks' => 38.5,
                        'average_marks' => 38.5,
                        'grade' => 'C',
                    ]
                ]
            ],
            'ownership-result-evaluation' => [
                'rows' => [
                    [
                        'ownership' => 'GOVERNMENT',
                        'schools_count' => 1,
                        'registered' => ['t' => 200],
                        'sat' => ['t' => 153],
                        'pass_ad' => ['t' => 148],
                        'grades' => ['e' => ['t' => 5]],
                        'average_marks' => 38.50,
                    ]
                ]
            ],
            'best-ten-schools' => [
                'rows' => [
                    [
                        'school' => 'TABORA PRIMARY',
                        'council' => 'TABORA MC',
                        'region' => 'TABORA',
                        'ownership' => 'GOVERNMENT',
                        'sat' => ['t' => 153],
                        'pass_ad' => ['t' => 148],
                        'average_marks' => 38.50,
                        'avg_grade' => 'C',
                        'position' => 1,
                    ]
                ]
            ],
            'least-ten-schools' => [
                'rows' => [
                    [
                        'school' => 'TABORA PRIMARY',
                        'council' => 'TABORA MC',
                        'region' => 'TABORA',
                        'ownership' => 'GOVERNMENT',
                        'sat' => ['t' => 153],
                        'pass_ad' => ['t' => 148],
                        'average_marks' => 38.50,
                        'avg_grade' => 'C',
                        'position' => 1,
                    ]
                ]
            ]
        ];

        foreach ($payloads as $key => $data) {
            PslePrecalculatedEvaluation::create([
                'exam_year' => 2026,
                'exam_type' => 'PSLE',
                'scope_type' => 'zonal',
                'scope_id' => null,
                'evaluation_key' => $key,
                'snapshot_id' => $snapshotId,
                'status' => PslePrecalculatedEvaluation::STATUS_READY,
                'data' => $data,
            ]);
        }

        return $snapshotId;
    }

    public function test_admin_can_view_zonal_result_book_preview_page(): void
    {
        $this->createSnapshotAndPayloads(published: true);

        $response = $this->actingAs($this->admin)
            ->get("/evaluations/psle/zonalwise/result-book");

        $response->assertStatus(200);
        $response->assertSee('ZONAL RESULT BOOK');
        $response->assertSee('KITABU CHA MATOKEO');
        $response->assertSee('YALIYOMO');
        $response->assertDontSee('YALIYOMO (TABLE OF CONTENTS)');
        $response->assertSee('OFISI YA WAZIRI MKUU');
        $response->assertSee('WASTANI wa Alama');
        $response->assertDontSee('GPA');

        // Check audit log for view event
        $this->assertTrue(
            GovernanceAuditLog::where('action', 'result_book_viewed')
                ->where('user_id', $this->admin->id)
                ->exists()
        );
    }

    public function test_admin_can_download_zonal_result_book_pdf(): void
    {
        $this->createSnapshotAndPayloads(published: true);

        $response = $this->actingAs($this->admin)
            ->get("/evaluations/psle/zonalwise/result-book/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Check audit log for download event
        $this->assertTrue(
            GovernanceAuditLog::where('action', 'result_book_downloaded')
                ->where('user_id', $this->admin->id)
                ->exists()
        );
    }

    public function test_guest_cannot_view_if_not_published(): void
    {
        $this->createSnapshotAndPayloads(published: false);

        $response = $this->actingAs($this->guest)
            ->get("/evaluations/psle/zonalwise/result-book");

        $response->assertStatus(403);
    }

    public function test_admin_can_override_settings_and_see_them_in_view(): void
    {
        $this->createSnapshotAndPayloads(published: true);

        $response = $this->actingAs($this->admin)
            ->get("/evaluations/psle/zonalwise/result-book?reo_name=Ndg.+Salum+Mzee&marking_center=Tabora+Boys+Secondary");

        $response->assertStatus(200);
        $response->assertSee('Ndg. Salum Mzee');
        $response->assertSee('Tabora Boys Secondary');
    }

    public function test_guest_cannot_view_if_correction_batch_is_active(): void
    {
        $this->createSnapshotAndPayloads(published: true, hasActiveCorrection: true);

        $response = $this->actingAs($this->guest)
            ->get("/evaluations/psle/zonalwise/result-book");

        $response->assertStatus(403);
    }

    public function test_zonalwise_index_card_shows_ready_status(): void
    {
        $this->createSnapshotAndPayloads(published: true);

        $response = $this->actingAs($this->admin)
            ->get("/evaluations/psle/zonalwise");

        $response->assertStatus(200);
        $response->assertSee('ZONAL RESULT BOOK / KITABU CHA MATOKEO');
        $response->assertSee('ready');
    }

    public function test_pdf_generation_falls_back_to_helvetica_when_poppins_font_files_are_missing(): void
    {
        $this->createSnapshotAndPayloads(published: true);

        $fontDir = app_path('Support/Pdf/font');
        $renamedFiles = [];
        $filesToRename = ['poppins.php', 'poppinsb.php', 'poppinsi.php', 'poppinsbi.php'];

        foreach ($filesToRename as $file) {
            $path = $fontDir . '/' . $file;
            if (file_exists($path)) {
                $tempPath = $path . '.bak';
                rename($path, $tempPath);
                $renamedFiles[$path] = $tempPath;
            }
        }

        try {
            $response = $this->actingAs($this->admin)
                ->get("/evaluations/psle/zonalwise/result-book/pdf");

            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/pdf');
        } finally {
            foreach ($renamedFiles as $original => $temp) {
                if (file_exists($temp)) {
                    rename($temp, $original);
                }
            }
        }
    }
}
