# Test Quick Reference Guide

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Only Mark Entry Tests
```bash
php artisan test tests/Unit/Services/MarkEntry/ tests/Feature/MarkEntryLifecycleWorkflowTest.php
```

### Run Unit Tests (51 tests)
```bash
php artisan test tests/Unit/Services/MarkEntry/
```

### Run Integration Tests (10 tests)
```bash
php artisan test tests/Feature/MarkEntryLifecycleWorkflowTest.php
```

### Run Specific Test Class
```bash
# Unit tests for lifecycle states
php artisan test tests/Unit/Services/MarkEntry/LifecycleStateServiceTest.php

# Unit tests for moderation
php artisan test tests/Unit/Services/MarkEntry/MarkModerationServiceTest.php

# Integration workflow tests
php artisan test tests/Feature/MarkEntryLifecycleWorkflowTest.php
```

### Run with Coverage Report
```bash
php artisan test --coverage
```

### Run with Detailed Output
```bash
php artisan test --verbose
```

---

## Test File Locations

### Unit Tests
- `tests/Unit/Services/MarkEntry/LifecycleStateServiceTest.php` (29 tests)
- `tests/Unit/Services/MarkEntry/MarkModerationServiceTest.php` (22 tests)

### Integration Tests
- `tests/Feature/MarkEntryLifecycleWorkflowTest.php` (10 tests)

---

## Test Summary

| Category | Tests | Assertions | Status |
|----------|-------|-----------|--------|
| Unit: Lifecycle States | 29 | 47 | ✅ Pass |
| Unit: Moderation | 22 | 49 | ✅ Pass |
| Integration: Workflows | 10 | 70 | ✅ Pass |
| **TOTAL** | **61** | **166** | **✅ Pass** |

---

## State Machine Reference

```
Draft
  ├─→ Validating
  │     ├─→ Validated
  │     │     ├─→ Awaiting Moderation
  │     │     │     ├─→ Approved
  │     │     │     │     ├─→ Submitted
  │     │     │     │     │     └─→ Archived (terminal)
  │     │     │     │     └─→ Draft (reject & resubmit)
  │     │     │     └─→ Rejected → Draft
  │     │     └─→ Draft (for corrections)
  │     └─→ Validation Failed
  │           └─→ Draft
  └─→ Rejected
        └─→ Draft (resubmit)
```

---

## Key Test Scenarios

### Happy Path
1. Create batch (draft)
2. Validate (validating → validated)
3. Moderate (awaiting_moderation)
4. Approve (approved)
5. Submit (submitted)
6. Archive (archived)

### Rejection Flow
1. Create batch & validate (validated)
2. Create review & reject (rejected)
3. Transition to draft (draft)
4. Re-validate & approve
5. Submit

### Error Recovery
1. Start validation (validating)
2. Fail validation (validation_failed)
3. Return to draft
4. Retry validation

### Concurrent Batches
- Multiple batches can be in different states
- Each has independent review chain
- No cross-batch interference

---

## Common Test Assertions

```php
// State transitions
$this->assertEquals('draft', $batch->lifecycle_state);

// Record creation
$this->assertNotNull($lifecycle);
$this->assertCount(2, $states);

// User tracking
$this->assertEquals($user->id, $lifecycle->transitioned_by);
$this->assertNotNull($lifecycle->transitioned_at);

// Rejection tracking
$this->assertTrue((bool)$batch->requires_resubmission);
$this->assertEquals($reason, $batch->rejection_reason);

// Invalid transitions
$this->expectException(\Exception::class);
$this->service->transition($batch, 'invalid_state', $user);
```

---

## Debugging Failed Tests

### Check Test Output
```bash
php artisan test tests/Feature/MarkEntryLifecycleWorkflowTest.php --testdox
```

### Run Single Test
```bash
# Find the test method name from failed output
php artisan test tests/Unit/Services/MarkEntry/LifecycleStateServiceTest.php --filter=test_transition_draft_to_validating
```

### Common Issues

**Foreign Key Constraint Failed**
- Ensure Region & District models exist
- Check School, Subject, ExamType relationships

**Unique Constraint on batch_hash**
- Each batch needs unique hash
- Use uniqid() for test batches

**Review Type Validation**
- Must be: `school_hod`, `district_supervisor`, or `admin`
- Other values will fail enum check

**Auth Context**
- Use `$this->actingAs($user)` before creating reviews
- Some service methods expect authenticated user

---

## Performance Notes

- All 61 tests complete in ~28 seconds
- Unit tests: ~21 seconds
- Integration tests: ~9 seconds
- RefreshDatabase provides test isolation

---

## Next Steps

1. **Load Testing** (Phase 4.3)
   - Test with 400,000 candidates
   - Verify PDF generation performance
   - Check CSV export scalability

2. **Security Audit** (Phase 4.4)
   - Verify authorization policies
   - Test policy enforcement
   - Check audit logging

3. **Deployment** (Phase 5)
   - Production checklist
   - 24/7 monitoring setup
   - User training

---

## Support

For test failures or issues:
1. Review test output and stack trace
2. Check state machine definitions
3. Verify test data setup (Region, District, School, Subject, ExamType)
4. Ensure proper auth context with `actingAs()`
5. Confirm unique batch hashes and codes

See `PHASE_4_TESTING_COMPLETE.md` for detailed test documentation.
