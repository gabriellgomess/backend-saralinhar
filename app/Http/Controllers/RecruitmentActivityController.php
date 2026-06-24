<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecruitmentActivity;

class RecruitmentActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = RecruitmentActivity::with('client');

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $activities = $query->orderBy('created_at', 'desc')->get();
        return response()->json($activities);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:recruitment_clients,id',
            'job_title' => 'required|string|max:255',
            'opening_date' => 'required|date',
            'sla_deadline' => 'nullable|date',
            'feedback_sent_date' => 'nullable|date',
            'hiring_date' => 'nullable|date',
            'candidate_name' => 'nullable|string|max:255',
            'candidate_contact' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric',
            'commission_percentage' => 'nullable|numeric',
            'commission_value' => 'nullable|numeric',
            'payment_date' => 'nullable|date',
            'feedback_30_days_date' => 'nullable|date',
            'replacement_45_days' => 'boolean',
            'observations' => 'nullable|string',
        ]);

        $activity = RecruitmentActivity::create($validated);
        return response()->json($activity, 201);
    }

    public function show($id)
    {
        $activity = RecruitmentActivity::with('client')->findOrFail($id);
        return response()->json($activity);
    }

    public function update(Request $request, $id)
    {
        $activity = RecruitmentActivity::findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'required|exists:recruitment_clients,id',
            'job_title' => 'required|string|max:255',
            'opening_date' => 'required|date',
            'sla_deadline' => 'nullable|date',
            'feedback_sent_date' => 'nullable|date',
            'hiring_date' => 'nullable|date',
            'candidate_name' => 'nullable|string|max:255',
            'candidate_contact' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric',
            'commission_percentage' => 'nullable|numeric',
            'commission_value' => 'nullable|numeric',
            'payment_date' => 'nullable|date',
            'feedback_30_days_date' => 'nullable|date',
            'replacement_45_days' => 'boolean',
            'observations' => 'nullable|string',
        ]);

        $activity->update($validated);
        return response()->json($activity);
    }

    public function destroy($id)
    {
        $activity = RecruitmentActivity::findOrFail($id);
        $activity->delete();
        return response()->json(null, 204);
    }

    public function dashboardStats()
    {
        $now = now();
        
        // Alerts for 30 days feedback
        // Show activities where feedback_30_days_date is in the future but close (e.g., next 7 days) OR passed and not marked done? 
        // Since we don't have a "done" status for the feedback itself, we'll just show upcoming ones.
        // Or maybe just list all pending feedbacks?
        // Let's list those where feedback_30_days_date is within next 7 days or passed in the last 7 days.
        
        $feedbackAlerts = RecruitmentActivity::with('client')
            ->whereNotNull('feedback_30_days_date')
            ->whereBetween('feedback_30_days_date', [$now->copy()->subDays(7), $now->copy()->addDays(30)])
            ->orderBy('feedback_30_days_date')
            ->get();

        // Alerts for 45 days replacement guarantee
        // If hiring_date exists, the guarantee expires 45 days after.
        // We want to show active guarantees.
        $replacementAlerts = RecruitmentActivity::with('client')
            ->whereNotNull('hiring_date')
            ->where('replacement_45_days', true) // Assuming this means "Guarantee applies"
            ->get()
            ->filter(function ($activity) use ($now) {
                $hiringDate = \Carbon\Carbon::parse($activity->hiring_date);
                $expirationDate = $hiringDate->copy()->addDays(45);
                // Show if we are within the 45 days window
                return $now->lte($expirationDate);
            })->map(function ($activity) {
                $hiringDate = \Carbon\Carbon::parse($activity->hiring_date);
                $activity->guarantee_expiration = $hiringDate->addDays(45)->format('Y-m-d');
                return $activity;
            })->values();

        return response()->json([
            'feedback_alerts' => $feedbackAlerts,
            'replacement_alerts' => $replacementAlerts,
            'total_activities' => RecruitmentActivity::count(),
            'total_clients' => \App\Models\RecruitmentClient::count(),
        ]);
    }
}
