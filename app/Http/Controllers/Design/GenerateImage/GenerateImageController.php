<?php

namespace App\Http\Controllers\Design\GenerateImage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignModel\GenerateImageModel\GenerateImageModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateImageController extends Controller
{
    public function generateImage(Request $request)
    {
        try {
            $request->validate([
                'prompt' => 'required|string',
                'style' => 'required|string',
                'aspect_ratio' => 'required|string',
            ]);
        
            $prompt = $request->input('prompt');
            $style = $request->input('style');
            $aspect_ratio = $request->input('aspect_ratio');
        
            // Map aspect_ratio to valid DALL-E 3 sizes
            $sizeMap = [
                '1:1' => '1024x1024',
                '16:9' => '1792x1024',
                '9:16' => '1024x1792', // Common portrait aspect ratio
                '4:3' => '1024x1024', // Fallback to square for similar ratios
                '3:4' => '1024x1792', // Portrait
            ];
        
            $size = $sizeMap[$aspect_ratio] ?? '1024x1024';
        
            $response = Http::withToken(config('services.openai.key'))
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'dall-e-3',
                    'prompt' => "$prompt, in a $style style", // Refined prompt
                    'n' => 1,
                    'size' => $size,
                    'quality' => 'standard', // Specify quality for clarity
                ]);
        
            if (!$response->successful()) {
                // Log the entire response for debugging
                Log::error('OpenAI API request failed.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                // Return a more detailed error to the client
                return response()->json([
                    'error' => 'Failed to generate image from API.',
                    'details' => $response->json() // The actual error from OpenAI
                ], $response->status());
            }
            
            $imageUrl = $response->json('data.0.url');
        
            $image = GenerateImageModel::create([
                'prompt' => $prompt,
                'style' => $style,
                'aspect_ratio' => $aspect_ratio,
                'image_url' => $imageUrl,
                'user_id' => auth()->id(), // Safely get authenticated user's ID
            ]);
        
            return response()->json(['image' => $image], 201);
        } catch (Throwable $e) {
            Log::error('Image generation failed unexpectedly.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'An internal server error occurred.'], 500);
        }
    }

    public function regenerateImage(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:generate_images,id',
            ]);

            $original = GenerateImageModel::findOrFail($request->input('id'));

            $prompt = $original->prompt;
            $style = $original->style;
            $aspect_ratio = $original->aspect_ratio;

            $sizeMap = [
                '1:1' => '1024x1024',
                '16:9' => '1792x1024',
                '9:16' => '1024x1792',
                '4:3' => '1024x1024',
                '3:4' => '1024x1792',
            ];
            $size = $sizeMap[$aspect_ratio] ?? '1024x1024';

            $response = Http::withToken(config('services.openai.key'))
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'dall-e-3',
                    'prompt' => "$prompt, in a $style style",
                    'n' => 1,
                    'size' => $size,
                    'quality' => 'standard',
                ]);

            if (!$response->successful()) {
                Log::error('OpenAI API request failed (regenerate).', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return response()->json([
                    'error' => 'Failed to regenerate image from API.',
                    'details' => $response->json()
                ], $response->status());
            }

            $imageUrl = $response->json('data.0.url');

            $image = GenerateImageModel::create([
                'prompt' => $prompt,
                'style' => $style,
                'aspect_ratio' => $aspect_ratio,
                'image_url' => $imageUrl,
                'user_id' => auth()->id(),
            ]);

            return response()->json(['image' => $image], 201);
        } catch (Throwable $e) {
            Log::error('Image regeneration failed unexpectedly.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An internal server error occurred.'], 500);
        }
    }
}
