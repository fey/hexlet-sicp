<div>
    <span wire:dirty wire:target="output">Updating...</span>
    @if ($checkResult === null)
        <form wire:submit.prevent="check">
            <div class="form-group">
                <textarea
                    class="form-control"
                    name="solution"
                    wire:model="solutionCode"
                    placeholder="@lang('solution.placeholder')"
                    wire:loading.attr="disabled"
                    wire:target="check"
                    cols="120"
                    rows="20"
                    required
                ></textarea>
            </div>
            <div class="d-flex justify-content-end">
                <button wire:click="check" class="btn btn-primary" type="button" wire:loading.attr="disabled"
                        wire:target="check">
                    <div wire:loading wire:target="check">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Loading...
                    </div>
                    <div wire:loading.remove wire:target="check">
                        Check
                    </div>
                </button>
            </div>
        </form>
    @else
        <pre>{{ $checkResult['output'] }}</pre>
    @endif
</div>
