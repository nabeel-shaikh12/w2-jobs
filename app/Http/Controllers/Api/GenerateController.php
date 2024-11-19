<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\AiTemplate;

class GenerateController extends Controller
{
    public function aiGenerate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'language' => 'required|string',
            'tone' => 'required|string',
            'length' => 'required|numeric',
            'result' => 'required|numeric',
            'creativity' => 'required|numeric',
            'fields.*.value' => 'required|string',
        ], [
            'fields.*.value.required' => 'This field is required',
        ]);

        $prompt = 'Please create content in the language of your choice: ' . $request->language . ' The content should reflect the tone you specified as: ' . $request->tone . ' Your prompt is: ' . $request->prompt . ' Content will be written ' . $request->result . ' times.';

        $credit_fee = get_option('per_word_credit');

        $template = AiTemplate::findOrFail($request->template_id);


        $maxToken = $request->input('length');
        $totalCharge = ($maxToken * (int) $request->result) * (float)$credit_fee;
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        if ($totalCharge > $user->credits) {
            return response()->json(['error' => 'Insufficient credits'], 400);
        }
        $user->credits -= $totalCharge;
        $user->save();

        if ($request->filled('prompt')) {

            $data = [
                'model' => $template->ai_model,
                'prompt' => $prompt,
                'n' => (int) $request->result ?? 1,
                'max_tokens' => (int) $request->length ?? 100,
                'temperature' => (float) $request->creativity ?? 0.7,
            ];

            // below code are is full example implementation of OpenAiService
            // with manual implementation [without OpenAi package] 
            // you can use above methods thats are commented
            // or you can use as like shortly like below and clear comments

            $response = \App\Services\OpenAiService::init($data)
                ->completions()
                ->getResponse()
                ->streamed();
            if ($response->toString() != null) {
                $user->aiGeneratedContents()->create([
                    'ai_template_id' => $request->template_id,
                    'prompt' => $request->prompt,
                    'content' => $response->toString(),
                    'result' => $request->result,
                    'length' => $request->length,
                    'charge' => $totalCharge,
                ]);
            }
            else{
                $user->credits += $totalCharge;
                $user->save();
            }
            
        }

        return $response->toArray();
    }
}
