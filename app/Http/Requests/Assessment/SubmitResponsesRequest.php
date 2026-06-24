<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o payload de respostas enviado pelo candidato.
 * A validação por question_type é feita no controller, após carregar as perguntas.
 */
class SubmitResponsesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // acesso público via token; autenticação é no middleware
    }

    public function rules(): array
    {
        return [
            'responses'                           => 'required|array|min:1',
            'responses.*.question_id'             => 'required|integer|exists:assessment_questions,id',
            'responses.*.numeric_answer'          => 'nullable|integer|min:0|max:10',
            'responses.*.text_answer'             => 'nullable|string|max:5000',
            'responses.*.option_id'               => 'nullable|integer|exists:assessment_options,id',
            'responses.*.ranking_json'            => 'nullable|array',
            'responses.*.ranking_json.*'          => 'integer',
            'responses.*.sjt_pair_json'           => 'nullable|array',
            'responses.*.sjt_pair_json.best_option_id'  => 'required_with:responses.*.sjt_pair_json|integer',
            'responses.*.sjt_pair_json.worst_option_id' => 'required_with:responses.*.sjt_pair_json|integer',
            'responses.*.response_time_seconds'   => 'nullable|integer|min:0',

            // Dados do respondente (opcional quando já vinculado à application)
            'respondent_name'  => 'nullable|string|max:255',
            'respondent_email' => 'nullable|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'responses.required'              => 'É necessário enviar ao menos uma resposta.',
            'responses.*.question_id.exists'  => 'Pergunta inválida.',
            'responses.*.option_id.exists'    => 'Opção inválida.',
        ];
    }
}
