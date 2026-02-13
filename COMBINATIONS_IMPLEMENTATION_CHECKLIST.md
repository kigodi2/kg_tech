# Combinations Implementation Checklist

Use this checklist to track progress through the implementation roadmap.

---

## Phase 1: Database Schema Enhancement

### 1.1 Create Pivot Table Migration
- [ ] Create migration file: `create_combination_subject_table.php`
- [ ] Define columns: id, combination_id, subject_id, created_at, updated_at
- [ ] Add unique constraint: `unique(['combination_id', 'subject_id'])`
- [ ] Add index on combination_id
- [ ] Add index on subject_id
- [ ] Test migration syntax
- [ ] Run on development environment
- [ ] Verify table structure

### 1.2 Update Combinations Table
- [ ] Create migration: `update_combinations_table.php`
- [ ] Add field: `category` (string, default 'ARTS')
- [ ] Add field: `description` (text, nullable)
- [ ] Add constraint: `unique(['exam_type_id', 'code'])`
- [ ] Keep old `subjects` column (for data migration)
- [ ] Test migration
- [ ] Backup production database
- [ ] Document rollback procedure

### 1.3 Data Migration Script
- [ ] Create artisan command: `MigrateCombinationSubjects`
- [ ] Parse existing subjects strings
- [ ] Find matching subjects by code
- [ ] Sync to pivot table
- [ ] Log migration results
- [ ] Handle errors gracefully
- [ ] Test with sample data
- [ ] Validate results

### 1.4 Database Testing
- [ ] Verify pivot table created correctly
- [ ] Verify new columns exist
- [ ] Verify unique constraints work
- [ ] Test duplicate combination code (should fail)
- [ ] Test duplicate subject in combination (should fail)
- [ ] Test relationship queries work
- [ ] Document schema in wiki

---

## Phase 2: Model Enhancement

### 2.1 Update Combination Model
- [ ] Add `subjects()` BelongsToMany relationship
- [ ] Add `examType()` BelongsTo relationship
- [ ] Add `$fillable` array with all fields
- [ ] Add `$casts` for datetime columns
- [ ] Add scope: `byCategory()`
- [ ] Add scope: `byExamType()`
- [ ] Add scope: `search()`
- [ ] Add accessor: `getSubjectCountAttribute()`
- [ ] Add accessor: `getSubjectCodesAttribute()`
- [ ] Add method: `syncSubjects()`
- [ ] Add method: `hasSubject()`
- [ ] Write unit tests for model
- [ ] Test relationships work correctly

### 2.2 Update Subject Model
- [ ] Add `combinations()` BelongsToMany relationship
- [ ] Test relationship works
- [ ] Verify cascade delete behavior

### 2.3 Create Form Requests
- [ ] Create `StoreCombinationRequest`
  - [ ] Validate code (required, unique)
  - [ ] Validate category (required, enum)
  - [ ] Validate subject_ids (array, exists)
  - [ ] Test validation works
- [ ] Create `UpdateCombinationRequest`
  - [ ] Validate code (unique, except current)
  - [ ] Validate category
  - [ ] Validate subject_ids (nullable)
  - [ ] Test validation works

### 2.4 Model Testing
- [ ] test_combination_has_many_subjects()
- [ ] test_subject_belongs_to_many_combinations()
- [ ] test_combination_code_unique_per_exam()
- [ ] test_cascade_delete_on_exam_deletion()
- [ ] test_scopes_work_correctly()
- [ ] test_accessors_return_correct_data()
- [ ] All tests passing

---

## Phase 3: API Layer Enhancement

### 3.1 Create Controller
- [ ] Create `CombinationController` class
- [ ] Implement `index()` method
  - [ ] Get exam type
  - [ ] Load combinations with subjects
  - [ ] Support search parameter
  - [ ] Support category filter
  - [ ] Paginate results
  - [ ] Return proper JSON
- [ ] Implement `store()` method
  - [ ] Validate request
  - [ ] Create combination
  - [ ] Sync subjects
  - [ ] Return created combination
- [ ] Implement `update()` method
  - [ ] Find combination
  - [ ] Validate request
  - [ ] Update fields
  - [ ] Sync subjects
  - [ ] Return updated combination
- [ ] Implement `destroy()` method
  - [ ] Find combination
  - [ ] Delete (cascade handled by DB)
  - [ ] Return success message
- [ ] Implement `import()` method
  - [ ] Get CSV file
  - [ ] Parse CSV
  - [ ] Validate rows
  - [ ] Create combinations with subjects
  - [ ] Return import results
  - [ ] Handle errors
- [ ] Implement `export()` method
  - [ ] Get combinations for exam type
  - [ ] Load subjects
  - [ ] Format as CSV
  - [ ] Return downloadable file
- [ ] Test all methods
- [ ] Test error handling

### 3.2 Create Routes
- [ ] Update `routes/api.php`
- [ ] Group routes under exam-types/{code}
- [ ] Add index route: GET /combinations
- [ ] Add store route: POST /combinations
- [ ] Add update route: PUT /combinations/{id}
- [ ] Add destroy route: DELETE /combinations/{id}
- [ ] Add import route: POST /combinations/import
- [ ] Add export route: GET /combinations/export
- [ ] Add middleware: auth
- [ ] Test routes register correctly

### 3.3 Test API
- [ ] test_list_combinations_with_pagination()
- [ ] test_list_combinations_with_search()
- [ ] test_list_combinations_with_category_filter()
- [ ] test_create_combination_with_subjects()
- [ ] test_create_combination_validation_fails()
- [ ] test_update_combination()
- [ ] test_update_combination_subjects()
- [ ] test_delete_combination()
- [ ] test_import_combinations_from_csv()
- [ ] test_export_combinations_to_csv()
- [ ] test_duplicate_code_returns_error()
- [ ] test_invalid_subjects_rejected()
- [ ] All tests passing
- [ ] Error responses proper format
- [ ] HTTP status codes correct

---

## Phase 4: Frontend Update

### 4.1 Update Alpine Component
- [ ] Update `loadCombinations()` method
  - [ ] Use paginated API
  - [ ] Load relationships data
  - [ ] Update state properly
  - [ ] Handle pagination response
- [ ] Update `saveCombination()` method
  - [ ] Use exam-type-specific URL
  - [ ] Send subject_ids (array) not string
  - [ ] Handle response properly
  - [ ] Show success message
  - [ ] Reload combinations
- [ ] Update `deleteCombination()` method
  - [ ] Use correct endpoint
  - [ ] Confirm before delete
  - [ ] Reload after delete
- [ ] Update `filterCombinations()` method
  - [ ] Call server-side search
  - [ ] Pass search parameter
  - [ ] Handle results
- [ ] Implement `importCombinationsCSV()` method
  - [ ] Send file to /api endpoint
  - [ ] Show progress
  - [ ] Display results
  - [ ] Reload combinations
- [ ] Implement `exportCombinationsCSV()` method
  - [ ] Call /api endpoint
  - [ ] Handle blob response
  - [ ] Trigger download

### 4.2 Update Modals
- [ ] Subject modal shows category
- [ ] Subject modal shows description
- [ ] Combination modal shows category dropdown
- [ ] Combination modal shows subject selection
- [ ] View modal displays relationships properly
- [ ] Edit modal pre-fills all fields
- [ ] Modal validation works

### 4.3 Update Table Display
- [ ] Display category badge
- [ ] Display subject count
- [ ] Display action buttons correctly
- [ ] Pagination controls work
- [ ] Search input triggers API call
- [ ] Category filter works
- [ ] Empty state message displays

### 4.4 Frontend Testing
- [ ] test_component_loads_data()
- [ ] test_modal_opens_with_correct_data()
- [ ] test_form_submission_calls_api()
- [ ] test_delete_shows_confirmation()
- [ ] test_search_filters_combinations()
- [ ] test_pagination_changes_page()
- [ ] test_import_shows_progress()
- [ ] test_export_downloads_file()
- [ ] All interactive elements work
- [ ] No console errors
- [ ] Responsive design maintained

---

## Phase 5: Routes and Configuration

### 5.1 Routes
- [ ] Verify all routes defined in api.php
- [ ] Test route parameters work
- [ ] Test middleware applied
- [ ] Test route names (if using named routes)
- [ ] Documentation updated

### 5.2 Configuration
- [ ] Check pagination defaults
- [ ] Check CSV file upload limits
- [ ] Check validation messages
- [ ] Check error handling config
- [ ] Check CORS settings (if applicable)

---

## Phase 6: Testing

### 6.1 Unit Tests
- [ ] Model tests created
- [ ] All model tests passing
- [ ] Relationship tests passing
- [ ] Scope tests passing
- [ ] Accessor tests passing

### 6.2 API Tests
- [ ] Controller tests created
- [ ] All controller tests passing
- [ ] Request validation tests passing
- [ ] Response format tests passing
- [ ] Pagination tests passing
- [ ] Search tests passing
- [ ] Import/export tests passing
- [ ] Error handling tests passing
- [ ] Authorization tests (if applicable)

### 6.3 Integration Tests
- [ ] Full workflow test: Create → Read → Update → Delete
- [ ] Import workflow test
- [ ] Export workflow test
- [ ] Relationship cascade test
- [ ] Search and filter test

### 6.4 Manual Testing
- [ ] Test in Chrome
- [ ] Test in Firefox
- [ ] Test in Safari
- [ ] Test on mobile
- [ ] Test with large datasets (500+ combinations)
- [ ] Test with special characters in data
- [ ] Test with empty data
- [ ] Test keyboard navigation
- [ ] Test screen reader compatibility

---

## Phase 7: Deployment Preparation

### 7.1 Documentation
- [ ] Update API documentation
- [ ] Document new fields
- [ ] Document new relationships
- [ ] Create migration guide
- [ ] Document breaking changes
- [ ] Update team wiki

### 7.2 Migration Strategy
- [ ] Backup production database
- [ ] Test migration on copy
- [ ] Create rollback procedure
- [ ] Plan downtime (if any)
- [ ] Notify stakeholders
- [ ] Prepare monitoring alerts

### 7.3 Staging
- [ ] Deploy to staging environment
- [ ] Run full test suite
- [ ] Manual testing on staging
- [ ] Performance testing
- [ ] Load testing (if applicable)
- [ ] Get stakeholder approval

---

## Phase 8: Production Deployment

### 8.1 Pre-Deployment
- [ ] Database backup taken
- [ ] Team notified
- [ ] Monitoring alerts ready
- [ ] Rollback procedure tested
- [ ] All tests passing

### 8.2 Deployment
- [ ] Run migrations
- [ ] Deploy code changes
- [ ] Clear application cache
- [ ] Run data migration script
- [ ] Verify deployment successful
- [ ] Monitor for errors

### 8.3 Post-Deployment
- [ ] Smoke test all features
- [ ] Verify data integrity
- [ ] Monitor error logs
- [ ] Monitor performance metrics
- [ ] Monitor user feedback
- [ ] Verify no regression

### 8.4 Follow-up
- [ ] Remove old subjects column (after verification)
- [ ] Update tests to remove deprecated code
- [ ] Document lessons learned
- [ ] Schedule team retrospective
- [ ] Plan next improvements

---

## Rollback Procedures

### If Database Migration Fails
- [ ] Restore from backup
- [ ] Investigate issue
- [ ] Fix migration
- [ ] Redeploy

### If API Changes Break Frontend
- [ ] Rollback API code
- [ ] Keep database changes (safer)
- [ ] Fix API
- [ ] Redeploy

### If Data Loss Occurs
- [ ] Restore from backup
- [ ] Rerun data migration
- [ ] Investigate root cause
- [ ] Implement safeguards

---

## Success Criteria Verification

After complete deployment, verify:

- [ ] All combinations display correctly
- [ ] Subject relationships intact
- [ ] Unique constraint prevents duplicates
- [ ] Category field works
- [ ] Search filters server-side
- [ ] Pagination works (25 items per page)
- [ ] Import creates relationships
- [ ] Export includes subjects
- [ ] Modal workflows function
- [ ] No console errors
- [ ] Performance acceptable
- [ ] No data loss
- [ ] All tests passing
- [ ] Team trained on new system

---

## Notes Section

Use this space to document decisions, issues, and lessons learned:

```
Date: _______________
Decision: _________________________________
Reason: __________________________________

Date: _______________
Issue: ____________________________________
Resolution: ______________________________

Date: _______________
Lesson Learned: __________________________
Applied To: _______________________________
```

---

## Sign-Off

- [ ] Developer: _________________________ Date: _________
- [ ] Code Reviewer: _____________________ Date: _________
- [ ] QA Lead: ___________________________ Date: _________
- [ ] Product Manager: ____________________ Date: _________
- [ ] Project Manager: ____________________ Date: _________

---

## Contact & Support

For questions during implementation:
- **Architecture questions** → See COMBINATIONS_IMPLEMENTATION_COMPARISON.md
- **Implementation details** → See COMBINATIONS_IMPROVEMENT_ROADMAP.md
- **Executive summary** → See COMBINATIONS_IMPLEMENTATION_ADVICE.md
- **This checklist** → Refer back to specific phase
