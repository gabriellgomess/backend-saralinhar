<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'client_id',
        'type',
        'description',
        'amount',
        'due_date',
        'admission_date',
        'warranty_ends_at',
        'payment_date',
        'status',
        'is_warranty_replacement',
        'job_id',
        'candidate_id',
        'candidate_contact',
        'candidate_salary',
        'commission_percentage',
        'financial_service_id',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
        'admission_date' => 'date',
        'warranty_ends_at' => 'date',
        'payment_date' => 'date',
        'is_warranty_replacement' => 'boolean',
        'amount' => 'decimal:2',
        'candidate_salary' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(RecruitmentClient::class, 'client_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function service()
    {
        return $this->belongsTo(FinancialService::class, 'financial_service_id');
    }

    public function recruiterCommissions()
    {
        return $this->hasMany(FinancialRecruiterCommission::class);
    }

    /**
     * Auto-cria um faturamento ao abrir/aprovar uma vaga.
     */
    public static function autoCreateForJob(Job $job)
    {
        // Evita duplicados
        if (self::where('job_id', $job->id)->exists()) {
            return null;
        }

        $client = null;
        if ($job->user && $job->user->recruitment_client_id) {
            $client = RecruitmentClient::find($job->user->recruitment_client_id);
        }
        
        if (!$client && $job->company) {
            $companyTrim = strtolower(trim($job->company));
            
            // 1. Busca exata (ignorando case e espaços)
            $client = RecruitmentClient::whereRaw('LOWER(TRIM(name)) = ?', [$companyTrim])->first();

            // 2. Se não encontrar, faz busca substring/bidirecional tolerante
            if (!$client && $companyTrim !== 'confidencial') {
                $allClients = RecruitmentClient::all();
                foreach ($allClients as $c) {
                    $clientNameClean = strtolower(trim($c->name));
                    if ($clientNameClean !== '' && (stripos($companyTrim, $clientNameClean) !== false || stripos($clientNameClean, $companyTrim) !== false)) {
                        $client = $c;
                        break;
                    }
                }
            }
        }

        if (!$client) {
            \Illuminate\Support\Facades\Log::warning("Não foi possível auto-criar transação financeira para vaga ID {$job->id}: nenhum cliente RecruitmentClient correspondente a '{$job->company}' foi encontrado.");
            return null;
        }

        $commissionPct = (float) ($client->commission_percentage ?? 0);
        $salary = (float) ($job->salary ?? 0);
        $amount = $commissionPct > 0 ? ($salary * ($commissionPct / 100)) : 0;

        return self::create([
            'client_id' => $client->id,
            'type' => 'recruitment',
            'description' => "Faturamento da Vaga: {$job->title}",
            'amount' => $amount,
            'due_date' => now()->addDays(30),
            'job_id' => $job->id,
            'candidate_salary' => $salary,
            'commission_percentage' => $commissionPct,
            'status' => 'pending',
        ]);
    }
}
