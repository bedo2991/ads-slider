<div>
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <div class="row justify-content-center">
        <div class="col-md-9">
            <form wire:submit="@if($form->realm) updateRealm @else createRealm @endif">
                <x-forms.inputs.text name="form.name" placeholder="{{__('My Realm Name')}}" required label="{{__('Name')}}">
                    <x-forms.helpers.help
                        text="{{__('Please keep it short')}}" />
                </x-forms.inputs.text>
                <x-forms.inputs.select name="form.locale" label="{{__('Locale')}}">
                    <x-slot:options>
                        @foreach ([null, 'de', 'en', 'it'] as $loc)
                        <option value="{{ $loc }}">
                            {{ $loc }}
                        </option>
                        @endforeach
                        </x-slot>
                        <x-forms.helpers.help
                            text="{{__('This value can be overridden by the single monitors configuration, or by a _locale_ query parameter')}}" />
                </x-forms.inputs.select>
                <x-forms.inputs.text name="form.nina_ars" placeholder="160700000000" label="{{__('Nina ARS')}}">
                    <x-forms.helpers.help
                        text="{{__('ARS means Amtlicher Regionalschlüssel. Further information: https://nina.api.bund.dev/')}}" />
                </x-forms.inputs.text>

                <div class="row">
                    <div class="col-md-6">
                        <x-forms.inputs.text name="form.lat" label="{{__('Latitude')}}" placeholder="52.5200" />
                    </div>
                    <div class="col-md-6">
                        <x-forms.inputs.text name="form.lon" label="{{__('Longitude')}}" placeholder="13.4050" />
                    </div>
                </div>

                <x-forms.inputs.text name="form.ow_api_key" placeholder="<your secret>" label="{{__('Openweather API key')}}">
                    <x-forms.helpers.help
                        text="{{__('Further information: https://openweathermap.org/appid')}}" />
                </x-forms.inputs.text>
                <x-forms.inputs.text name="form.ow_city_id" label="{{__('Openweather City ID')}}">
                    <x-forms.helpers.help
                        text="{{__('Find your city ID at https://openweathermap.org/find')}}" />
                </x-forms.inputs.text>
                <x-forms.inputs.text name="form.orders_pull" readonly label="{{__('Orders pull')}}">
                    <x-forms.helpers.help
                        text="{{__('If a GET is done to this URL: :url Orders Link will be queried immediately and the results shown prominently. Keep the URL in good hands.',['url'=>route('realm.requestpull',['realm'=>$form->orders_pull?$form->orders_pull:'order_pull_example'])])}}" />
                </x-forms.inputs.text>
                <button class="btn btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="{{ __('Generate a new random link') }}" wire:click="refreshOrdersPull"
                    @if ($form->orders_pull) wire:confirm="All the external systems calling the current URL will not be able to force an update. Are you sure?" @endif
                    type="button"><i class="fas fa-fw fa-arrows-rotate"></i></button>

                @if ($form->orders_pull)
                <button @class(['btn btn-outline-danger']) data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="{{ __('Remove Orders Pull') }}" wire:click="deleteOrdersPull"
                    wire:confirm="All the external systems calling the current URL will not be able to force an update. Are you sure?"
                    type="button"><i class="fas fa-fw fa-trash"></i></button>
                @endif

                <x-forms.inputs.text name="form.orders_link" type="url" label="{{__('Orders source link')}}" />
                <x-forms.inputs.number name="form.orders_polling_frequency" label="{{__('Polling frequency in seconds')}}">
                    <x-forms.helpers.help
                        text="{{__('If 0, polling is disabled')}}" />
                </x-forms.inputs.number>

                <div class="my-3" />

                <x-forms.buttons.primary text="{{ __('Save Realm') }}" />
                {{-- <x-forms.buttons.reset text="{{ __('Clear Form') }}" /> --}}
                @isset($deleteButton)
                <x-forms.buttons.delete wire:click.prevent="{{$deleteButton}}" text="{{ __('Delete Realm') }}" />
                @endisset
            </form>
        </div>
        <div class="col-md-9">
            <h3>{{__('All Users of this realm')}}</h3>
            @forelse ($form->realm?->users as $user)
            <div class="card mb-2">
                <div class="card-body">
                    <h5 class="card-title">{{ $user->name }}</h5>
                    @if ($user->is_admin)
                    <span class="badge text-bg-danger align-text-top" data-bs-placement="bottom"
                        data-bs-toggle="tooltip"
                        data-bs-title="{{ __('Server Admin') }}"><i
                            class="fas fa-fw fa-crown"></i></span>
                    @elseif ($user->is_realm_admin)
                    <span class="badge text-bg-warning align-text-top" data-bs-placement="bottom"
                        data-bs-toggle="tooltip" data-bs-title="{{ __('Admin') }}"><i
                            class="fas fa-fw fa-crown"></i></span>
                    @else
                    <span class="badge text-bg-info align-text-top" data-bs-placement="bottom"
                        data-bs-toggle="tooltip" data-bs-title="{{ __('User') }}"><i
                            class="fas fa-fw fa-user"></i></span>
                    @endif
                    @can ('update',$user)
                    <a href="{{route('users.edit',['user'=>$user->id])}}" target="_blank">
                        <button class="btn btn-outline-secondary float-end"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ __('Edit User') }}">
                            <i class="fas fa-fw fa-edit"></i>
                        </button>
                    </a>
                    @endcan
                </div>
            </div>
            @empty
            <div class="alert alert-info">
                {{ __('No users found for this realm.') }}
            </div>
            @endforelse
        </div>
    </div>
</div>