<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\TemplateForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Template;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EditTemplate extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public TemplateForm $form;

    public $allMenus;

    public function createTemplate()
    {
        $this->authorize('create', Template::class);
        $this->form->storeTemplate();
        flash(__('The template has been created'))->success();
        Log::channel('crud')->info('Template created', [
            'template' => $this->form->template,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('templates.index');
    }

    public function updateTemplate()
    {
        $this->authorize('update', $this->form->template);
        $this->form->storeTemplate();
        flash(__('The template has been updated'))->success();
        Log::channel('crud')->info('Template updated', [
            'template' => $this->form->template,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('templates.index');
    }

    public function deleteTemplate()
    {
        $this->authorize('delete', $this->form->template);
        $template = $this->form->template;
        $this->form->deleteTemplate();

        event(new SecurityAuditEvent(
            action: 'template.deleted',
            description: "Template '{$template->name}' (ID: {$template->id}) deleted by user ID: ".auth()->id(),
            userId: auth()->id(),
            realmId: $template->realm_id,
            context: ['template_id' => $template->id, 'name' => $template->name]
        ));

        flash(__('The template has been deleted'))->success();
        Log::channel('crud')->warning('Template deleted', [
            'template' => $template,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('templates.index');
    }

    public function mount($allMenus, ?Template $template)
    {
        if ($template && $template->exists) {
            $this->authorize('update', $template);
        } else {
            $this->authorize('create', Template::class);
        }

        $this->form->setTemplate($template);
        $this->allMenus = $allMenus;
    }

    public function render()
    {
        return view('livewire.edit-template');
    }
}
