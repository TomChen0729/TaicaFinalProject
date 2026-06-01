<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScenarioService;
class ScenarioController extends Controller
{
    protected ScenarioService $scenarioService;

    public function __construct(ScenarioService $scenarioService)
    {
        $this->scenarioService = $scenarioService;
    }

    public function show($id)
    {
        $scenario = $this->scenarioService->getScenario($id);
        return response()->json($scenario, 200);
    }
}
