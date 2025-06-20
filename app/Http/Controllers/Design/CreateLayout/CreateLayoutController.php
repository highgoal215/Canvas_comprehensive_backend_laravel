<?php

namespace App\Http\Controllers\Design\CreateLayout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignModel\GenerateLayoutModel\GenerateLayout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateLayoutController extends Controller
{
    public function generateLayout(Request $request)
    {
        try {
            $request->validate([
                'contentType' => 'required|string',
                'contentDescription' => 'required|string',
                'style' => 'required|string',
                'aspect_ratio' => 'required|string',
            ]);

            $contentType = $request->input('contentType');
            $contentDescription = $request->input('contentDescription');
            $style = $request->input('style');
            $aspect_ratio = $request->input('aspect_ratio');

            // Construct a detailed prompt for OpenAI
            $prompt = "A professional, high-resolution layout for '$contentType'. " .
                      "The layout should feature: '$contentDescription'. " .
                      "The design must be in a '$style' style, suitable for a photo frame or digital display.";

            // Map aspect_ratio to valid DALL-E 3 sizes
            $sizeMap = [
                '1:1' => '1024x1024',
                '16:9' => '1792x1024',
                '9:16' => '1024x1792',
                '4:3' => '1024x1024',
                '3:4' => '1024x1792'
            ];
            $size = $sizeMap[$aspect_ratio] ?? '1024x1024';

            $response = Http::withToken(config('services.openai.key'))
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => $size,
                    'quality' => 'hd', // Use 'hd' for higher quality layouts
                ]);

            if (!$response->successful()) {
                Log::error('OpenAI API request failed (generateLayout).', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return response()->json([
                    'error' => 'Failed to generate layout from API.',
                    'details' => $response->json()
                ], $response->status());
            }

            $layoutUrl = $response->json('data.0.url');

            $layout = GenerateLayout::create([
                'content_type' => $contentType,
                'content_description' => $contentDescription,
                'style' => $style,
                'aspect_ratio' => $aspect_ratio,
                'layout_url' => $layoutUrl,
                'user_id' => auth()->id(),
            ]);

            return response()->json(['layout' => $layout], 201);
        } catch (Throwable $e) {
            Log::error('Layout generation failed unexpectedly.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An internal server error occurred.'], 500);
        }
    }
}
