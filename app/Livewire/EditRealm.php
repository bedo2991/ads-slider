<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\RealmForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Realm;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EditRealm extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public RealmForm $form;

    public function mount(?Realm $realm)
    {
        if ($realm && $realm->exists) {
            $this->authorize('update', $realm);
        } else {
            $this->authorize('create', Realm::class);
        }

        $this->form->setRealm($realm);
    }

    public function deleteOrdersPull()
    {
        $this->authorize('update', $this->form->realm);
        $this->form->realm->removeOrdersPull();
        event(new SecurityAuditEvent(
            action: 'realm.orders_pull_deleted',
            description: "Orders pull token deleted for Realm ID: {$this->form->realm->id}",
            userId: auth()->id(),
            realmId: $this->form->realm->id
        ));
        $this->redirectRoute('realms.edit', $this->form->realm->id);
    }

    public function refreshOrdersPull()
    {
        $this->authorize('update', $this->form->realm);
        $this->form->realm->updateOrdersPull();
        event(new SecurityAuditEvent(
            action: 'realm.orders_pull_refreshed',
            description: "Orders pull token refreshed for Realm ID: {$this->form->realm->id}",
            userId: auth()->id(),
            realmId: $this->form->realm->id
        ));
        $this->redirectRoute('realms.edit', $this->form->realm->id);
    }

    public function updateRealm()
    {
        $this->authorize('update', $this->form->realm);
        $this->form->saveRealm();
        event(new SecurityAuditEvent(
            action: 'realm.updated',
            description: "Realm configuration updated for Realm ID: {$this->form->realm->id} ({$this->form->realm->name})",
            userId: auth()->id(),
            realmId: $this->form->realm->id
        ));
        flash(__('The realm has been updated'))->success();
        Log::channel('crud')->warning('Realm updated', [
            'realm' => $this->form->realm,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('realms.edit', $this->form->realm->id);

    }

    public function createRealm()
    {
        $this->authorize('create', Realm::class);
        $this->form->saveRealm();
        event(new SecurityAuditEvent(
            action: 'realm.created',
            description: "New Realm created: {$this->form->realm->name} (ID: {$this->form->realm->id})",
            userId: auth()->id(),
            realmId: $this->form->realm->id
        ));
        flash(__('The realm has been created'))->success();
        Log::channel('crud')->warning('Realm created', [
            'realm' => $this->form->realm,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('realms.index');
    }

    public function render()
    {
        return view('livewire.edit-realm');
    }
}
