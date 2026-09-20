<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Template;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Template::class);

        $templates = Template::ofRealm(auth()->user()->realm_id)->with('user')->paginate(10);

        return view('templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Template::class);
        $allMenus = Menu::ofRealm(auth()->user()->realm_id)->orderby('name')->get(['id', 'name']);

        return view('templates.create', compact('allMenus'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Factory|View
     */
    public function edit(Template $template)
    {
        $this->authorize('update', $template);
        $allMenus = Menu::ofRealm(auth()->user()->realm_id)->orderby('name')->get(['id', 'name']);

        return view('templates.edit', compact('template', 'allMenus'));
    }
}
