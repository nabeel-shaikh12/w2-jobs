<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class CandidateExport implements FromCollection, WithHeadings
{

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'User Name',
            'Email',
            'Phone',
            'Address',
            'Status',
            'Profile Visits',
            'Shortlisted',
            'Bookmarked',
            'Applied Jobs',
            'Resume',
            'Join Date',
            'Last Update',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        return User::with('jobBookmarks')->withCount(['appliedJobs', 'profileVisits', 'candidateBookmarks'])
            ->candidate()->get()
            ->map(function (User $user) {
                return [
                    $user->id,
                    $user->name,
                    $user->username,
                    $user->email,
                    $user->phone,
                    $user->address,
                    $user->status,
                    $user->profile_visits_count,
                    $user->jobBookmarks->where('opening_id', '!=', null)->count(),
                    $user->candidate_bookmarks_count,
                    $user->applied_jobs_count,
                    $user->meta['custom_cv'] ?? 'NA',
                    $user->formatedDateFor('created_at'),
                    $user->formatedDateFor('updated_at'),
                ];
            });
    }
}
