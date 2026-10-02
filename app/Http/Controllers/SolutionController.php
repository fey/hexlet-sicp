<?php

namespace App\Http\Controllers;

use App\DTO\Solution\SolutionShowPageData;
use App\Models\Exercise;
use App\Models\Solution;
use App\Presenters\ExercisePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Contracts\View\View;
use Inertia\Response;

class SolutionController extends Controller
{
    public function index(Request $request): View
    {
        $filter = array_merge(['name' => null, 'exercise_id' => null], (array)$request->input('filter', []));

        $solutions = QueryBuilder::for(Solution::versioned())
            ->allowedFilters(
                AllowedFilter::exact('exercise_id'),
                AllowedFilter::partial('user.name'),
                AllowedFilter::exact('user_id'),
            )
            ->with(['user', 'exercise'])
            ->whereHas('user')
            ->latest('solutions.created_at')
            ->paginate(50);

        $exercises = Exercise::orderBy('id')->get();
        $exerciseTitles = ExercisePresenter::collection($exercises)
            ->pluck('fullTitle', 'id');

        $solutionAuthors = [];
        if (Auth::user()) {
            $solutionAuthors = [Auth::user()->id => Auth::user()->name];
        }

        return view(
            'solution.index',
            [
                'solutions' => $solutions,
                'filter'    => $filter,
                'exerciseTitles'  => $exerciseTitles,
                'solutionAuthors' => $solutionAuthors,
            ]
        );
    }

    public function show(Solution $solution): Response
    {
        if (!$solution->user()->exists()) {
            abort(404);
        }

        $page = SolutionShowPageData::fromExercise($solution->exercise, $solution->user);

        // Имя компонента явное: User\SolutionController@show рендерит ту же страницу.
        return $this->inertia($page->toArray(), 'Solution/Show')
            ->withViewData(['robots' => 'noindex, nofollow', 'description' => $page->description()]);
    }
}
