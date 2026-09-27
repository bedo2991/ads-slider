<?php

namespace Tests\Feature;

use App\Livewire\EditMonitor;
use App\Livewire\EditRealm;
use App\Models\Monitor;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MarketingSentencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_realm_can_save_and_update_marketing_sentences(): void
    {
        $realm = Realm::factory()->create([
            'marketing_sentences' => null,
        ]);
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);

        $inputText = "Sentence One\n  Sentence Two  \n\nSentence Three\n";

        Livewire::actingAs($user)
            ->test(EditRealm::class, ['realm' => $realm])
            ->set('form.marketing_sentences', $inputText)
            ->call('updateRealm')
            ->assertHasNoErrors();

        $fresh = $realm->fresh();
        $this->assertEquals(['Sentence One', 'Sentence Two', 'Sentence Three'], $fresh->marketing_sentences);

        // Test clearing sentences
        Livewire::actingAs($user)
            ->test(EditRealm::class, ['realm' => $fresh])
            ->set('form.marketing_sentences', '')
            ->call('updateRealm')
            ->assertHasNoErrors();

        $this->assertNull($realm->fresh()->marketing_sentences);
    }

    public function test_monitor_can_save_and_update_custom_marketing_sentences(): void
    {
        $realm = Realm::factory()->create([
            'marketing_sentences' => ['Realm sentence 1', 'Realm sentence 2'],
        ]);
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'marketing_sentences' => null,
        ]);

        $customText = "Custom Sentence A\nCustom Sentence B";

        Livewire::actingAs($user)
            ->test(EditMonitor::class, ['monitor' => $monitor])
            ->set('form.marketing_sentences', $customText)
            ->call('updateMonitor')
            ->assertHasNoErrors();

        $fresh = $monitor->fresh();
        $this->assertEquals(['Custom Sentence A', 'Custom Sentence B'], $fresh->marketing_sentences);
        $this->assertEquals(['Custom Sentence A', 'Custom Sentence B'], $fresh->getEffectiveMarketingSentences());

        // Test clearing monitor sentences falls back to realm
        Livewire::actingAs($user)
            ->test(EditMonitor::class, ['monitor' => $fresh])
            ->set('form.marketing_sentences', '')
            ->call('updateMonitor')
            ->assertHasNoErrors();

        $this->assertNull($monitor->fresh()->marketing_sentences);
        $this->assertEquals(['Realm sentence 1', 'Realm sentence 2'], $monitor->fresh()->getEffectiveMarketingSentences());
    }

    public function test_effective_marketing_sentences_resolution_and_fallback(): void
    {
        $realmWithSentences = Realm::factory()->create([
            'marketing_sentences' => ['Realm standard sentence'],
        ]);
        $realmWithoutSentences = Realm::factory()->create([
            'marketing_sentences' => null,
        ]);

        // Monitor with custom sentences overrides realm
        $monitorOverride = Monitor::factory()->create([
            'realm_id' => $realmWithSentences->id,
            'marketing_sentences' => ['Monitor override sentence'],
        ]);
        $this->assertEquals(['Monitor override sentence'], $monitorOverride->getEffectiveMarketingSentences());

        // Monitor without sentences falls back to realm standard
        $monitorFallback = Monitor::factory()->create([
            'realm_id' => $realmWithSentences->id,
            'marketing_sentences' => null,
        ]);
        $this->assertEquals(['Realm standard sentence'], $monitorFallback->getEffectiveMarketingSentences());

        // Monitor and realm both without sentences returns empty array
        $monitorEmpty = Monitor::factory()->create([
            'realm_id' => $realmWithoutSentences->id,
            'marketing_sentences' => null,
        ]);
        $this->assertEquals([], $monitorEmpty->getEffectiveMarketingSentences());
    }

    public function test_collect_data_disables_closed_marketing_when_no_sentences_configured(): void
    {
        $realm = Realm::factory()->create([
            'marketing_sentences' => null,
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'show_we_are_closed_marketing' => true,
            'marketing_sentences' => null,
        ]);

        $response = $this->get('Content/'.$monitor->api_token.'/data.json');
        $response->assertOk();
        $data = $response->json();

        // Effective sentences should be empty
        $this->assertEquals([], $data['marketing_sentences']);
        // show_we_are_closed_marketing should count as disabled
        $this->assertFalse($data['m']['show_we_are_closed_marketing']);
    }

    public function test_collect_data_preserves_closed_marketing_when_sentences_are_configured(): void
    {
        $realm = Realm::factory()->create([
            'marketing_sentences' => ['Standard realm marketing sentence'],
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'show_we_are_closed_marketing' => true,
            'marketing_sentences' => null,
        ]);

        $response = $this->get('Content/'.$monitor->api_token.'/data.json');
        $response->assertOk();
        $data = $response->json();

        // Effective sentences should come from realm
        $this->assertEquals(['Standard realm marketing sentence'], $data['marketing_sentences']);
        $this->assertTrue($data['m']['show_we_are_closed_marketing']);
    }

    public function test_collect_data_uses_monitor_custom_sentences_when_configured(): void
    {
        $realm = Realm::factory()->create([
            'marketing_sentences' => ['Realm sentence'],
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'show_we_are_closed_marketing' => true,
            'marketing_sentences' => ['Monitor specific sentence'],
        ]);

        $response = $this->get('Content/'.$monitor->api_token.'/data.json');
        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(['Monitor specific sentence'], $data['marketing_sentences']);
        $this->assertTrue($data['m']['show_we_are_closed_marketing']);
    }

    public function test_display_endpoint_includes_marketing_sentences_in_view_data(): void
    {
        $realm = Realm::factory()->create([
            'marketing_sentences' => ['View test sentence'],
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'show_we_are_closed_marketing' => true,
            'marketing_sentences' => null,
        ]);

        $response = $this->get(route('showEventsToken', $monitor->api_token));
        $response->assertOk();
        $response->assertViewIs('showevents');
        $viewData = $response->viewData('data');

        $this->assertEquals(['View test sentence'], $viewData['marketing_sentences']);
        $this->assertTrue($viewData['m']['show_we_are_closed_marketing']);
    }

    public function test_display_endpoint_disables_closed_marketing_when_no_sentences(): void
    {
        $realm = Realm::factory()->create([
            'marketing_sentences' => null,
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'show_we_are_closed_marketing' => true,
            'marketing_sentences' => null,
        ]);

        $response = $this->get(route('showEventsToken', $monitor->api_token));
        $response->assertOk();
        $viewData = $response->viewData('data');

        $this->assertEquals([], $viewData['marketing_sentences']);
        $this->assertFalse($viewData['m']['show_we_are_closed_marketing']);
    }
}
