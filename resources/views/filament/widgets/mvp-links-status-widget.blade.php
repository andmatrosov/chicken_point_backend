@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview">
    <div class="fi-wi-stats-overview-stat">
        <div class="fi-wi-stats-overview-stat-content">
            <div class="fi-wi-stats-overview-stat-label-ctn">
                {{ \Filament\Support\generate_icon_html(Heroicon::OutlinedLink) }}

                <span class="fi-wi-stats-overview-stat-label">
                    Статусы активных ссылок MVP
                </span>
            </div>

            <div class="mt-4 space-y-5">
                @foreach ($statuses as $status)
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: start; column-gap: 1rem;">
                        <div style="min-width: 0;margin-bottom: 10px;">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ $status['version_label'] }}
                            </p>

                            @if (filled($status['mvp_link']))
                                <a
                                    href="{{ $status['mvp_link'] }}"
                                    class="mt-2 block text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                                    style="overflow-wrap: anywhere;"
                                    target="_blank"
                                    rel="noreferrer"
                                    title="{{ $status['mvp_link'] }}"
                                >
                                    {{ $status['mvp_link'] }}
                                </a>
                            @else
                                <span class="mt-2 block text-sm text-gray-600 dark:text-gray-400">Не указана</span>
                            @endif
                        </div>

                        <x-filament::badge
                            :color="$status['is_active'] ? 'success' : 'gray'"
                            style="white-space: nowrap; justify-self: end;"
                        >
                            {{ $status['is_active'] ? 'Активна' : 'Неактивна' }}
                        </x-filament::badge>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
