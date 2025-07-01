<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ステータス - {{ $handle }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">ステータス for <a href="{{ route('profile.show', ['handle' => $handle]) }}" class="text-blue-500 hover:underline">{{ $handle }}</a></h1>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">全体統計</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div><span class="font-semibold">記期間:</span> {{ $period_start ? $period_start->format('Y-m-d') . ' - ' . $period_end->format('Y-m-d') : 'N/A' }} ({{ number_format($period_days) }} 日間)</div>
                <div><span class="font-semibold">Bluesky登録日時:</span> {{ $user->registered_at ? $user->registered_at->format('Y-m-d H:i:s') : 'N/A' }}</div>
                <div><span class="font-semibold">総投稿数:</span> {{ number_format($total_posts) }}</div>
                <div><span class="font-semibold">つぶやいた日数:</span> {{ number_format($days_with_posts) }}</div>
                <div><span class="font-semibold">つぶやかなかった日数:</span> {{ number_format($days_without_posts) }}</div>
                <div><span class="font-semibold">一日の平均投稿数:</span> {{ number_format($average_posts_per_day, 2) }}</div>
                <div><span class="font-semibold">一日の最高投稿数:</span> {{ number_format($max_posts_per_day) }} @if($max_posts_per_day_date) ({{ $max_posts_per_day_date }}) @endif</div>
                <div><span class="font-semibold">総いいね数:</span> {{ number_format($total_likes) }}</div>
                <div><span class="font-semibold">総リプライ数:</span> {{ number_format($total_replies) }}</div>
                <div><span class="font-semibold">総リポスト数:</span> {{ number_format($total_reposts) }}</div>
                <div><span class="font-semibold">総メンション数:</span> {{ number_format($total_mentions) }}</div>
                <div><span class="font-semibold">フォロワー/フォロー比率:</span> {{ number_format($follower_following_ratio, 2) }}</div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">日別アクティビティグラフ</h2>
            <div class="mb-4">
                <label for="periodSelect" class="block text-sm font-medium text-gray-700">表示期間:</label>
                <select id="periodSelect" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
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

{{--        <div class="bg-white shadow-md rounded-lg p-6">--}}
{{--            <h2 class="text-xl font-bold mb-4">日別アクティビティテーブル</h2>--}}
{{--            @if($stats->isEmpty())--}}
{{--                <p>まだ統計データがありません。</p>--}}
{{--            @else--}}
{{--                <div class="overflow-x-auto">--}}
{{--                    <table class="min-w-full bg-white">--}}
{{--                        <thead class="bg-gray-200">--}}
{{--                            <tr>--}}
{{--                                <th class="py-2 px-4">日付</th>--}}
{{--                                <th class="py-2 px-4">投稿</th>--}}
{{--                                <th class="py-2 px-4">いいね</th>--}}
{{--                                <th class="py-2 px-4">リプライ</th>--}}
{{--                                <th class="py-2 px-4">リポスト</th>--}}
{{--                                <th class="py-2 px-4">メンション</th>--}}
{{--                            </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                            @foreach($stats as $stat)--}}
{{--                                <tr class="text-center border-b">--}}
{{--                                    <td class="py-2 px-4">{{ $stat->date->format('Y-m-d') }}</td>--}}
{{--                                    <td class="py-2 px-4">{{ number_format($stat->posts_count) }}</td>--}}
{{--                                    <td class="py-2 px-4">{{ number_format($stat->likes_count) }}</td>--}}
{{--                                    <td class="py-2 px-4">{{ number_format($stat->replies_count) }}</td>--}}
{{--                                    <td class="py-2 px-4">{{ number_format($stat->reposts_count) }}</td>--}}
{{--                                    <td class="py-2 px-4">{{ number_format($stat->mentions_count) }}</td>--}}
{{--                                </tr>--}}
{{--                            @endforeach--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            @endif--}}
{{--        </div>--}}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const CHART_DATA_ALL = JSON.parse(@json($chart_data_all));
            const CHART_DATA_30 = JSON.parse(@json($chart_data_30));
            const CHART_DATA_60 = JSON.parse(@json($chart_data_60));
            const CHART_DATA_90 = JSON.parse(@json($chart_data_90));

            const CHART_DATA_MAP = {
                'all': CHART_DATA_ALL,
                '30': CHART_DATA_30,
                '60': CHART_DATA_60,
                '90': CHART_DATA_90,
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
                    type: 'bar',
                    data: {
                        labels: data_to_display.labels,
                        datasets: [
                            {
                                label: '投稿数',
                                data: data_to_display.posts,
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.8)', // 濃いめの色に設定
                                tension: 0.1,
                                fill: false
                            },
                            //{
                            //    label: 'いいね数',
                            //    data: data_to_display.likes,
                            //    borderColor: 'rgb(255, 99, 132)',
                            //    backgroundColor: 'rgba(255, 99, 132, 0.8)', // 濃いめの色に設定
                            //    tension: 0.1,
                            //    fill: false
                            //},
                            //{
                            //    label: 'リプライ数',
                            //    data: data_to_display.replies,
                            //    borderColor: 'rgb(54, 162, 235)',
                            //    backgroundColor: 'rgba(54, 162, 235, 0.8)', // 濃いめの色に設定
                            //    tension: 0.1,
                            //    fill: false
                            //},
                            //{
                            //    label: 'リポスト数',
                            //    data: data_to_display.reposts,
                            //    borderColor: 'rgb(255, 205, 86)',
                            //    backgroundColor: 'rgba(255, 205, 86, 0.8)', // 濃いめの色に設定
                            //    tension: 0.1,
                            //    fill: false
                            //},
                            //{
                            //    label: 'メンション数',
                            //    data: data_to_display.mentions,
                            //    borderColor: 'rgb(153, 102, 255)',
                            //    backgroundColor: 'rgba(153, 102, 255, 0.8)', // 濃いめの色に設定
                            //    tension: 0.1,
                            //    fill: false
                            //}
                        ]
                    },
                    options: {
                        responsive: true, // ここをtrueに戻す
                        maintainAspectRatio: false, // これを維持
                        plugins: {
                            legend: {
                                display: false // 凡例を非表示にする
                            }
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day',
                                    tooltipFormat: 'YYYY-MM-DD',
                                    displayFormats: {
                                        day: 'YYYY-MM-DD'
                                    }
                                },
                                title: {
                                    display: true,
                                    text: '日付'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '件数'
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
</body>
</html>
