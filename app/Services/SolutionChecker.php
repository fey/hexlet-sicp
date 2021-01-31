<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use mikehaertl\shellcommand\Command;

class SolutionChecker
{
    public function check(string $exercisePath, string $solutionCode): array
    {
        $stubViewPath = sprintf(
            'exercise.solution_stub.%s',
            str_replace('.', '_', $exercisePath)
        );

        $contents = view(
            ($stubViewPath),
            ['solution' => $solutionCode]
        )
            ->render();

        Storage::put('solution.rkt', $contents);
        $solutionPath = storage_path('app/solution.rkt');

        $command = new Command("raco test {$solutionPath}");

        $command->execute();

        $exitCode = $command->getExitCode();
        $output = $command->getExecuted()
            ? $command->getOutput()
            : $command->getError();

        return [
            'exit_code' => $exitCode,
            'output' => $output,
        ];
    }
}
