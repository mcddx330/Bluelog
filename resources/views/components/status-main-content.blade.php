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
    'total_text_length',
    'average_text_per_post',
    'average_text_per_day',
    'follower_growth_pace',
    'following_growth_pace',
    'communication_rate',
    'chart_data_all',
    'chart_data_30',
    'chart_data_60',
    'chart_data_90',
    'posts_by_day_of_week',
    'posts_by_hour',
    'cumulative_posts',
])

<div class="lg:w-2/3">
    <div class="space-y-4">
        <div class="mt-8 bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4">全体統計</h2>
            <table class="min-w-full bg-white">
                <tbody>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">Bluesky登録日時:</td>
                        <td class="py-2 px-4">{{ $user->registered_at ? $user->registered_at->format('Y/m/d H:i:s') : 'N/A' }}</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">Bluelog上における記録期間:</td>
                        <td class="py-2 px-4">
                            @php
                                $period_start_carbon = $period_start instanceof \Carbon\Carbon
                                    ? $period_start
                                    : (is_string($period_start) ? \Carbon\Carbon::parse($period_start) : null);
                                $period_end_carbon = $period_end instanceof \Carbon\Carbon
                                    ? $period_end
                                    : (is_string($period_end) ? \Carbon\Carbon::parse($period_end) : null);
                            @endphp
                            {{ $period_start_carbon->format('Y/m/d') }}
                            〜
                            {{ $period_end_carbon->format('Y/m/d') }}
                            ({{ number_format($period_days) }} 日間)
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">総投稿数:</td>
                        <td class="py-2 px-4">{{ number_format($total_posts) }} 件</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">つぶやいた日数:</td>
                        <td class="py-2 px-4">{{ number_format($days_with_posts) }} 日</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">つぶやかなかった日数:</td>
                        <td class="py-2 px-4">{{ number_format($days_without_posts) }} 日</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">一日の平均投稿数:</td>
                        <td class="py-2 px-4">{{ number_format($average_posts_per_day, 2) }} 件</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">一日の最高投稿数:</td>
                        <td class="py-2 px-4">
                            {{ number_format($max_posts_per_day) }} 件
                            @if($max_posts_per_day_date)
                                ({{ $max_posts_per_day_date }})
                            @endif
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">総いいね数:</td>
                        <td class="py-2 px-4">{{ number_format($total_likes) }} 件</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">総リプライ数:</td>
                        <td class="py-2 px-4">{{ number_format($total_replies) }} 件</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">総リポスト数:</td>
                        <td class="py-2 px-4">{{ number_format($total_reposts) }} 件</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">フォロワー/フォロー比率:</td>
                        <td class="py-2 px-4">{{ number_format($follower_following_ratio, 2) }} 倍</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">総ツイート文字数:</td>
                        <td class="py-2 px-4">
                            {{ number_format($total_text_length) }} 文字
                            ({{ number_format($average_text_per_post, 2) }} 文字/件  {{ number_format($average_text_per_day, 2) }} 文字/日)
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">フォロー増加ペース (1日あたり):</td>
                        <td class="py-2 px-4">{{ number_format($following_growth_pace, 2) }} 人</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">フォロワー増加ペース (1日あたり):</td>
                        <td class="py-2 px-4">{{ number_format($follower_growth_pace, 2) }} 人</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-4 font-semibold">コミュニケーション率:</td>
                        <td class="py-2 px-4">{{ number_format($communication_rate, 2) }} %</td>
                    </tr>
                </tbody>
            </table>
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

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">曜日別アクティビティグラフ</h2>
            <div class="w-full" style="height: 200px;">
                <canvas id="postsByDayOfWeekChart"></canvas>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">時間帯別アクティビティグラフ</h2>
            <div class="w-full" style="height: 200px;">
                <canvas id="postsByHourChart"></canvas>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">累計ポスト推移グラフ</h2>
            <div class="w-full" style="height: 200px;">
                <canvas id="cumulativePostsChart"></canvas>
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
            const POSTS_BY_DAY_OF_WEEK = JSON.parse('{!! $posts_by_day_of_week->toJson() !!}');
            const POSTS_BY_HOUR = JSON.parse('{!! $posts_by_hour->toJson() !!}');
            const CUMULATIVE_POSTS = JSON.parse('{!! $cumulative_posts->toJson() !!}');

            const CHART_DATA_MAP = {
                'all': CHART_DATA_ALL,
                '30' : CHART_DATA_30,
                '60' : CHART_DATA_60,
                '90' : CHART_DATA_90,
            };

            const ctx_daily = document.getElementById('dailyActivityChart').getContext('2d');
            let daily_activity_chart;

            function update_daily_chart(period) {
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

                daily_activity_chart = new Chart(ctx_daily, chart_config);
            }

            document.getElementById('periodSelect').addEventListener('change', (event) => {
                update_daily_chart(event.target.value);
            });

            // 初期表示
            update_daily_chart('30');

            // 曜日別アクティビティグラフ
            const ctx_day_of_week = document.getElementById('postsByDayOfWeekChart').getContext('2d');
            new Chart(ctx_day_of_week, {
                type   : 'bar',
                data   : {
                    labels  : Object.keys(POSTS_BY_DAY_OF_WEEK),
                    datasets: [
                        {
                            label          : '投稿数',
                            data           : Object.values(POSTS_BY_DAY_OF_WEEK),
                            backgroundColor: 'rgba(153, 102, 255, 0.8)',
                            borderColor    : 'rgb(153, 102, 255)',
                            borderWidth    : 1
                        }
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
                        y: {
                            beginAtZero: true,
                            title      : {
                                display: true,
                                text   : '件数'
                            }
                        }
                    }
                }
            });

            // 時間帯別アクティビティグラフ
            const ctx_hour = document.getElementById('postsByHourChart').getContext('2d');
            new Chart(ctx_hour, {
                type   : 'bar',
                data   : {
                    labels  : Object.keys(POSTS_BY_HOUR),
                    datasets: [
                        {
                            label          : '投稿数',
                            data           : Object.values(POSTS_BY_HOUR),
                            backgroundColor: 'rgba(255, 159, 64, 0.8)',
                            borderColor    : 'rgb(255, 159, 64)',
                            borderWidth    : 1
                        }
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
                            title: {
                                display: true,
                                text   : '時間帯'
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
            });

            // 累計ポスト推移グラフ
            const ctx_cumulative = document.getElementById('cumulativePostsChart').getContext('2d');
            new Chart(ctx_cumulative, {
                type   : 'line',
                data   : {
                    labels  : CUMULATIVE_POSTS.map(item => item.date),
                    datasets: [
                        {
                            label          : '累計投稿数',
                            data           : CUMULATIVE_POSTS.map(item => item.cumulative_posts_count),
                            borderColor    : 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension        : 0.1,
                            fill           : true
                        }
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
                                text   : '累計件数'
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
