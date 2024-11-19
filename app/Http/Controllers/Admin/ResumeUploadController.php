<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Uploader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ResumeUploadController extends Controller
{
    public function uploadResumes(Request $request)
{
    try {
        $request->validate([
            'resume.*' => 'required|file|mimes:pdf,doc,docx|max:10240', // Up to 10MB
        ]);

        if (!$request->hasFile('resume')) {
            return response()->json(['message' => 'No files uploaded'], 400);
        }

        $nameInput = $request->input('name');
        $skillInput = $request->input('skills');
        $files = $request->file('resume');
        $uploadedFiles = [];
        $candidatesData = [];

        foreach ($files as $file) {
            if ($file->isValid()) {
                $path = $this->saveFile($file);
                $uploadedFiles[] = $path;

                try {
                    // Attempt to extract data from resume, including the email check
                    $candidateData = $this->extractResumeData($file);
                } catch (\Exception $e) {
                    return response()->json(['message' => $e->getMessage()], 400);
                }

                if (!empty($skillInput)) {
                    DB::table('categories')->insert([
                        'title' => $skillInput,
                        'slug' => Str::slug($skillInput),
                        'type' => 'service'

                    ]);
                }

                $candidatesData[] = [
                    'name' => $nameInput ?? 'Unknown',
                    'email' => $candidateData['email'],
                    'password' => Hash::make(Str::random(10)),
                    'role' => 'user',
                    'status' => 1,
                    'meta' => [
                        ...$candidateData['meta'] ?? [],
                        'resume' => $path,
                    ],
                ];
            } else {
                return response()->json(['message' => 'Invalid file uploaded'], 422);
            }
        }

        foreach ($candidatesData as $data) {
            try {
                $user = User::create($data);
                logger()->info('User Created:', ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
            } catch (\Exception $e) {
                logger()->error('User Creation Error:', ['error' => $e->getMessage(), 'data' => $data]);
                return response()->json(['message' => 'User account creation failed.'], 500);
            }
        }

        return response()->json([
            'message' => 'Resumes uploaded and user accounts created successfully!',
            'files' => $uploadedFiles,
        ], 200);
    } catch (\Exception $e) {
        \Log::error('Error uploading resumes: ' . $e->getMessage());
        return response()->json(['message' => 'Server Error'], 500);
    }
}

    public function saveFile($file)
    {
        try {
            // Store the uploaded file in the 'resumes' folder in the 'public' disk
            return $file->store('resumes', 'public');
        } catch (\Exception $e) {
            \Log::error('Error saving file: ' . $e->getMessage());
            return null;
        }
    }

    private function extractResumeData($resumeFile)
    {
        $extension = $resumeFile->getClientOriginalExtension();
        $data = [];
    
        if ($extension === 'pdf') {
            $pdfParser = new PdfParser();
            $pdf = $pdfParser->parseFile($resumeFile->getPathname());
            $text = $pdf->getText();
    
            $data = $this->parseResumeText($text);
    
        } elseif (in_array($extension, ['doc', 'docx'])) {
            $phpWord = IOFactory::load($resumeFile->getPathname());
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                foreach ($elements as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . ' ';
                    }
                }
            }
            $data = $this->parseResumeText($text);
        }
    
        // Check if email exists in parsed data, return with email if found, otherwise show error
        if (empty($data['email'])) {
            throw new \Exception('Email not found in document, which is required.');
        }
    
        return $data;
    }
    
    private function parseResumeText($text)
    {
        $data = [];

        preg_match('/([A-Za-z]+ [A-Za-z]+)/', $text, $nameMatches);
        preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', $text, $emailMatches);

        $data['name'] = $nameMatches[0] ?? null;
        $data['email'] = $emailMatches[0] ?? null;

        return $data;
    }

    public function destroyResume(User $candidate)
    {
        if ($candidate->meta && isset($candidate->meta['resume'])) {
            $this->removeFile($candidate->meta['resume']);
        }

        $candidate->update(['meta->resume' => null]);

        return response()->json(['message' => 'Resume removed successfully.'], 200);
    }
}
