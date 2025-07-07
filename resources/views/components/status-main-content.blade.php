@props([
    'user',
    'handle',
    'period_start',
    'period_end',
    'period_days',
    'total_posts',
    'days_with_posts',
    'days_without_posts',
    'average_posts_per_day',
    'max_posts_per_day',
    'max_posts_per_day_date',
    'total_likes',
    'total_replies',
    'total_reposts',
    'follower_following_ratio',
    'chart_data_all',
    'chart_data_30',
    'chart_data_60',
    'chart_data_90',
])

<div class="lg:w-2/3">
    <div class="space-y-4">
        <div class="mt-8 bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4">全体統計</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div><span
                        class="font-semibold">Bluesky登録日時:</span> {{ $user->registered_at ? $user->registered_at->format('Y-m-d H:i:s') : 'N/A' }}
                </div>
                <div>
                    <span class="font-semibold">Bluelog上における記録期間:</span>
                    @php
                        $period_start_carbon = $period_start instanceof \Carbon\Carbon
                            ? $period_start
                            : \Carbon\Carbon::parse($period_start);
                        $period_end_carbon = $period_end instanceof \Carbon\Carbon
                            ? $period_end
                            : \Carbon\Carbon::parse($period_end);
                    @endphp
                    {{ $period_start_carbon ? $period_start_carbon->format('Y-m-d') . ' - ' . $period_end_carbon->format('Y-m-d') : 'N/A' }}
                    ({{ number_format($period_days) }} 日間)
                </div>
                <div><span class="font-semibold">総投稿数:</span> {{ number_format($total_posts) }}</div>
                <div><span class="font-semibold">つぶやいた日数:</span> {{ number_format($days_with_posts) }}</div>
                <div><span class="font-semibold">つぶやかなかった日数:</span> {{ number_format($days_without_posts) }}</div>
                <div><span class="font-semibold">一日の平均投稿数:</span> {{ number_format($average_posts_per_day, 2) }}</div>
                <div>
                    <span class="font-semibold">一日の最高投稿数:</span>
                    {{ number_format($max_posts_per_day) }}
                    @if($max_posts_per_day_date)
                        ({{ $max_posts_per_day_date }})
                    @endif
                </div>
                <div><span class="font-semibold">総いいね数:</span> {{ number_format($total_likes) }}</div>
                <div><span class="font-semibold">総リプライ数:</span> {{ number_format($total_replies) }}</div>
                <div><span class="font-semibold">総リポスト数:</span> {{ number_format($total_reposts) }}</div>
                <div><span class="font-semibold">フォロワー/フォロー比率:</span> {{ number_format($follower_following_ratio, 2) }}</div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">日別アクティビティグラフ</h2>
            <div class="mb-4">
                <label for="periodSelect" class="block text-sm font-medium text-gray-700">表示期間:</label>
                <select id="periodSelect"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="30">最近30日</option>
                    <option value="60">最近60日</option>
                    <option value="90">最近90日</option>
                    <option value="all">全期間</option>
                </select>
            </div>
            {{-- canvasを囲むdivを追加し、そのdivにサイズを指定 --}}
            <div class="w-full" style="height: 200px;">
                <canvas id="dailyActivityChart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const CHART_DATA_ALL = JSON.parse('{!! $chart_data_all->toJson() !!}');
            const CHART_DATA_30 = JSON.parse('{!! $chart_data_30->toJson() !!}');
            const CHART_DATA_60 = JSON.parse('{!! $chart_data_60->toJson() !!}');
            const CHART_DATA_90 = JSON.parse('{!! $chart_data_90->toJson() !!}');

            const CHART_DATA_MAP = {
                'all': CHART_DATA_ALL,
                '30' : CHART_DATA_30,
                '60' : CHART_DATA_60,
                '90' : CHART_DATA_90,
            };

            const ctx = document.getElementById('dailyActivityChart').getContext('2d');
            let daily_activity_chart;

            function update_chart(period) {
                const data_to_display = CHART_DATA_MAP[String(period)];

                if (!data_to_display) {
                    console.error('No data found for period:', period);
                    return;
                }

                if (daily_activity_chart) {
                    daily_activity_chart.destroy();
                }

                const chart_config = {
                    type   : 'bar',
                    data   : {
                        labels  : data_to_display.labels,
                        datasets: [
                            {
                                label          : '投稿数',
                                data           : data_to_display.posts,
                                borderColor    : 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.8)', // 濃いめの色に設定
                                tension        : 0.1,
                                fill           : false
                            },
                        ]
                    },
                    options: {
                        responsive         : true,
                        maintainAspectRatio: false,
                        plugins            : {
                            legend: {
                                display: false
                            }
                        },
                        scales             : {
                            x: {
                                type : 'time',
                                time : {
                                    unit          : 'day',
                                    tooltipFormat : 'YYYY-MM-DD',
                                    displayFormats: {
                                        day: 'YYYY-MM-DD'
                                    }
                                },
                                title: {
                                    display: true,
                                    text   : '日付'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title      : {
                                    display: true,
                                    text   : '件数'
                                }
                            }
                        }
                    }
                };

                daily_activity_chart = new Chart(ctx, chart_config);
            }

            document.getElementById('periodSelect').addEventListener('change', (event) => {
                update_chart(event.target.value);
            });

            // 初期表示
            update_chart('30');
        });
    </script>
@endpush
