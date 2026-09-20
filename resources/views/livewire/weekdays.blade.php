<div>
    <div class="mb-3">
        <label for="{{ $name }}" class="form-label">
            @isset($label)
                {{ $label }}
            @else
                {{ ucfirst($name) }}
            @endisset
        </label>
        <div class="col-12 btn-group btn-group-sm" role="group" aria-label="Default button group">
            <input wire:model="moSelected" wire:change="updateString" type="checkbox" class="btn-check" id="btncheck-mon"
                autocomplete="off">
            <label class="btn btn-outline-primary" for="btncheck-mon">{{ substr(__('Monday'), 0, 3) }}<span
                    class="d-none d-sm-inline">{{ substr(__('Monday'), 3) }}</span></label>
            <input wire:model="tuSelected" wire:change="updateString" type="checkbox" class="btn-check"
                id="btncheck-tue" autocomplete="off">
            <label class="btn btn-outline-primary" for="btncheck-tue">{{ substr(__('Tuesday'), 0, 3) }}<span
                    class="d-none d-sm-inline">{{ substr(__('Tuesday'), 3) }}</span></label>
            <input wire:model="weSelected" wire:change="updateString" type="checkbox" class="btn-check"
                id="btncheck-wed" autocomplete="off">
            <label class="btn btn-outline-primary" for="btncheck-wed">{{ substr(__('Wednesday'), 0, 3) }}<span
                    class="d-none d-sm-inline">{{ substr(__('Wednesday'), 3) }}</span></label>
            <input wire:model="thSelected" wire:change="updateString" type="checkbox" class="btn-check"
                id="btncheck-thu" autocomplete="off">
            <label class="btn btn-outline-primary" for="btncheck-thu">{{ substr(__('Thursday'), 0, 3) }}<span
                    class="d-none d-sm-inline">{{ substr(__('Thursday'), 3) }}</span></label>
            <input wire:model="frSelected" wire:change="updateString" type="checkbox" class="btn-check"
                id="btncheck-fri" autocomplete="off">
            <label class="btn btn-outline-primary" for="btncheck-fri">{{ substr(__('Friday'), 0, 3) }}<span
                    class="d-none d-sm-inline">{{ substr(__('Friday'), 3) }}</span></label>
            <input wire:model="saSelected" wire:change="updateString" type="checkbox" class="btn-check"
                id="btncheck-sat" autocomplete="off">
            <label class="btn btn-outline-primary" for="btncheck-sat">{{ substr(__('Saturday'), 0, 3) }}<span
                    class="d-none d-sm-inline">{{ substr(__('Saturday'), 3) }}</span></label>
            <input wire:model="suSelected" wire:change="updateString" type="checkbox" class="btn-check"
                id="btncheck-sun" autocomplete="off">
            <label class="btn btn-outline-primary" for="btncheck-sun">{{ substr(__('Sunday'), 0, 3) }}<span
                    class="d-none d-sm-inline">{{ substr(__('Sunday'), 3) }}</span></label>
        </div>
        <div class="col-12 justify-content-evenly mt-4">
            <button class="btn btn-sm btn-success" wire:click.prevent="selectAll">{{ __('Select all Days') }}</button>
            <button class="btn btn-sm btn-secondary" wire:click.prevent="selectNone">{{ __('None') }}</button>
        </div>
        @error($name)
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
        {{ $slot }}
    </div>
</div>
