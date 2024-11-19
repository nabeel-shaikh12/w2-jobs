<?php
namespace App\Http\Controllers\Employer;

use App\Models\Plan;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    /**
     * Fetch and limit resumes based on the user's plan.
     */
    public function fetchResumes($planId)
    {
        // Fetch the plan with its resumes data
        $plan = Plan::findOrFail($planId);

        // Get the resumes from the JSON data field
        $data = json_decode($plan->data, true);  // Decoding JSON into an array

        // Check if resumes are stored in the JSON 'resumes' key
        $resumes = isset($data['resumes']) ? $data['resumes'] : [];

        // Fetch the resume limit from the plan
        $resumeLimit = $plan->resume_limit;

        // Apply the limit
        $limitedResumes = array_slice($resumes, 0, $resumeLimit);

        // Return the limited resumes
        return response()->json([
            'resumes' => $limitedResumes,
            'limit' => $resumeLimit,
            'total_resumes' => count($resumes)
        ]);
    }

    /**
     * Store a new resume (while enforcing the resume limit).
     */
    public function storeResume(Request $request, $planId)
    {
        // Fetch the plan and its resume data
        $plan = Plan::findOrFail($planId);

        // Get current resumes from JSON data
        $data = json_decode($plan->data, true);
        $resumes = isset($data['resumes']) ? $data['resumes'] : [];

        // Check if the user has exceeded the resume limit
        if (count($resumes) >= $plan->resume_limit) {
            return response()->json(['message' => 'Resume limit reached.'], 403);
        }

        // Add the new resume to the resumes list
        $newResume = $request->input('resume');  // Assuming 'resume' is passed in the request
        $resumes[] = $newResume;

        // Update the plan's data with the new resume
        $data['resumes'] = $resumes;
        $plan->data = json_encode($data);
        $plan->save();

        return response()->json(['message' => 'Resume added successfully.', 'resumes' => $resumes]);
    }
}
