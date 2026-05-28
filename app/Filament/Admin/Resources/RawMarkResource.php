<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RawMarkResource\Pages;
use App\Models\DistrictCouncil;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RawMarkResource extends Resource
{
    protected static ?string $model = RawMark::class;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    protected static ?string $slug = 'raw-marks';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Mark Entry';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Candidate & Subject Information')
                    ->schema([
                        Forms\Components\TextInput::make('candidate_index_number')
                            ->label('Index Number')
                            ->readOnly(),

                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->readOnly(),

                        Forms\Components\Select::make('candidate_id')
                            ->label('Candidate')
                            ->relationship('candidate', 'full_name')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Placeholder::make('exam_type')
                            ->label('Exam Type')
                            ->content(fn (?RawMark $record): string => $record?->batch?->examType?->code ?? '—'),

                        Forms\Components\Placeholder::make('school')
                            ->label('School')
                            ->content(fn (?RawMark $record): string => $record?->batch?->school?->name ?? $record?->candidate?->school?->name ?? '—'),

                        Forms\Components\Placeholder::make('council')
                            ->label('Council')
                            ->content(fn (?RawMark $record): string => $record?->batch?->school?->council?->name ?? $record?->candidate?->school?->council?->name ?? '—'),

                        Forms\Components\Placeholder::make('region')
                            ->label('Region')
                            ->content(fn (?RawMark $record): string => $record?->batch?->school?->region?->name ?? $record?->candidate?->school?->region?->name ?? '—'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Individual Paper Marks (0-100, increments of 0.5)')
                    ->schema([
                        Forms\Components\TextInput::make('paper_1_marks')
                            ->label('Paper 1')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('paper_2_marks')
                            ->label('Paper 2')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('paper_3_marks')
                            ->label('Paper 3')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('practical_marks')
                            ->label('Practical')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('project_marks')
                            ->label('Project')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('has_errors')
                            ->label('Has Errors')
                            ->disabled(),

                        Forms\Components\Textarea::make('error_messages')
                            ->label('Error Messages')
                            ->disabled()
                            ->formatStateUsing(fn($state) => is_array($state) ? implode("\n", $state) : $state),

                        Forms\Components\Toggle::make('is_locked')
                            ->label('Locked')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'batch.examType',
                'batch.school.council',
                'batch.school.region',
                'candidate.school',
                'subject.examType',
                'verification',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('candidate_index_number')
                    ->label('Index')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Candidate')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('candidate.combination')
                    ->label('Comb')
                    ->sortable()
                    ->hidden(fn (): bool => static::isPsleSelected()),

                Tables\Columns\TextColumn::make('candidate.gender')
                    ->label('Sex')
                    ->badge()
                    ->sortable()
                    ->visible(fn (): bool => static::isPsleSelected()),

                Tables\Columns\TextColumn::make('batch.school.name')
                    ->label('School')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn (): bool => static::isPsleSelected()),

                Tables\Columns\TextColumn::make('batch.school.council.name')
                    ->label('Council')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn (): bool => static::isPsleSelected()),

                Tables\Columns\TextColumn::make('batch.school.region.name')
                    ->label('Region')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn (): bool => static::isPsleSelected()),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paper_1_marks')
                    ->label('P1')
                    ->numeric()
                    ->sortable()
                    ->hidden(fn (): bool => static::isPsleSelected()),

                Tables\Columns\TextColumn::make('psle_mark')
                    ->label('Mark')
                    ->state(fn (RawMark $record) => $record->paper_1_marks)
                    ->numeric()
                    ->visible(fn (): bool => static::isPsleSelected()),

                Tables\Columns\TextColumn::make('paper_2_marks')
                    ->label('P2')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (): bool => ! static::isPsleSelected() || static::selectedSubjectHasPaper(2)),

                Tables\Columns\TextColumn::make('paper_3_marks')
                    ->label('P3')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (): bool => ! static::isPsleSelected() || static::selectedSubjectHasPaper(3)),

                Tables\Columns\TextColumn::make('practical_marks')
                    ->label('Practical')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (): bool => ! static::isPsleSelected() || static::selectedSubjectHasPractical()),

                Tables\Columns\TextColumn::make('project_marks')
                    ->label('Project')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (): bool => ! static::isPsleSelected() || static::selectedSubjectHasProject()),

                Tables\Columns\IconColumn::make('has_errors')
                    ->label('Errors')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('batch.status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->visible(fn (): bool => static::isPsleSelected()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->label('Exam Type')
                    ->options(fn () => ExamType::orderBy('code')->pluck('code', 'id')->all())
                    ->default(fn () => static::defaultExamTypeId())
                    ->query(function (Builder $query, array $data): Builder {
                        $examTypeId = data_get($data, 'value');

                        if (! $examTypeId) {
                            return $query;
                        }

                        $examType = ExamType::find($examTypeId);

                        return $query
                            ->whereHas('batch', fn (Builder $batchQuery) => $batchQuery->where('exam_type_id', $examTypeId))
                            ->when(strtoupper((string) $examType?->code) === 'PSLE', function (Builder $psleQuery) use ($examTypeId) {
                                $psleQuery
                                    ->whereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('exam_type_id', $examTypeId))
                                    ->whereHas('candidate.school', fn (Builder $schoolQuery) => $schoolQuery->whereIn('school_type', ['PRIMARY', 'BOTH']));
                            });
                    }),

                Tables\Filters\SelectFilter::make('exam_year_id')
                    ->label('Exam Year')
                    ->options(fn () => ExamYear::orderByDesc('year_label')->pluck('year_label', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => data_get($data, 'value')
                        ? $query->whereHas('batch', fn (Builder $batchQuery) => $batchQuery->where('exam_year_id', data_get($data, 'value')))
                        : $query),

                Tables\Filters\SelectFilter::make('region_id')
                    ->label('Region')
                    ->options(fn () => Region::orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => data_get($data, 'value')
                        ? $query->whereHas('batch.school', fn (Builder $schoolQuery) => $schoolQuery->where('region_id', data_get($data, 'value')))
                        : $query),

                Tables\Filters\SelectFilter::make('council_id')
                    ->label('Council')
                    ->options(fn () => DistrictCouncil::orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => data_get($data, 'value')
                        ? $query->whereHas('batch.school', fn (Builder $schoolQuery) => $schoolQuery->where('council_id', data_get($data, 'value')))
                        : $query),

                Tables\Filters\SelectFilter::make('school_id')
                    ->label('School')
                    ->searchable()
                    ->options(fn () => School::orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => data_get($data, 'value')
                        ? $query->whereHas('batch', fn (Builder $batchQuery) => $batchQuery->where('school_id', data_get($data, 'value')))
                        : $query),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->searchable()
                    ->options(fn () => Subject::orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => data_get($data, 'value')
                        ? $query->where('subject_id', data_get($data, 'value'))
                        : $query),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'validated' => 'Validated',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'locked' => 'Locked',
                        'processed' => 'Processed',
                        'rejected' => 'Rejected',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => data_get($data, 'value')
                        ? $query->whereHas('batch', fn (Builder $batchQuery) => $batchQuery->where('status', data_get($data, 'value')))
                        : $query),

                Tables\Filters\TernaryFilter::make('has_errors')
                    ->label('Has Errors'),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['candidate_index_number', 'full_name', 'subject.name', 'batch.school.name'];
    }

    private static function isPsleSelected(): bool
    {
        $examTypeId = static::selectedExamTypeId();

        if (! $examTypeId) {
            return false;
        }

        return ExamType::whereKey($examTypeId)->whereRaw('UPPER(code) = ?', ['PSLE'])->exists();
    }

    private static function selectedSubject(): ?Subject
    {
        $subjectId = request()->input('tableFilters.subject_id.value');

        return $subjectId ? Subject::find($subjectId) : null;
    }

    private static function selectedExamTypeId(): ?int
    {
        $filterValue = request()->input('tableFilters.exam_type_id.value');

        if ($filterValue !== null && $filterValue !== '') {
            return (int) $filterValue;
        }

        return static::defaultExamTypeId();
    }

    private static function defaultExamTypeId(): ?int
    {
        return ExamType::whereRaw('UPPER(code) = ?', ['PSLE'])->value('id');
    }

    private static function selectedSubjectHasPaper(int $paperNumber): bool
    {
        $subject = static::selectedSubject();

        return $subject ? (int) ($subject->written_papers ?? 1) >= $paperNumber : false;
    }

    private static function selectedSubjectHasPractical(): bool
    {
        return (bool) (static::selectedSubject()?->has_practical ?? false);
    }

    private static function selectedSubjectHasProject(): bool
    {
        return (bool) (static::selectedSubject()?->has_project ?? false);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRawMarks::route('/'),
            'view' => Pages\ViewRawMark::route('/{record}'),
        ];
    }
}
