<?php

namespace App\Http\Controllers\Api\Exercise;

use App\DTO\Api\CheckSolutionData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Exercise\CheckResultResource;
use App\Models\Exercise;
use App\Models\User;
use App\Services\ExerciseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckController extends Controller
{
    public function store(
        Request $request,
        Exercise $exercise,
        CheckSolutionData $data,
        ExerciseService $exerciseService
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $checkResult = $exerciseService->check($user, $exercise, $data->solution_code);

        return response([
            'check_result' => new CheckResultResource($checkResult),
        ], 201);
    }
}
