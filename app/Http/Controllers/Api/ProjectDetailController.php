<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;

class ProjectDetailController extends Controller
{
    public function milestones(Project $project)
    {
        return response()->json([
            'data' => $project->milestones
        ]);
    }
}