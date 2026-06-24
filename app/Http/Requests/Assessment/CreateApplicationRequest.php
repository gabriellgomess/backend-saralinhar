<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class CreateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessment_test_id'    => 'required|integer|exists:assessment_tests,id',
            'recruitment_client_id' => 'nullable|integer|exists:recruitment_clients,id',
            'candidate_id'          => 'nullable|integer|exists:candidates,id',
            'respondent_name'       => 'nullable|string|max:255',
            'respondent_email'      => 'nullable|email|max:255',
            'application_type'      => 'nullable|in:candidate,employee,leader,team,climate',
            'expires_at'            => 'nullable|date|after:now',
            'metadata'              => 'nullable|array',
        ];
    }
}
