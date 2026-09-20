<?php

namespace Tests\Feature;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tests\TestCase;

class NotExistingRoutesTest extends TestCase
{
    // EVENTS
    public function test_events_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('events.destroy', 1);
    }

    public function test_events_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('events.store', 1);
    }

    public function test_events_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('events.update', 1);
    }

    // TEMPLATE
    public function test_templates_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('templates.destroy', 1);
    }

    public function test_templates_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('templates.store', 1);
    }

    public function test_templates_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('templates.update', 1);
    }

    // MONITOR
    public function test_monitors_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('monitors.destroy', 1);
    }

    public function test_monitors_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('monitors.store', 1);
    }

    public function test_monitors_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('monitors.update', 1);
    }

    // VIDSLIDES
    public function test_vid_slides_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('vidSlides.destroy', 1);
    }

    public function test_vid_slides_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('vidSlides.store', 1);
    }

    public function test_vid_slides_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('vidSlides.update', 1);
    }

    // PIC_SLIDES
    public function test_pic_slides_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('picSlides.destroy', 1);
    }

    public function test_pic_slides_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('picSlides.store', 1);
    }

    public function test_pic_slides_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('picSlides.update', 1);
    }

    // EVENTS_IMPORT
    public function test_events_import_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('eventsImports.destroy', 1);
    }

    public function test_events_import_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('eventsImports.store', 1);
    }

    public function test_events_import_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('eventsImports.update', 1);
    }
}
