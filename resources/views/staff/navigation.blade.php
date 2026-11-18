<nav aria-label="Staff navigation" class="space-y-6">
    <div>
        <p class="staff-nav-heading">Workspace</p>
        <a class="staff-nav-link {{ request()->routeIs('staff.dashboard') ? 'staff-nav-active' : '' }}" href="{{ route('staff.dashboard') }}" @if(request()->routeIs('staff.dashboard')) aria-current="page" @endif>
            <x-icon name="layout-grid" class="h-[18px] w-[18px]" /><span>Dashboard</span>
        </a>
        <a class="staff-nav-link {{ request()->routeIs('staff.articles.*') ? 'staff-nav-active' : '' }}" href="{{ route('staff.articles.index') }}" @if(request()->routeIs('staff.articles.*')) aria-current="page" @endif>
            <x-icon name="book-open" class="h-[18px] w-[18px]" /><span>Articles</span>
        </a>
        <a class="staff-nav-link {{ request()->routeIs('staff.content-readiness') ? 'staff-nav-active' : '' }}" href="{{ route('staff.content-readiness') }}" @if(request()->routeIs('staff.content-readiness')) aria-current="page" @endif>
            <x-icon name="check-circle" class="h-[18px] w-[18px]" /><span>Content quality</span>
        </a>
    </div>
    @can('manage-staff')
    <div>
        <p class="staff-nav-heading">Publishing</p>
        <a class="staff-nav-link {{ request()->routeIs('staff.editorial-plan') ? 'staff-nav-active' : '' }}" href="{{ route('staff.editorial-plan') }}" @if(request()->routeIs('staff.editorial-plan')) aria-current="page" @endif>
            <x-icon name="calendar-days" class="h-[18px] w-[18px]" /><span>Editorial plan</span>
        </a>
        <a class="staff-nav-link {{ request()->routeIs('staff.website.homepage') ? 'staff-nav-active' : '' }}" href="{{ route('staff.website.homepage') }}" @if(request()->routeIs('staff.website.homepage')) aria-current="page" @endif>
            <x-icon name="monitor" class="h-[18px] w-[18px]" /><span>Homepage</span>
        </a>
        <a class="staff-nav-link {{ request()->routeIs('staff.pages.*') ? 'staff-nav-active' : '' }}" href="{{ route('staff.pages.index') }}" @if(request()->routeIs('staff.pages.*')) aria-current="page" @endif>
            <x-icon name="files" class="h-[18px] w-[18px]" /><span>Website pages</span>
        </a>
    </div>
    @php
        $showAdditionalTools = request()->routeIs('staff.growth*', 'staff.users.*', 'staff.website.regions*', 'staff.website.settings*', 'staff.launch-readiness');
    @endphp
    <details class="staff-more-tools" @if($showAdditionalTools) open @endif>
        <summary class="staff-more-trigger">
            <span class="inline-flex items-center gap-3"><x-icon name="settings" class="h-[18px] w-[18px]" />More tools</span>
            <x-icon name="chevron-down" class="h-4 w-4 transition-transform" />
        </summary>
        <div class="mt-2 space-y-1 pl-1">
            <a class="staff-nav-link {{ request()->is('staff/growth/seo') ? 'staff-nav-active' : '' }}" href="{{ route('staff.growth', 'seo') }}">SEO tools</a>
            <a class="staff-nav-link {{ request()->is('staff/growth/analytics') ? 'staff-nav-active' : '' }}" href="{{ route('staff.growth', 'analytics') }}">Analytics</a>
            <a class="staff-nav-link {{ request()->is('staff/growth/revenue') ? 'staff-nav-active' : '' }}" href="{{ route('staff.growth', 'revenue') }}">Revenue</a>
            <a class="staff-nav-link {{ request()->is('staff/growth/integrations') ? 'staff-nav-active' : '' }}" href="{{ route('staff.growth', 'integrations') }}">Integrations</a>
            <a class="staff-nav-link {{ request()->routeIs('staff.website.regions*') ? 'staff-nav-active' : '' }}" href="{{ route('staff.website.regions') }}">Regional editions</a>
            <a class="staff-nav-link {{ request()->routeIs('staff.website.settings*') ? 'staff-nav-active' : '' }}" href="{{ route('staff.website.settings') }}">Site settings</a>
            <a class="staff-nav-link {{ request()->routeIs('staff.users.*') ? 'staff-nav-active' : '' }}" href="{{ route('staff.users.index') }}">Team</a>
            <a class="staff-nav-link {{ request()->routeIs('staff.launch-readiness') ? 'staff-nav-active' : '' }}" href="{{ route('staff.launch-readiness') }}">Launch checks</a>
        </div>
    </details>
    @endcan
    <div class="staff-account-link">
        <a class="staff-nav-link {{ request()->routeIs('staff.profile.*') ? 'staff-nav-active' : '' }}" href="{{ route('staff.profile.edit') }}" @if(request()->routeIs('staff.profile.*')) aria-current="page" @endif><x-icon name="settings" class="h-[18px] w-[18px]" /><span>My profile</span></a>
        <a class="staff-nav-link {{ request()->routeIs('staff.security.*') ? 'staff-nav-active' : '' }}" href="{{ route('staff.security.index') }}" @if(request()->routeIs('staff.security.*')) aria-current="page" @endif>
            <x-icon name="shield-check" class="h-[18px] w-[18px]" /><span>Account security</span>
        </a>
    </div>
</nav>
