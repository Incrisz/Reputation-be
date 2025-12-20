<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SwaggerUIController extends Controller
{
    /**
     * Display the Swagger UI for API documentation
     */
    public function index(): View
    {
        return view('swagger-ui', [
            'spec_url' => url('/api/docs.json'),
            'title' => 'BizVisibility AI - API Documentation',
        ]);
    }
}
