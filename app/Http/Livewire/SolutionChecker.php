<?php

namespace App\Http\Livewire;

use App\Models\Exercise;
use App\Services\SolutionChecker as SolutionCheckerService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SolutionChecker extends Component
{
    public string $solutionCode = '';
    public Exercise $exercise;
    public ?array $checkResult = null;

    public function check(SolutionCheckerService $checker): void
    {
        $this->checkResult = $checker->check($this->exercise->path, $this->solutionCode);
    }

    public function render(): View
    {
        return view('livewire.solution-checker');
    }
}
