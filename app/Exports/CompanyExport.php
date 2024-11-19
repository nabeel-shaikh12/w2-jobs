<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class CompanyExport implements FromCollection, WithHeadings
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
            'Plan',
            'Jobs Posted',
            'Shortlisted',
            'Applications',
            'Saved Candidate',
            'Active Jobs',
            'Inactive Jobs',
            'KYC Verified at',
            'Other Informations',
            'Join Date',
            'Last Update',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        return User::query()->employer()->get()
            ->map(function (User $user) {
                return [
                    $user->id,
                    $user->name,
                    $user->username,
                    $user->email,
                    $user->phone,
                    $user->address,
                    $user->status,
                    $user->planmodel?->title,
                    $user->jobs()->count(),
                    $user->appliedJobs()->whereNotNull('opening_id')->count(),
                    $user->appliedJobs()->count(),
                    $user->candidateBookmarks()->count(),
                    $user->jobs()->active()->count(),
                    $user->jobs()->inactive()->count(),
                    $user->formatedDateFor('kyc_verified_at'),
                    $user->meta,
                    $user->formatedDateFor('created_at'),
                    $user->formatedDateFor('updated_at'),
                ];
            });
    }
}
