<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrontendExperienceTest extends TestCase
{
    public function test_every_interactive_document_loads_the_shared_frontend_bundle(): void
    {
        $excluded = [
            'emails\\tenant-credentials.blade.php',
            'tenant\\contract-pdf.blade.php',
        ];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $relativePath = $file->getRelativePathname();
            $contents = File::get($file->getPathname());

            if (! str_contains($contents, '<!DOCTYPE html>') || in_array($relativePath, $excluded, true)) {
                continue;
            }

            $this->assertStringContainsString('@vite(', $contents, $file->getRelativePathname());
            $this->assertStringContainsString('name="viewport"', $contents, $file->getRelativePathname());
        }
    }

    public function test_frontend_bundle_contains_accessibility_and_safety_controls(): void
    {
        $javascript = File::get(resource_path('js/app.js'));
        $css = File::get(public_path('css/panel.css'));

        $this->assertStringContainsString('skip-link', $javascript);
        $this->assertStringContainsString('mobile-nav-toggle', $javascript);
        $this->assertStringContainsString('confirm-dialog', $javascript);
        $this->assertStringContainsString("form.dataset.submitting === 'true'", $javascript);
        $this->assertStringContainsString("form.setAttribute('aria-busy', 'true')", $javascript);
        $this->assertStringContainsString('@media print', $css);
        $this->assertStringContainsString(':focus-visible', $css);
    }

    public function test_templates_do_not_depend_on_remote_frontend_cdns(): void
    {
        $templates = collect(File::allFiles(resource_path('views')))
            ->map(fn ($file) => File::get($file->getPathname()))
            ->implode("\n");

        foreach (['fonts.googleapis.com', 'fonts.gstatic.com', 'cdn.jsdelivr.net', 'cdnjs.cloudflare.com', 'unpkg.com'] as $host) {
            $this->assertStringNotContainsString($host, $templates);
        }
    }

    public function test_admin_navigation_matches_the_requested_modules(): void
    {
        $sidebar = File::get(resource_path('views/partials/admin-sidebar.blade.php'));

        $this->assertStringContainsString("route('profile.edit')", $sidebar);
        $this->assertStringContainsString('>Profile</a>', $sidebar);
        $this->assertStringNotContainsString("route('admin.system-health')", $sidebar);
        $this->assertStringNotContainsString("route('admin.suppliers.index')", $sidebar);
        $this->assertStringNotContainsString("route('admin.purchase-orders.index')", $sidebar);
        $this->assertStringNotContainsString("route('admin.inventory.index')", $sidebar);
    }
}
