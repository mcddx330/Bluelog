@extends('layouts.app')

@section('title', 'ステータス - ' . $handle)

@section('content')
    <x-profile-main-content :user="$user" :profile="$profile" :handle="$handle"/>

    <div class="mt-8">
        <h1 class="text-2xl font-bold">{{ "@". $handle }} のステータス</h1>

        <div class="lg:flex lg:space-x-8">
            <x-status-main-content
                :user="$user"
                :handle="$handle"
                :period_start="$period_start"
                :period_end="$period_end"
                :period_days="$period_days"
                :total_posts="$total_posts"
                :days_with_posts="$days_with_posts"
                :days_without_posts="$days_without_posts"
                :average_posts_per_day="$average_posts_per_day"
                :max_posts_per_day="$max_posts_per_day"
                :max_posts_per_day_date="$max_posts_per_day_date"
                :total_likes="$total_likes"
                :total_replies="$total_replies"
                :total_reposts="$total_reposts"
                :follower_following_ratio="$follower_following_ratio"
                :chart_data_all="$chart_data_all"
                :chart_data_30="$chart_data_30"
                :chart_data_60="$chart_data_60"
                :chart_data_90="$chart_data_90"
            />
            <x-profile-sidebar :user="$user" :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
        </div>
    </div>
@endsection
