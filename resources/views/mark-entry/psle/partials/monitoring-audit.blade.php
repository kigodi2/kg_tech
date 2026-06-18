                <div class="adm-breadcrumb">
                    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Monitoring & Audit</span>
                </div>

                <div class="adm-page-header">
                    <h1 class="adm-page-title">Monitoring & Audit</h1>
                    <p class="adm-page-desc">Track real-time officer activity, mark-entry progress, and system audit logs for the PSLE portal.</p>
                </div>

                <!-- Summary Cards -->
                <div class="adm-stats">
                    <div class="adm-stat">
                        <div class="adm-stat-label">Active Officers</div>
                        <div class="adm-stat-value" style="color: #fff;">{{ number_format($monitoringSummary['active_officers'] ?? 0) }}</div>
                        <i class="fas fa-user-tie adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Marks Today</div>
                        <div class="adm-stat-value" style="color: var(--tz-green);">{{ number_format($monitoringSummary['marks_today'] ?? 0) }}</div>
                        <i class="fas fa-calendar-check adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Pending Marks</div>
                        <div class="adm-stat-value" style="color: #ff7b7b;">{{ number_format($monitoringSummary['pending_marks'] ?? 0) }}</div>
                        <i class="fas fa-clock-rotate-left adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Submitted Batches</div>
                        <div class="adm-stat-value" style="color: var(--tz-blue);">{{ number_format($monitoringSummary['submitted_batches'] ?? 0) }}</div>
                        <i class="fas fa-file-export adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Validation Runs</div>
                        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ number_format($monitoringSummary['validation_runs'] ?? 0) }}</div>
                        <i class="fas fa-shield-halved adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Audit Events</div>
                        <div class="adm-stat-value" style="color: #fff;">{{ number_format($monitoringSummary['audit_events'] ?? 0) }}</div>
                        <i class="fas fa-list-check adm-stat-icon"></i>
                    </div>
                </div>

                <!-- Scope Filters -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Monitoring Scope</div>
                    </div>
                    <form method="GET" action="{{ url()->current() }}" class="adm-filters">
                        <input type="hidden" name="view" value="monitoring-audit">
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Exam Year</label>
                            <select name="exam_year_id" class="adm-select" onchange="this.form.submit()">
                                @foreach($examYears ?? [] as $yr)
                                    <option value="{{ $yr->id }}" {{ ($activeFilters['exam_year_id'] ?? '') == $yr->id ? 'selected' : '' }}>{{ $yr->year_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Region</label>
                            <select name="region_id" class="adm-select" onchange="this.form.submit()" {{ !empty($allowedRegionId) ? 'disabled' : '' }}>
                                @if(empty($allowedRegionId))
                                    <option value="">All Regions</option>
                                @endif
                                @foreach($regions ?? [] as $reg)
                                    <option value="{{ $reg->id }}" {{ ($activeFilters['region_id'] ?? '') == $reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                                @endforeach
                            </select>
                            @if(!empty($allowedRegionId))
                                <input type="hidden" name="region_id" value="{{ $allowedRegionId }}">
                            @endif
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">District</label>
                            <select name="district_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Districts</option>
                                @foreach($districts ?? [] as $dist)
                                    <option value="{{ $dist->id }}" {{ ($activeFilters['district_id'] ?? '') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Primary School</label>
                            <select name="school_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Schools</option>
                                @foreach($schools ?? [] as $sch)
                                    <option value="{{ $sch->id }}" {{ ($activeFilters['school_id'] ?? '') == $sch->id ? 'selected' : '' }}>{{ $sch->code }} - {{ $sch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Subject</label>
                            <select name="subject_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Subjects</option>
                                @foreach($psleSubjects as $subj)
                                    <option value="{{ $subj->id }}" {{ ($activeFilters['subject_id'] ?? '') == $subj->id ? 'selected' : '' }}>{{ $subj->code }} - {{ $subj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Officer Productivity -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">{{ $isMarkOfficer && !$isAdmin ? 'My Productivity' : 'Officer Productivity' }}</div>
                    </div>
                    <div class="adm-card-body" style="padding: 0;">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>Officer</th>
                                    <th>Region</th>
                                    <th style="text-align: center;">Assigned</th>
                                    <th style="text-align: center;">Entered</th>
                                    <th style="text-align: center;">Pending</th>
                                    <th>Last Activity</th>
                                    <th style="text-align: right;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productivityStats as $p)
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: #fff;">{{ $p->officer }}</div>
                                            <div style="font-size: 0.7rem; color: #94a3b8;">Mark Entry Officer</div>
                                        </td>
                                        <td>{{ $p->region }}</td>
                                        <td style="text-align: center;">{{ number_format($p->assigned_candidates) }}</td>
                                        <td style="text-align: center; color: var(--tz-green);">{{ number_format($p->entered_marks) }}</td>
                                        <td style="text-align: center; color: #ff7b7b;">
                                            @if($p->pending_marks === null)
                                                <span style="color: #94a3b8;">—</span>
                                            @else
                                                {{ number_format($p->pending_marks) }}
                                            @endif
                                        </td>
                                        <td>{{ $p->last_active }}</td>
                                        <td style="text-align: right;">
                                            @php
                                                $prog = $p->assigned_candidates > 0 ? ($p->entered_marks / $p->assigned_candidates) * 100 : 0;
                                            @endphp
                                            @if(!$p->has_assignment)
                                                <span class="badge" style="background-color: rgba(239, 68, 68, 0.15) !important; color: #f87171 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; white-space: nowrap !important;">Assignment Missing</span>
                                            @elseif($prog >= 100)
                                                <span class="badge badge-green" style="white-space: nowrap !important;">Completed</span>
                                            @elseif($prog > 0)
                                                <span class="badge badge-yellow" style="white-space: nowrap !important;">In Progress</span>
                                            @else
                                                <span class="badge badge-outline" style="white-space: nowrap !important;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">No officer productivity data found for this scope.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Regional Progress -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Regional Progress Monitoring</div>
                    </div>
                    <div class="adm-card-body" style="padding: 0;">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>District/Council</th>
                                    <th style="text-align: center;">Schools</th>
                                    <th style="text-align: center;">Candidates</th>
                                    <th style="text-align: center;">Entered</th>
                                    <th style="text-align: center;">Outliers</th>
                                    <th>Progress</th>
                                    <th style="text-align: right;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($regionalProgress as $rp)
                                    @if(str_contains(strtoupper((string)($rp->district ?? '')), 'UNASSIGNED') || str_contains(strtoupper((string)($rp->district ?? '')), 'CSEE') || str_contains(strtoupper((string)($rp->district ?? '')), 'ACSEE'))
                                        @continue
                                    @endif
                                    <tr>
                                        <td style="font-weight: 600; color: #fff;">{{ $rp->district }}</td>
                                        <td style="text-align: center;">{{ number_format($rp->schools) }}</td>
                                        <td style="text-align: center;">{{ number_format($rp->candidates) }}</td>
                                        <td style="text-align: center; color: var(--tz-green);">{{ number_format($rp->marks_entered) }}</td>
                                        <td style="text-align: center; color: #ffb74d;">{{ number_format($rp->outliers) }}</td>
                                        <td style="width: 150px;">
                                            <div style="height: 6px; width: 100%; background: #1e293b; border-radius: 3px; overflow: hidden; margin-bottom: 4px;">
                                                <div style="height: 100%; width: {{ $rp->progress }}%; background: var(--tz-blue);"></div>
                                            </div>
                                            <div style="font-size: 0.7rem; color: #94a3b8;">{{ $rp->progress }}% complete</div>
                                        </td>
                                        <td style="text-align: right;">
                                            @if($rp->status === 'Completed')
                                                <span class="badge badge-green">Completed</span>
                                            @elseif($rp->status === 'In Progress')
                                                <span class="badge badge-yellow">In Progress</span>
                                            @else
                                                <span class="badge badge-outline">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">No regional progress data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="adm-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Recent Activity -->
                    <div class="adm-card">
                        <div class="adm-card-head">
                            <div class="adm-card-title">Recent Activity</div>
                        </div>
                        <div class="adm-card-body" style="padding: 0;">
                            <table class="adm-table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentActivity as $act)
                                        <tr>
                                            <td style="font-size: 0.75rem;">{{ $act->created_at->format('H:i:s') }}</td>
                                            <td>
                                                <div style="font-weight: 600; color: #fff; font-size: 0.85rem;">{{ $act->actor->name ?? 'System' }}</div>
                                            </td>
                                            <td style="font-size: 0.85rem;">
                                                <span style="color: {{ $act->status === 'success' ? 'var(--tz-green)' : ($act->status === 'failed' ? '#ff7b7b' : 'var(--tz-yellow)') }}">
                                                    {{ $act->action }}
                                                </span>
                                                <div style="font-size: 0.7rem; color: #94a3b8;">{{ Str::limit($act->message, 40) }}</div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 20px; color: #94a3b8;">No recent activity logs.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Batch Activity -->
                    <div class="adm-card">
                        <div class="adm-card-head">
                            <div class="adm-card-title">Recent Batches</div>
                        </div>
                        <div class="adm-card-body" style="padding: 0;">
                            <table class="adm-table">
                                <thead>
                                    <tr>
                                        <th>Batch</th>
                                        <th>School/Subject</th>
                                        <th style="text-align: right;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($batchActivity as $b)
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600; color: #fff; font-size: 0.85rem;">{{ $b->batch_code }}</div>
                                                <div style="font-size: 0.7rem; color: #94a3b8;">{{ $b->updated_at->diffForHumans() }}</div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.85rem;">{{ $b->school->code }} - {{ Str::limit($b->school->name, 20) }}</div>
                                                <div style="font-size: 0.7rem; color: #94a3b8;">{{ $b->subject->name }}</div>
                                            </td>
                                            <td style="text-align: right;">
                                                @php
                                                    $bBadge = match($b->status) {
                                                        'draft' => 'badge-outline',
                                                        'validated' => 'badge-blue',
                                                        'submitted' => 'badge-yellow',
                                                        'approved' => 'badge-green',
                                                        'locked' => 'badge-green',
                                                        'rejected' => 'badge-red',
                                                        default => 'badge-outline'
                                                    };
                                                @endphp
                                                <span class="badge {{ $bBadge }}">{{ ucfirst($b->status) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 20px; color: #94a3b8;">No recent batch activity.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Audit Trail Preview -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Audit Trail Preview</div>
                        <div class="adm-card-head-actions">
                            <button class="btn btn-outline btn-sm" disabled title="Full export available in next update">
                                <i class="fas fa-file-csv"></i> Export Audit CSV
                            </button>
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 0;">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Region</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th style="text-align: right;">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditTrail as $audit)
                                    <tr>
                                        <td style="font-size: 0.8rem;">{{ $audit->time->format('d M Y, H:i') }}</td>
                                        <td>
                                            <div style="font-weight: 600; color: #fff;">{{ $audit->user }}</div>
                                            <div style="font-size: 0.7rem; color: #94a3b8;">{{ $audit->role }}</div>
                                        </td>
                                        <td>{{ $audit->region }}</td>
                                        <td><span class="badge badge-outline">{{ strtoupper($audit->action) }}</span></td>
                                        <td style="font-size: 0.85rem;">{{ $audit->details }}</td>
                                        <td style="text-align: right; font-family: monospace; font-size: 0.75rem; color: #94a3b8;">{{ $audit->ip }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">No audit trail records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Suspicious Activity (Placeholders for logic) -->
                <div class="adm-card" style="margin-top: 20px; border-left: 4px solid var(--tz-yellow);">
                    <div class="adm-card-head">
                        <div class="adm-card-title"><i class="fas fa-triangle-exclamation" style="color: var(--tz-yellow); margin-right: 8px;"></i> Security Observations</div>
                    </div>
                    <div class="adm-card-body">
                        <div class="empty-state" style="padding: 20px;">
                            <div class="empty-title" style="font-size: 1rem;">No suspicious activity detected.</div>
                            <div class="empty-desc">The system is currently monitoring mark entry patterns and access attempts. All parameters are within normal ranges.</div>
                        </div>
                    </div>
                </div>
