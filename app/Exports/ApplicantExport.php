<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

use function Clue\StreamFilter\fun;

class ApplicantExport implements FromCollection, WithHeadings
{
    public $dynamicFields = [];

    public function __construct(public $job)
    {
        $this->dynamicFields = collect(json_decode($this->job->fields))->pluck('label')->toArray();
    }

    public function headings(): array
    {
        $defaultHeadings = [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Address',
            'Join Date',
        ];

        return array_merge($defaultHeadings, $this->dynamicFields);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->job
            ->appliedApplicants()
            ->get()
            ->map(function (User $user) {

                $dynamicValues = collect(json_decode($user->pivot->meta)?->fields ?? [])
                ->flatMap(function ($item) {
                    return [strtolower($item->label) => $item->value];
                })->toArray();

                $defaultValues =  [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->address,
                    $user->formatedDateFor('created_at'),
                ];

                collect($this->dynamicFields)->each(function ($label) use ($dynamicValues, $defaultValues) {
                    array_push($defaultValues, $dynamicValues[$label] ?? '');
                });

                return array_merge($defaultValues, $dynamicValues);
            });
    }
}
