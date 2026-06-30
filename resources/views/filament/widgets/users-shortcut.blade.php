{{-- resources/views/filament/widgets/users-shortcut.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section heading="Quick links">
        <div class="space-y-8">
            {{-- Blocco 1: Users, Media, Categories --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Users --}}
                <a
                    href="{{ url('/admin/users/users') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">
                                Users
                            </span>
                            <span class="text-sm text-gray-500">
                                Manage users and roles
                            </span>
                        </div>
                    </div>
                </a>

                {{-- Media --}}
                <a
                    href="{{ url('/admin/media') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.75 5.25h16.5v13.5H3.75V5.25zM8.25 10.5l2.25 3 3-4.5 4.5 6.75" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">
                                Media
                            </span>
                            <span class="text-sm text-gray-500">
                                Manage media library
                            </span>
                        </div>
                    </div>
                </a>

                {{-- Categories --}}
                <a
                    href="{{ url('/admin/categories') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M4.5 6.75h15m-15 4.5h9m-9 4.5h6" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">
                                Categories
                            </span>
                            <span class="text-sm text-gray-500">
                                Manage categories
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Separatore visivo tra blocchi --}}
            <div class="border-t border-gray-200"></div>

            {{-- Blocco 2: Activities, People, Institutions --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Activities --}}
                <a
                    href="{{ url('/admin/activities') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.75 5.25h16.5M3.75 9.75h16.5M3.75 14.25h10.5M3.75 18.75h6" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">
                                Activities
                            </span>
                            <span class="text-sm text-gray-500">
                                Manage activities
                            </span>
                        </div>
                    </div>
                </a>

                {{-- People --}}
                <a
                    href="{{ url('/admin/people') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">
                                People
                            </span>
                            <span class="text-sm text-gray-500">
                                Manage people profiles
                            </span>
                        </div>
                    </div>
                </a>

                {{-- Institutions --}}
                <a
                    href="{{ url('/admin/institutions') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 10.5L12 4.5l9 6v8.25A1.25 1.25 0 0 1 19.75 20H4.25A1.25 1.25 0 0 1 3 18.75V10.5z" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">
                                Institutions
                            </span>
                            <span class="text-sm text-gray-500">
                                Manage institutions
                            </span>
                        </div>
                    </div>
                </a>
            </div>

           {{-- Separatore visivo tra blocchi --}}
            <div class="border-t border-gray-200"></div>

            {{-- Blocco 3: FAQs, Projects, Research catalogue, Pages --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- FAQs --}}
                <a
                    href="{{ url('/admin/faqs') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.625 9.75a3.375 3.375 0 1 1 6.75 0c0 1.355-.804 2.396-1.878 3.096-.792.516-1.497 1.23-1.497 2.154v.405M12 18.75h.008" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">FAQs</span>
                            <span class="text-sm text-gray-500">Manage FAQs</span>
                        </div>
                    </div>
                </a>

                {{-- Projects --}}
                <a
                    href="{{ url('/admin/projects') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6.75h16.5v10.5H3.75V6.75z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 17.25v1.5h9v-1.5" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">Projects</span>
                            <span class="text-sm text-gray-500">Manage projects</span>
                        </div>
                    </div>
                </a>

                {{-- Research catalogue --}}
                <a
                    href="{{ url('/admin/research-catalogues') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.75c-2.5-1.5-6.5-1.5-9 0v12c2.5-1.5 6.5-1.5 9 0m0-12c2.5-1.5 6.5-1.5 9 0v12c-2.5-1.5-6.5-1.5-9 0m0-12v12" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">Catalogue Research</span>
                            <span class="text-sm text-gray-500">Manage catalogue</span>
                        </div>
                    </div>
                </a>
            </div>

                {{-- Separatore visivo tra blocchi --}}
                <div class="border-t border-gray-200"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Pages --}}
                <a
                    href="{{ url('/admin/pages') }}"
                    class="block rounded-2xl bg-white text-black px-6 py-6 shadow-sm border border-gray-200 hover:border-black hover:shadow-md transition"
                >
                    <div class="flex items-center gap-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 21H7.5A2.25 2.25 0 0 1 5.25 18.75V5.25A2.25 2.25 0 0 1 7.5 3h7.379a2.25 2.25 0 0 1 1.591.659l2.371 2.371A2.25 2.25 0 0 1 21 7.621V18.75A2.25 2.25 0 0 1 19.5 21Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 10.5h9M8.25 14.25h9M8.25 18h6" />
                            </svg>
                        </span>

                        <div class="flex flex-col">
                            <span class="text-base font-semibold">Pages</span>
                            <span class="text-sm text-gray-500">Manage pages</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
