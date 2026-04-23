<?php

namespace App\Http\Controllers;

use App\Services\MathService;

class MathController extends Controller
{
    protected $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function index()
    {
        return $this->mathService->add(3, 4);
    }
}
