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
        $this->assertStringContainsString('.sidebar-nav{display:flex;flex-direction:column;gap:4px;flex:1;min-height:0;overflow-y:auto', $css);
        $this->assertStringContainsString('.sidebar-footer{border-top:1px solid #e2e8f0;padding-top:16px;margin-top:12px;flex-shrink:0}', $css);
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

    public function test_templates_do_not_emit_byte_order_marks(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            $this->assertFalse(str_starts_with(File::get($file->getPathname()), "\xEF\xBB\xBF"), $file->getRelativePathname());
        }
    }

    public function test_admin_navigation_matches_the_requested_modules(): void
    {
        $sidebar = File::get(resource_path('views/partials/admin-sidebar.blade.php'));

        $this->assertStringContainsString("route('profile.edit')", $sidebar);
        $this->assertStringContainsString("route('admin.dashboard')", $sidebar);
        $this->assertStringNotContainsString("route('manager.products')", $sidebar);
        $this->assertStringNotContainsString("route('admin.system-health')", $sidebar);
        $this->assertStringNotContainsString("route('admin.suppliers.index')", $sidebar);
        $this->assertStringNotContainsString("route('admin.purchase-orders.index')", $sidebar);
        $this->assertStringNotContainsString("route('admin.inventory.index')", $sidebar);
    }

    public function test_all_manager_navigation_items_have_icons(): void
    {
        $sidebar = File::get(resource_path('views/partials/manager-sidebar.blade.php'));
        preg_match_all('/<a\s+href="[^"]+"\s+class="nav-item[^>]+>(.*?)<\/a>/s', $sidebar, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $item) {
            $this->assertStringContainsString('<svg', $item);
        }
    }

    public function test_role_navigation_has_profile_and_current_page_states(): void
    {
        $managerSidebar = File::get(resource_path('views/partials/manager-sidebar.blade.php'));
        $tenantSidebar = File::get(resource_path('views/partials/tenant-sidebar.blade.php'));
        $applications = File::get(resource_path('views/manager/rental-applications.blade.php'));
        $notifications = File::get(resource_path('views/partials/notification-bell.blade.php'));

        $this->assertStringContainsString("route('profile.edit')", $managerSidebar);
        $this->assertStringContainsString("route('profile.edit')", $tenantSidebar);
        $this->assertStringContainsString("['active' => 'applications']", $applications);
        $this->assertStringContainsString("=== 'notifications' ? 'active'", $notifications);
        $this->assertStringContainsString('aria-expanded', $notifications);
    }

    public function test_public_navigation_contains_no_placeholder_links(): void
    {
        $templates = collect(File::allFiles(resource_path('views')))
            ->map(fn ($file) => File::get($file->getPathname()))
            ->implode("\n");

        $this->assertStringNotContainsString('href="#"', $templates);
        $this->assertStringContainsString("route('terms')", File::get(resource_path('views/welcome.blade.php')));
    }
}
