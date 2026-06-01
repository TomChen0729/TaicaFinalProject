<?php

namespace App\Services;

use App\Models\Scenario;

class ScenarioService
{
    public function getScenario(string $id)
    {
        return Scenario::findOrFail($id);
    }
}
