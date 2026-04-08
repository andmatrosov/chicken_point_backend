@php
    use Filament\Support\Icons\Heroicon;
@endphp

<style>
    .admin-overview-stats {
        height: 100%;
    }

    .admin-overview-stats-grid {
        display: grid;
        gap: 1rem;
        align-items: stretch;
        height: 100%;
    }

    .admin-overview-stats-card {
        height: 100%;
    }

    .admin-overview-stats-card .fi-wi-stats-overview-stat-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    @media (min-width: 768px) {
        .admin-overview-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<x-filament-widgets::widget class="fi-wi-stats-overview admin-overview-stats" style="height: 100%;">
    <div class="admin-overview-stats-grid">
        <div class="fi-wi-stats-overview-stat admin-overview-stats-card">
            <div class="fi-wi-stats-overview-stat-content">
                <div class="fi-wi-stats-overview-stat-label-ctn">
                    {{ \Filament\Support\generate_icon_html(Heroicon::OutlinedUsers) }}

                    <span class="fi-wi-stats-overview-stat-label">
                        Количество участников
                    </span>
                </div>

                <div class="fi-wi-stats-overview-stat-value">
                    {{ $participantsCount }}
                </div>
            </div>
        </div>

        <div class="fi-wi-stats-overview-stat admin-overview-stats-card">
            <div class="fi-wi-stats-overview-stat-content">
                <div class="fi-wi-stats-overview-stat-label-ctn">
                    {{ \Filament\Support\generate_icon_html(Heroicon::OutlinedGift) }}

                    <span class="fi-wi-stats-overview-stat-label">
                        Активные призы
                    </span>
                </div>

                <div class="fi-wi-stats-overview-stat-value">
                    {{ $activePrizesCount }}
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
