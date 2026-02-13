<?php

namespace App\Observers;

use App\Models\School;

class SchoolObserver
{
    /**
     * Handle the School "created" event.
     */
    public function created(School $school): void
    {
        $school->enforceExamTypeSchoolType();
    }

    /**
     * Handle the School "updated" event.
     */
    public function updated(School $school): void
    {
        $school->enforceExamTypeSchoolType();
    }

    /**
     * Handle the School "saving" event - validate before save.
     */
    public function saving(School $school): void
    {
        // Optional: Validate before saving (throws exception if invalid)
        // Uncomment the line below to enforce strict validation
        // $school->validateExamTypeSchoolType();
    }
}
